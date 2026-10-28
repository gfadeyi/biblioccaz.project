<?php
if (!file_exists(__DIR__ . '/secrets.php')) {
    die("Configuration manquante : copie secrets.example.php en secrets.php et remplis-le.");
}
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/sessions_lib.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$fermetureGlobaleSessions = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'en_ligne.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'clients_auteurs';
$actualiserSession = !$fermetureGlobaleSessions && !in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), ['en_ligne_donnees.php', 'logs_donnees.php'], true);
controlerSessionUtilisateur($pdo, $actualiserSession);

if (isset($_SESSION['user_id'])) {
    header('Cache-Control: no-store');
    if ($actualiserSession) {
        $stmt = $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}

function insertLog($type, $message) {
    $logFile = __DIR__ . '/secure_data/logs.txt';
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
    $chemin = $fichier && is_file(__DIR__ . '/img/' . $fichier) ? $fichier : 'default.png';
    return $racine . 'img/' . implode('/', array_map('rawurlencode', explode('/', $chemin)));
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
$ignoredPages = ['admin/logs_donnees.php', 'admin/en_ligne_donnees.php', 'recherche.php',
                 'get_captcha_image.php', 'captcha_piece.php', 'captcha_verifier.php',
                 'admin/puzzle_image.php', 'admin/puzzle_piece.php', 'admin/puzzle_verifier.php'];

if (!in_array($currentPage, $ignoredPages) && strpos($currentPage, 'traitement_') === false) {
    insertLog('VISITE', "Consultation de la page : " . $currentPage);
}
?>
