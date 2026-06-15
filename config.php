<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inactivity_limit = 360; 

if (isset($_SESSION['last_activity'])) {
    $duration = time() - $_SESSION['last_activity'];
    if ($duration > $inactivity_limit) {

    if (isset($_SESSION['user_id'])) {
            try {
                $pdo_auto = new PDO("mysql:host=localhost;dbname=biblioccaz;charset=utf8mb4", "admin_biblio", "Esgi_2026_Biblio!", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                
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

$host = 'localhost';
$db   = 'biblioccaz';
$user = 'admin_biblio'; 
$pass = 'Esgi_2026_Biblio!'; 

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

$currentPage = basename($_SERVER['PHP_SELF']);
$ignoredPages = ['get_stats_logs.php', 'action_admin.php', 'modifier_statut.php'];

if (!in_array($currentPage, $ignoredPages) && strpos($currentPage, 'traitement_') === false) {
    insertLog('VISITE', "Consultation de la page : " . $currentPage);
}
?>