<?php
if (!file_exists(__DIR__ . '/secrets.php')) {
    die("Configuration manquante : copie secrets.example.php en secrets.php et remplis-le.");
}
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/sessions_lib.php';

$modeProduction = modeProduction();
$enLigneDeCommande = PHP_SAPI === 'cli';

if ($modeProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dossierPrive('php_erreurs.log'));
    if (!$enLigneDeCommande) {
        header_remove('X-Powered-By');
    }
    if (!$enLigneDeCommande && !connexionHttps()) {
        $origineSite = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST);
        header('Location: ' . $origineSite . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit();
    }
}

demarrerSession();
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

$host = DB_HOST;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
if ($modeProduction) {
    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    if ($modeProduction) {
        error_log('Connexion à la base impossible : ' . $e->getMessage());
        http_response_code(503);
        die("Le site est momentanément indisponible. Merci de réessayer dans quelques minutes.");
    }
    die("Erreur de connexion : " . $e->getMessage());
}

if (!$enLigneDeCommande) {
    $fermetureGlobaleSessions = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'en_ligne.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'clients_auteurs';
    $actualiserSession = !$fermetureGlobaleSessions && !in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), ['logs_donnees.php', 'message_nouveaux.php'], true);
    controlerSessionUtilisateur($pdo, $actualiserSession);

    if (isset($_SESSION['user_id'])) {
        header('Cache-Control: no-store');
        if ($actualiserSession) {
            $stmt = $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
    }
}

function insertLog($type, $message) {
    $logFile = dossierPrive('logs.txt');
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'ANONYME';
    $pseudo = isset($_SESSION['pseudo']) ? $_SESSION['pseudo'] : 'Invité';
    
    if ($userId !== 'ANONYME') {
        $userString = $userId . ' (' . $pseudo . ')';
    } else {
        $userString = 'ANONYME';
    }
    
    $logLine = '[' . $date . '] [' . $type . '] [IP: ' . $ip . '] [USER_ID: ' . $userString . '] - ' . $message . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

function urlImage(?string $fichier, string $racine = ''): string
{
    if ($fichier && preg_match('~\Ahttps://covers\.openlibrary\.org/b/id/[1-9][0-9]*-[SML]\.jpg\z~', $fichier)) {
        return $fichier;
    }
    $chemin = $fichier && is_file(__DIR__ . '/img/' . $fichier) ? $fichier : 'default.png';
    return $racine . 'img/' . implode('/', array_map('rawurlencode', explode('/', $chemin)));
}

function urlStatique(string $chemin, string $racine = ''): string
{
    $fichier = __DIR__ . '/' . $chemin;
    return $racine . $chemin . (is_file($fichier) ? '?v=' . filemtime($fichier) : '');
}

function formaterPrix(float|int|string|null $montant): string
{
    return number_format((float) $montant, 2, ',', "\u{00A0}") . "\u{00A0}€";
}

$cheminScript = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: '');
$racineSite = str_replace('\\', '/', __DIR__);
$currentPage = strpos($cheminScript, $racineSite . '/') === 0
    ? substr($cheminScript, strlen($racineSite) + 1)
    : basename($_SERVER['PHP_SELF']);
if (!$enLigneDeCommande && !str_starts_with($currentPage, 'admin/')) {
    require_once __DIR__ . '/panier_minute_lib.php';
    synchroniserPanierMinute($pdo);
}
$ignoredPages = ['admin/logs_donnees.php', 'recherche.php',
                 'get_captcha_image.php', 'captcha_piece.php', 'captcha_verifier.php',
                 'admin/puzzle_image.php', 'admin/puzzle_piece.php', 'admin/puzzle_verifier.php'];

if (!$enLigneDeCommande && !in_array($currentPage, $ignoredPages) && strpos($currentPage, 'traitement_') === false) {
    insertLog('VISITE', "Consultation de la page : " . $currentPage);
}
?>
