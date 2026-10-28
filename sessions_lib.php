<?php

const SESSION_INACTIVITE = 600;

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

function refuserSession(PDO $pdo, string $motif): void
{
    terminerSuiviSession($pdo);
    insertLog('DECONNEXION', $motif);
    detruireSessionCourante();
    header('Location: login.php?session=expiree');
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
        $stmt = $pdo->prepare('SELECT mot_de_passe, statut, email_verifie FROM user WHERE id = ? FOR UPDATE');
        $stmt->execute([$membre['id']]);
        $actuel = $stmt->fetch();
        if (!$actuel || !hash_equals($membre['mot_de_passe'], $actuel['mot_de_passe']) || !(int) $actuel['email_verifie'] || in_array($actuel['statut'], ['refuse_temporaire', 'refuse_definitif', 'banni'], true)) {
            throw new RuntimeException('Le compte a changé. Merci de vous reconnecter.');
        }
        $pdo->prepare('INSERT INTO session_utilisateur (id_user, empreinte, empreinte_mdp, espace) VALUES (?, ?, ?, ?)')
            ->execute([$membre['id'], empreinteSessionCourante(), $_SESSION['empreinte_auth'], session_name() === 'BIBLIOCCAZ_ADMIN' ? 'admin' : 'site']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['empreinte_auth']);
        throw $e;
    }
}

function controlerSessionUtilisateur(PDO $pdo, bool $actualiser = true): void
{
    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] >= SESSION_INACTIVITE) {
        refuserSession($pdo, 'Expiration après dix minutes sans activité');
    }
    if (!isset($_SESSION['user_id'])) { $_SESSION['last_activity'] = time(); return; }
    $stmt = $pdo->prepare('SELECT id, pseudo, role, statut, email_verifie, mot_de_passe, nom, prenom FROM user WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $membre = $stmt->fetch();
    if (!$membre || !isset($_SESSION['empreinte_auth']) || !hash_equals(hash('sha256', $membre['mot_de_passe']), $_SESSION['empreinte_auth']) || ($_SESSION['role'] ?? null) !== $membre['role'] || !(int) $membre['email_verifie'] || !in_array($membre['statut'], ['actif', 'valide_moderateur'], true)) {
        refuserSession($pdo, 'Session invalidée après modification du compte');
    }
    $suiviDisponible = suiviSessionsDisponible($pdo);
    if (!$suiviDisponible && !empty($_SESSION['suivi_sessions_actif'])) {
        refuserSession($pdo, 'Registre des sessions indisponible');
    }
    if ($suiviDisponible) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id_suivi FROM session_utilisateur WHERE empreinte = ? AND id_user = ? AND empreinte_mdp = ? AND date_revocation IS NULL AND derniere_activite > DATE_SUB(NOW(), INTERVAL 600 SECOND) FOR UPDATE');
            $stmt->execute([empreinteSessionCourante(), $membre['id'], $_SESSION['empreinte_auth']]);
            $suivi = $stmt->fetch();
            if ($suivi && $actualiser) {
                $pdo->prepare('UPDATE session_utilisateur SET derniere_activite = NOW() WHERE id_suivi = ?')->execute([$suivi['id_suivi']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
        if (!$suivi) { refuserSession($pdo, 'Session révoquée, expirée ou non enregistrée'); }
    }
    foreach (['pseudo', 'role', 'nom', 'prenom'] as $champ) { $_SESSION[$champ] = $membre[$champ]; }
    if ($actualiser) { $_SESSION['last_activity'] = time(); }
}

function sessionsActives(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT s.id_suivi, s.id_user, s.espace, s.date_connexion, s.derniere_activite, u.pseudo, u.role,
        CASE WHEN s.empreinte = ? THEN 1 ELSE 0 END AS courante
        FROM session_utilisateur s INNER JOIN user u ON u.id = s.id_user
        WHERE s.date_revocation IS NULL AND s.derniere_activite > DATE_SUB(NOW(), INTERVAL 600 SECOND)
        AND s.empreinte_mdp = SHA2(u.mot_de_passe, 256) AND u.email_verifie = 1
        AND u.statut IN ('actif', 'valide_moderateur')
        ORDER BY s.derniere_activite DESC, s.id_suivi DESC LIMIT 500");
    $stmt->execute([empreinteSessionCourante()]);
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
        $sql = 'UPDATE session_utilisateur SET date_revocation = NOW(), id_revoque_par = ? WHERE id_user = ? AND date_revocation IS NULL';
        $parametres = [$idAdministrateur, $idMembre];
        if ($idSuivi !== null) { $sql .= ' AND id_suivi = ?'; $parametres[] = $idSuivi; }
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
