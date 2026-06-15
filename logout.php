<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $id_user = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $pdo_logout = new PDO("mysql:host=localhost;dbname=biblioccaz;charset=utf8mb4", "admin_biblio", "Esgi_2026_Biblio!", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $stmt = $pdo_logout->prepare("INSERT INTO logs (action_type, description, date_action, adresse_ip, id_user) VALUES ('LOGOUT', 'Déconnexion volontaire (clic bouton)', NOW(), ?, ?)");
        $stmt->execute([$ip, $id_user]);
        
    } catch (\PDOException $e) {
      
    }
}
session_destroy();
header("Location: index.php");
exit();