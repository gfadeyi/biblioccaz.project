<?php

const SESSION_INACTIVITE = 600;

function modeProduction(): bool
{
    return defined('MODE_PRODUCTION') && MODE_PRODUCTION === true;
}

function connexionHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function dossierPrive(string $chemin = ''): string
{
    $dossier = defined('DOSSIER_PRIVE') && DOSSIER_PRIVE !== '' ? rtrim(str_replace('\\', '/', DOSSIER_PRIVE), '/') : __DIR__ . '/secure_data';
    return $chemin === '' ? $dossier : $dossier . '/' . $chemin;
}

function demarrerSession(): void
{
    if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_NONE) {
        return;
    }
    require_once __DIR__ . '/secrets.php';
    $dossierSessions = dossierPrive('sessions');
    if (modeProduction() && (is_dir($dossierSessions) || mkdir($dossierSessions, 0700, true) || is_dir($dossierSessions))) {
        session_save_path($dossierSessions);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => modeProduction() || connexionHttps()]);
    session_start();
}

function conditionSessionValide(bool $verifierRole = true): string
{
    return "s.date_revocation IS NULL AND s.derniere_activite > DATE_SUB(?, INTERVAL 600 SECOND)
        AND s.empreinte_mdp = SHA2(u.mot_de_passe, 256) AND u.email_verifie = 1 AND u.statut IN ('actif','valide_moderateur')" . ($verifierRole ? ' AND s.role_connexion = u.role' : '');
}

function roleSessionDisponible(PDO $pdo): bool
{
    try { $pdo->prepare('SELECT role_connexion FROM session_utilisateur LIMIT 0')->execute(); return true; }
    catch (PDOException $e) { if (in_array((int) ($e->errorInfo[1] ?? 0),[1146,1054],true)) { return false; } throw $e; }
}

function appareilSessionDisponible(PDO $pdo): bool
{
    try { $pdo->prepare('SELECT appareil FROM session_utilisateur LIMIT 0')->execute(); return true; }
    catch (PDOException $e) { if (in_array((int) ($e->errorInfo[1] ?? 0),[1146,1054],true)) { return false; } throw $e; }
}

function libelleAppareil(string $agent): ?string
{
    $navigateurs = ['SamsungBrowser' => 'Samsung Internet', 'Edg' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'FxiOS' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'];
    $systemes = ['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'CrOS' => 'ChromeOS', 'Macintosh' => 'macOS', 'Linux' => 'Linux'];
    $trouver = function (array $motifs) use ($agent): ?string {
        foreach ($motifs as $motif => $libelle) {
            if (str_contains($agent, $motif)) { return $libelle; }
        }
        return null;
    };
    $parties = array_filter([$trouver($navigateurs), $trouver($systemes)]);
    return $parties ? implode(' · ', $parties) : null;
}

function empreinteSessionCourante(): string
{
    return hash('sha256', session_name() . ':' . session_id());
}

function suiviSessionsDisponible(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT id_suivi FROM session_utilisateur LIMIT 1')->execute();
        return true;
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1146) { return false; }
        throw $e;
    }
}

function detruireSessionCourante(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parametres = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $parametres['path'], 'domain' => $parametres['domain'], 'secure' => $parametres['secure'], 'httponly' => true, 'samesite' => $parametres['samesite'] ?: 'Lax']);
    }
    session_destroy();
}

function terminerSuiviSession(PDO $pdo): void
{
    if (suiviSessionsDisponible($pdo)) {
        $pdo->prepare('UPDATE session_utilisateur SET date_revocation = COALESCE(date_revocation, NOW()) WHERE empreinte = ?')->execute([empreinteSessionCourante()]);
    }
}

function refuserSession(PDO $pdo, string $motif, string $raison = 'inconnue'): void
{
    terminerSuiviSession($pdo);
    insertLog('DECONNEXION', $motif);
    detruireSessionCourante();
    session_id('');
    session_start();
    $_SESSION['motif_deconnexion'] = $raison;
    if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'message_nouveaux.php') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['nouveaux' => 0, 'authentification_requise' => true]);
        exit;
    }
    header('Location: login.php');
    exit;
}

function enregistrerSessionUtilisateur(PDO $pdo, array $membre): void
{
    $_SESSION['empreinte_auth'] = hash('sha256', $membre['mot_de_passe']);
    $_SESSION['last_activity'] = time();
    $_SESSION['suivi_sessions_actif'] = suiviSessionsDisponible($pdo);
    if (!$_SESSION['suivi_sessions_actif']) { return; }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT mot_de_passe, statut, email_verifie, role FROM user WHERE id = ? FOR UPDATE');
        $stmt->execute([$membre['id']]);
        $actuel = $stmt->fetch();
        if (!$actuel || ($membre['role'] ?? $_SESSION['role'] ?? null) !== $actuel['role'] || !hash_equals($membre['mot_de_passe'], $actuel['mot_de_passe']) || !(int) $actuel['email_verifie'] || in_array($actuel['statut'], ['refuse_temporaire', 'refuse_definitif', 'banni'], true)) {
            throw new RuntimeException('Le compte a changé. Merci de vous reconnecter.');
        }
        $colonnes = ['id_user', 'empreinte', 'empreinte_mdp', 'espace'];
        $valeurs = [$membre['id'], empreinteSessionCourante(), $_SESSION['empreinte_auth'], session_name() === 'BIBLIOCCAZ_ADMIN' ? 'admin' : 'site'];
        if (roleSessionDisponible($pdo)) { $colonnes[] = 'role_connexion'; $valeurs[] = $actuel['role']; }
        if (appareilSessionDisponible($pdo)) { $colonnes[] = 'appareil'; $valeurs[] = libelleAppareil((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')); }
        $pdo->prepare('INSERT INTO session_utilisateur (' . implode(', ', $colonnes) . ') VALUES (' . implode(', ', array_fill(0, count($colonnes), '?')) . ')')->execute($valeurs);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['empreinte_auth']);
        throw $e;
    }
}

function controlerSessionUtilisateur(PDO $pdo, bool $actualiser = true): void
{
    $inactive = isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] >= SESSION_INACTIVITE;
    if (!isset($_SESSION['user_id'])) {
        if ($inactive) { refuserSession($pdo,'Expiration après dix minutes sans activité','expiree'); }
        $_SESSION['last_activity'] = time(); return;
    }
    $stmt = $pdo->prepare('SELECT id, pseudo, role, statut, email_verifie, mot_de_passe, nom, prenom FROM user WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $membre = $stmt->fetch();
    if ($membre && in_array($membre['statut'], ['refuse_temporaire','refuse_definitif','banni'], true)) { refuserSession($pdo,'Compte suspendu','suspendue'); }
    if ($membre && isset($_SESSION['empreinte_auth']) && !hash_equals(hash('sha256',$membre['mot_de_passe']),$_SESSION['empreinte_auth'])) { refuserSession($pdo,'Mot de passe modifié','mot_de_passe'); }
    if (!$membre || !isset($_SESSION['empreinte_auth']) || !hash_equals(hash('sha256', $membre['mot_de_passe']), $_SESSION['empreinte_auth']) || ($_SESSION['role'] ?? null) !== $membre['role'] || !(int) $membre['email_verifie'] || !in_array($membre['statut'], ['actif', 'valide_moderateur'], true)) {
        refuserSession($pdo, 'Session invalidée après modification du compte');
    }
    $suiviDisponible = suiviSessionsDisponible($pdo);
    if (!$suiviDisponible && !empty($_SESSION['suivi_sessions_actif'])) {
        refuserSession($pdo, 'Registre des sessions indisponible');
    }
    if ($suiviDisponible) {
        $avecRole = roleSessionDisponible($pdo);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id_suivi, date_revocation, id_revoque_par' . ($avecRole ? ', role_connexion' : '') . ', (derniere_activite > DATE_SUB(NOW(), INTERVAL 600 SECOND)) AS recente FROM session_utilisateur WHERE empreinte = ? AND id_user = ? AND empreinte_mdp = ? FOR UPDATE');
            $stmt->execute([empreinteSessionCourante(), $membre['id'], $_SESSION['empreinte_auth']]);
            $suivi = $stmt->fetch();
            $roleValide = !$avecRole || ($suivi && $suivi['role_connexion'] === $membre['role']);
            if ($suivi && $roleValide && !$suivi['date_revocation'] && $suivi['recente'] && !$inactive && $actualiser) {
                $pdo->prepare('UPDATE session_utilisateur SET derniere_activite = NOW() WHERE id_suivi = ?')->execute([$suivi['id_suivi']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
        if (!$suivi) { refuserSession($pdo, 'Session non enregistrée'); }
        if (!$roleValide) { refuserSession($pdo,'Rôle de connexion absent ou modifié'); }
        if ($suivi['date_revocation']) { refuserSession($pdo, 'Session fermée', $suivi['id_revoque_par'] ? 'fermee' : 'inconnue'); }
        if (!$suivi['recente']) { refuserSession($pdo,'Expiration du registre après dix minutes','expiree'); }
    }
    if ($inactive) { refuserSession($pdo,'Expiration après dix minutes sans activité','expiree'); }
    foreach (['pseudo', 'role', 'nom', 'prenom'] as $champ) { $_SESSION[$champ] = $membre[$champ]; }
    if ($actualiser) { $_SESSION['last_activity'] = time(); }
}

function finSessionSuspendue(): bool
{
    return ($_SESSION['motif_deconnexion'] ?? '') === 'suspendue';
}

function messageFinSession(): string
{
    $raison = $_SESSION['motif_deconnexion'] ?? '';
    unset($_SESSION['motif_deconnexion']);
    return match ($raison) {
        'fermee' => 'Votre session a été fermée. Veuillez vous reconnecter.',
        'expiree' => 'Votre session a expiré. Veuillez vous reconnecter.',
        'mot_de_passe' => 'Votre session a été invalidée après modification du mot de passe. Merci de vous reconnecter.',
        'suspendue' => 'Votre compte est suspendu. La connexion est interrompue.',
        'inconnue' => "Votre session n'est plus valide. Veuillez vous reconnecter.",
        default => '',
    };
}

function sessionsActives(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT s.id_suivi, s.id_user, s.espace, s.date_connexion, s.derniere_activite, u.pseudo, u.role,
        CASE WHEN s.empreinte = ? THEN 1 ELSE 0 END AS courante
        FROM session_utilisateur s INNER JOIN user u ON u.id = s.id_user
        WHERE " . conditionSessionValide(roleSessionDisponible($pdo)) . "
        ORDER BY s.derniere_activite DESC, s.id_suivi DESC LIMIT 500");
    $stmt->execute([empreinteSessionCourante(),$pdo->query('SELECT NOW()')->fetchColumn()]);
    return $stmt->fetchAll();
}

function revoquerSessionsUtilisateur(PDO $pdo, int $idAdministrateur, int $idMembre, ?int $idSuivi): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, role, statut, email_verifie FROM user WHERE id IN (?, ?) ORDER BY id FOR UPDATE');
        $stmt->execute([$idAdministrateur, $idMembre]);
        $membres = [];
        foreach ($stmt->fetchAll() as $membre) { $membres[(int) $membre['id']] = $membre; }
        $acteur = $membres[$idAdministrateur] ?? null;
        $cible = $membres[$idMembre] ?? null;
        if (!$acteur || $acteur['role'] !== 'admin' || !(int) $acteur['email_verifie'] || $acteur['statut'] !== 'actif' || !$cible || ($cible['role'] === 'admin' && $idMembre !== $idAdministrateur)) {
            throw new DomainException('Cette action sur les sessions de ce compte est interdite.');
        }
        $sql = 'UPDATE session_utilisateur s INNER JOIN user u ON u.id=s.id_user SET s.date_revocation = NOW(), s.id_revoque_par = ? WHERE s.id_user = ? AND ' . conditionSessionValide(roleSessionDisponible($pdo));
        $parametres = [$idAdministrateur, $idMembre, $pdo->query('SELECT NOW()')->fetchColumn()];
        if ($idSuivi !== null) { $sql .= ' AND s.id_suivi = ?'; $parametres[] = $idSuivi; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametres);
        $nombre = $stmt->rowCount();
        $pdo->commit();
        return $nombre;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}
