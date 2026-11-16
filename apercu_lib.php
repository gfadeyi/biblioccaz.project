<?php

const APERCU_COOKIE = 'BIBLIOCCAZ_APERCU';
const APERCU_SESSION = 'BIBLIOCCAZ_VUE';
const APERCU_DUREE = 3600;
const APERCU_ROLES = ['admin', 'gestionnaire', 'moderateur'];
const APERCU_VUES = ['invite' => 'Vue Invité', 'client' => 'Vue Client'];
const APERCU_EMAIL_CLIENT = 'lecteur.apercu@biblioccaz.invalid';

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

function ouvrirApercuBackOffice(int $idUser, string $vue = 'invite'): bool
{
    if ($idUser <= 0 || !isset(APERCU_VUES[$vue]) || cleApercu() === '') { return false; }
    $expiration = time() + APERCU_DUREE;
    $donnees = $idUser . '.' . $expiration . '.' . $vue;
    if (!headers_sent()) { setcookie(APERCU_COOKIE, $donnees . '.' . signatureApercu($donnees), optionsCookieApercu($expiration)); }
    return true;
}

function fermerApercuBackOffice(): void
{
    if (!headers_sent()) {
        setcookie(APERCU_COOKIE, '', optionsCookieApercu(time() - 3600));
        setcookie(APERCU_SESSION, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => modeProduction() || connexionHttps(), 'httponly' => true, 'samesite' => 'Lax']);
    }
    unset($_COOKIE[APERCU_COOKIE]);
}

function jetonApercu(): ?array
{
    static $jeton = false;
    if ($jeton !== false) { return $jeton; }
    $valeur = $_COOKIE[APERCU_COOKIE] ?? null;
    if (!defined('SITE_URL') || !is_string($valeur) || !preg_match('/\A(\d{1,10})\.(\d{10})\.(invite|client)\.([a-f0-9]{64})\z/', $valeur, $parties) || cleApercu() === '') { return $jeton = null; }
    [, $idUser, $expiration, $vue, $signature] = $parties;
    if ((int) $expiration < time() || !hash_equals(signatureApercu($idUser . '.' . $expiration . '.' . $vue), $signature)) { return $jeton = null; }
    return $jeton = ['id' => (int) $idUser, 'expiration' => (int) $expiration, 'vue' => $vue];
}

function apercuBackOffice(PDO $pdo): ?array
{
    static $resultat = false;
    if ($resultat !== false) { return $resultat; }
    $jeton = session_name() === APERCU_SESSION ? jetonApercu() : null;
    if ($jeton === null) { return $resultat = null; }
    try {
        $stmt = $pdo->prepare("SELECT pseudo, role FROM user WHERE id = ? AND statut = 'actif' AND email_verifie = 1");
        $stmt->execute([$jeton['id']]);
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return $resultat = null;
    }
    if (!$membre || !in_array($membre['role'], APERCU_ROLES, true)) { return $resultat = null; }
    return $resultat = ['pseudo' => $membre['pseudo'], 'role' => $membre['role'], 'expiration' => $jeton['expiration'], 'vue' => $jeton['vue']];
}

function apercuActif(): bool
{
    return !empty($GLOBALS['apercuSite']);
}

function clientApercu(PDO $pdo): ?array
{
    try {
        $stmt = $pdo->prepare("SELECT id, pseudo, role, nom, prenom FROM user WHERE email = ? AND role = 'client' AND statut = 'actif'");
        $stmt->execute([APERCU_EMAIL_CLIENT]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function refuserActionApercu(): never
{
    $message = "Action indisponible dans l’aperçu : le site y est en lecture seule, rien n’est enregistré.";
    http_response_code(403);
    header('Cache-Control: no-store');
    if (requeteAttendJson()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erreur' => 'apercu', 'apercu' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $racine = rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/') . '/';
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Aperçu en lecture seule — BIBLIOccaz</title><link rel="stylesheet" href="' . htmlspecialchars($racine, ENT_QUOTES) . 'style.css"></head><body><main class="page-formulaire"><section class="carte-formulaire" role="alert"><h1>Aperçu en lecture seule</h1><p>' . htmlspecialchars($message) . '</p><a class="bouton bouton-principal" href="javascript:history.back()">Revenir à la page précédente</a></section></main></body></html>';
    exit;
}

function appliquerApercu(PDO $pdo, array $apercu): void
{
    $GLOBALS['apercuSite'] = $apercu;
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) { refuserActionApercu(); }
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    foreach (['user_id', 'pseudo', 'role', 'nom', 'prenom', 'empreinte_auth'] as $cle) { unset($_SESSION[$cle]); }
    $client = $apercu['vue'] === 'client' ? clientApercu($pdo) : null;
    if ($client) {
        $_SESSION['user_id'] = (int) $client['id'];
        foreach (['pseudo', 'role', 'nom', 'prenom'] as $cle) { $_SESSION[$cle] = $client[$cle]; }
    }
    $_SESSION['last_activity'] = time();
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}
