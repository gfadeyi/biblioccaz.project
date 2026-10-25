<?php
if (!file_exists(__DIR__ . '/secrets.php')) {
    die("Configuration manquante : copie secrets.example.php en secrets.php et remplis-le.");
}
require_once __DIR__ . '/secrets.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inactivity_limit = 600; 

if (isset($_SESSION['last_activity'])) {
    $duration = time() - $_SESSION['last_activity'];
    if ($duration > $inactivity_limit) {

    if (isset($_SESSION['user_id'])) {
            try {
                $pdo_auto = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                
                $stmt_u = $pdo_auto->prepare("SELECT last_activity FROM user WHERE id = ?");
                $stmt_u->execute([$_SESSION['user_id']]);
                $u_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
                $date_exacte_deco = $u_data['last_activity'] ?? date('Y-m-d H:i:s');

                $stmt_log = $pdo_auto->prepare("INSERT INTO logs (action_type, description, date_action, adresse_ip, id_user) VALUES ('LOGOUT', 'Déconnexion automatique (inactivité 300s)', ?, ?, ?)");
                $stmt_log->execute([$date_exacte_deco, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SESSION['user_id']]);
            } catch (\PDOException $e) {

            }
        }

        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}
$_SESSION['last_activity'] = time();

$isLocal = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');
$timeout_duration = 360;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=inactive");
    exit();
}
$_SESSION['last_activity'] = time();

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

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
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

$cheminScript = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: '');
$racineSite = str_replace('\\', '/', __DIR__);
$currentPage = strpos($cheminScript, $racineSite . '/') === 0
    ? substr($cheminScript, strlen($racineSite) + 1)
    : basename($_SERVER['PHP_SELF']);
$ignoredPages = ['admin/logs_donnees.php', 'admin/en_ligne_donnees.php', 'recherche.php',
                 'get_captcha_image.php', 'captcha_piece.php', 'captcha_verifier.php'];

if (!in_array($currentPage, $ignoredPages) && strpos($currentPage, 'traitement_') === false) {
    insertLog('VISITE', "Consultation de la page : " . $currentPage);
}
?>