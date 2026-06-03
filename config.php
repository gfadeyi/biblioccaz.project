<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLocal = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');

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