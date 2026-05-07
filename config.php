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
    if ($isLocal) {
        try {
            $user_local = 'root';
            $pass_local = ''; 
            $pdo = new PDO($dsn, $user_local, $pass_local, $options);
        } catch (\PDOException $e2) {
            die("Erreur de connexion locale : " . $e2->getMessage());
        }
    } else {
        die("Erreur de connexion serveur : " . $e->getMessage());
    }
}

function insertLog($pdo, $type, $message) {
    try {
        $sql = "INSERT INTO logs (action_type, description, adresse_ip, id_user) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $type, 
            $message, 
            $_SERVER['REMOTE_ADDR'], 
            $_SESSION['user_id'] ?? null
        ]);
    } catch (Exception $e) {
    }
}

insertLog($pdo, 'VISITE', "Consultation de la page : " . $_SERVER['PHP_SELF']);
?>