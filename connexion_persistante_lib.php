<?php

const CONNEXION_PERSISTANTE_COOKIE = 'BIBLIOCCAZ_SOUVENIR';
const CONNEXION_PERSISTANTE_JOURS = 30;
const CONNEXION_PERSISTANTE_ROLES = ['client'];

function connexionPersistanteDisponible(PDO $pdo): bool
{
    static $disponible = null;
    if ($disponible !== null) { return $disponible; }
    try {
        $pdo->prepare('SELECT selecteur, empreinte_validateur, empreinte_mdp, date_expiration FROM connexion_persistante LIMIT 0')->execute();
        return $disponible = true;
    } catch (PDOException $e) {
        if (in_array((int) ($e->errorInfo[1] ?? 0), [1146, 1054], true)) { return $disponible = false; }
        throw $e;
    }
}

function connexionPersistantePermise(array $membre): bool
{
    return in_array($membre['role'] ?? '', CONNEXION_PERSISTANTE_ROLES, true);
}

function optionsCookiePersistant(int $expiration): array
{
    $chemin = (string) parse_url(SITE_URL, PHP_URL_PATH);
    return ['expires' => $expiration, 'path' => rtrim($chemin, '/') . '/', 'secure' => modeProduction() || connexionHttps(), 'httponly' => true, 'samesite' => 'Lax'];
}

function ecrireCookiePersistant(string $valeur, int $expiration): void
{
    if (!headers_sent()) { setcookie(CONNEXION_PERSISTANTE_COOKIE, $valeur, optionsCookiePersistant($expiration)); }
    $_COOKIE[CONNEXION_PERSISTANTE_COOKIE] = $valeur;
}

function effacerCookiePersistant(): void
{
    if (!array_key_exists(CONNEXION_PERSISTANTE_COOKIE, $_COOKIE)) { return; }
    if (!headers_sent()) { setcookie(CONNEXION_PERSISTANTE_COOKIE, '', optionsCookiePersistant(time() - 3600)); }
    unset($_COOKIE[CONNEXION_PERSISTANTE_COOKIE]);
}

function lireCookiePersistant(): ?array
{
    $valeur = $_COOKIE[CONNEXION_PERSISTANTE_COOKIE] ?? null;
    if (!is_string($valeur) || !preg_match('/\A([a-f0-9]{24}):([a-f0-9]{64})\z/', $valeur, $parties)) { return null; }
    return [$parties[1], $parties[2]];
}

function creerConnexionPersistante(PDO $pdo, array $membre): bool
{
    if (!connexionPersistantePermise($membre) || !connexionPersistanteDisponible($pdo)) { return false; }
    $selecteur = bin2hex(random_bytes(12));
    $validateur = bin2hex(random_bytes(32));
    $expiration = time() + CONNEXION_PERSISTANTE_JOURS * 86400;
    $pdo->prepare('DELETE FROM connexion_persistante WHERE id_user = ? AND date_expiration <= NOW()')->execute([$membre['id']]);
    $pdo->prepare('INSERT INTO connexion_persistante (id_user, selecteur, empreinte_validateur, empreinte_mdp, date_expiration, appareil) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), ?)')
        ->execute([$membre['id'], $selecteur, hash('sha256', $validateur), hash('sha256', $membre['mot_de_passe']), $expiration, libelleAppareil((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))]);
    ecrireCookiePersistant($selecteur . ':' . $validateur, $expiration);
    return true;
}

function supprimerConnexionsPersistantes(PDO $pdo, int $idUser): int
{
    if (!connexionPersistanteDisponible($pdo)) { return 0; }
    $requete = $pdo->prepare('DELETE FROM connexion_persistante WHERE id_user = ?');
    $requete->execute([$idUser]);
    return $requete->rowCount();
}

function supprimerConnexionPersistanteCourante(PDO $pdo): void
{
    $cookie = lireCookiePersistant();
    if ($cookie && connexionPersistanteDisponible($pdo)) {
        $pdo->prepare('DELETE FROM connexion_persistante WHERE selecteur = ?')->execute([$cookie[0]]);
    }
    effacerCookiePersistant();
}

function restaurerConnexionPersistante(PDO $pdo): bool
{
    $cookie = lireCookiePersistant();
    if (!$cookie) {
        effacerCookiePersistant();
        return false;
    }
    if (session_name() === 'BIBLIOCCAZ_ADMIN' || !connexionPersistanteDisponible($pdo)) { return false; }
    [$selecteur, $validateur] = $cookie;
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare('SELECT c.id_connexion, c.empreinte_validateur, c.empreinte_mdp, UNIX_TIMESTAMP(c.date_expiration) AS expiration, c.date_expiration > NOW() AS en_cours,
                u.id, u.pseudo, u.role, u.statut, u.email_verifie, u.mot_de_passe, u.nom, u.prenom
            FROM connexion_persistante c INNER JOIN user u ON u.id = c.id_user WHERE c.selecteur = ? FOR UPDATE');
        $requete->execute([$selecteur]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$ligne) {
            $pdo->commit();
            return false;
        }
        if (!hash_equals($ligne['empreinte_validateur'], hash('sha256', $validateur))) {
            $pdo->prepare('DELETE FROM connexion_persistante WHERE id_user = ?')->execute([$ligne['id']]);
            $pdo->commit();
            insertLog('SECURITE', 'Jeton « rester connecté » invalide : connexions persistantes du compte #' . (int) $ligne['id'] . ' supprimées');
            effacerCookiePersistant();
            return false;
        }
        $valide = (int) $ligne['en_cours'] === 1 && connexionPersistantePermise($ligne) && $ligne['statut'] === 'actif'
            && (int) $ligne['email_verifie'] === 1 && hash_equals($ligne['empreinte_mdp'], hash('sha256', $ligne['mot_de_passe']));
        if (!$valide) {
            $pdo->prepare('DELETE FROM connexion_persistante WHERE id_connexion = ?')->execute([$ligne['id_connexion']]);
            $pdo->commit();
            effacerCookiePersistant();
            return false;
        }
        $nouveauSelecteur = bin2hex(random_bytes(12));
        $nouveauValidateur = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE connexion_persistante SET selecteur = ?, empreinte_validateur = ?, derniere_utilisation = NOW() WHERE id_connexion = ?')
            ->execute([$nouveauSelecteur, hash('sha256', $nouveauValidateur), $ligne['id_connexion']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    ecrireCookiePersistant($nouveauSelecteur . ':' . $nouveauValidateur, (int) $ligne['expiration']);
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['user_id'] = $ligne['id'];
    $_SESSION['pseudo'] = $ligne['pseudo'];
    $_SESSION['role'] = $ligne['role'];
    $_SESSION['nom'] = $ligne['nom'];
    $_SESSION['prenom'] = $ligne['prenom'];
    $_SESSION['connexion_time'] = time();
    try {
        enregistrerSessionUtilisateur($pdo, $ligne);
    } catch (RuntimeException $e) {
        $_SESSION = [];
        return false;
    }
    insertLog('CONNEXION', 'Reconnexion automatique « rester connecté »');
    return true;
}
