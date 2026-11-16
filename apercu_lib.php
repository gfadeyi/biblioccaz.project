<?php

const APERCU_COOKIE = 'BIBLIOCCAZ_APERCU';
const APERCU_DUREE = 3600;
const APERCU_ROLES = ['admin', 'gestionnaire', 'moderateur'];

function cleApercu(): string
{
    static $cle = null;
    if ($cle !== null) { return $cle; }
    $fichier = dossierPrive('cle_apercu.txt');
    $contenu = is_file($fichier) ? trim((string) @file_get_contents($fichier)) : '';
    if (strlen($contenu) < 64) {
        $contenu = bin2hex(random_bytes(32));
        if (@file_put_contents($fichier, $contenu, LOCK_EX) === false) { $contenu = ''; }
    }
    return $cle = $contenu;
}

function optionsCookieApercu(int $expiration): array
{
    $chemin = (string) parse_url(SITE_URL, PHP_URL_PATH);
    return ['expires' => $expiration, 'path' => rtrim($chemin, '/') . '/', 'secure' => modeProduction() || connexionHttps(), 'httponly' => true, 'samesite' => 'Lax'];
}

function signatureApercu(string $donnees): string
{
    return hash_hmac('sha256', 'apercu|' . $donnees, cleApercu());
}

function ouvrirApercuBackOffice(int $idUser): bool
{
    if ($idUser <= 0 || cleApercu() === '') { return false; }
    $expiration = time() + APERCU_DUREE;
    $donnees = $idUser . '.' . $expiration;
    if (!headers_sent()) { setcookie(APERCU_COOKIE, $donnees . '.' . signatureApercu($donnees), optionsCookieApercu($expiration)); }
    return true;
}

function fermerApercuBackOffice(): void
{
    if (!headers_sent()) { setcookie(APERCU_COOKIE, '', optionsCookieApercu(time() - 3600)); }
    unset($_COOKIE[APERCU_COOKIE]);
}

function apercuBackOffice(PDO $pdo): ?array
{
    static $resultat = false;
    if ($resultat !== false) { return $resultat; }
    $valeur = $_COOKIE[APERCU_COOKIE] ?? null;
    if (!is_string($valeur) || !preg_match('/\A(\d{1,10})\.(\d{10})\.([a-f0-9]{64})\z/', $valeur, $parties) || cleApercu() === '') { return $resultat = null; }
    [, $idUser, $expiration, $signature] = $parties;
    if ((int) $expiration < time() || !hash_equals(signatureApercu($idUser . '.' . $expiration), $signature)) { return $resultat = null; }
    try {
        $stmt = $pdo->prepare("SELECT pseudo, role FROM user WHERE id = ? AND statut = 'actif' AND email_verifie = 1");
        $stmt->execute([(int) $idUser]);
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return $resultat = null;
    }
    if (!$membre || !in_array($membre['role'], APERCU_ROLES, true)) { return $resultat = null; }
    return $resultat = ['pseudo' => $membre['pseudo'], 'role' => $membre['role'], 'expiration' => (int) $expiration];
}
