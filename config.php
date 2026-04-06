<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLocal = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');

if ($isLocal) {
    $host = 'localhost';
    $db   = 'biblioccaz';
    $user = 'root'; 
    $pass = ''; 
} else {
    $host = 'localhost';
    $db   = 'biblioccaz';
    $user = 'admin_biblio'; 
    $pass = 'Esgi_2026_Biblio!'; 
}

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
?>