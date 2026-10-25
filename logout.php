<?php
require_once __DIR__ . '/secrets.php';
session_start();
if (isset($_SESSION['user_id'])) {
    $id_user = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $pdo_logout = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $stmt = $pdo_logout->prepare("INSERT INTO logs (action_type, description, date_action, adresse_ip, id_user) VALUES ('LOGOUT', 'Déconnexion volontaire (clic bouton)', NOW(), ?, ?)");
        $stmt->execute([$ip, $id_user]);
        
    } catch (\PDOException $e) {
      
    }
}
session_destroy();
$retour = (($_GET['depuis'] ?? '') === 'admin') ? 'admin/login.php?deconnecte=1' : 'index.php';
header("Location: $retour");
exit();