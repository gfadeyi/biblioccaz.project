<?php
require 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
  
    $stmt = $pdo->query("SELECT email, pseudo FROM user WHERE is_newsletter = 1");
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subscribers)) {
        echo "<script>alert('Aucun abonné actif trouvé pour cette campagne.'); window.location.href='diffusion_admin.php';</script>";
        exit();
    }

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2;

    try {
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'biblioccaz.noreply@gmail.com'; 
        $mail->Password   = 'awwjqhrexgeuqpns';        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

    
        $mail->setFrom('biblioccaz.noreply@gmail.com', 'BIBLIOccaz');
        $mail->Subject = "Des nouvelles fraîches de BIBLIOccaz ! 📚";

        
        foreach ($subscribers as $s) {
            $mail->addBCC($s['email']);
        }

        $mail->addAddress('biblioccaz.noreply@gmail.com', 'Abonnés BIBLIOccaz');

        $mail->isHTML(true); 
        
        $body = "<h2>Bonjour cher lecteur !</h2>";
        $body .= "<p>Voici les dernières actualités de votre plateforme de livres d'occasion préférée <strong>BIBLIOccaz</strong>.</p>";
        $body .= "<p>De nouveaux ouvrages viennent d'être ajoutés au catalogue par nos auteurs et notre communauté. N'attendez plus pour venir les découvrir !</p>";
        $body .= "<br><hr>";
        $body .= "<p style='font-size: 0.8rem; color: gray;'>Vous recevez ce message car vous êtes inscrit à notre newsletter. Si vous souhaitez vous désabonner, rendez-vous sur votre espace profil.</p>";

        $mail->Body = $body;
        
        
        $mail->send();

       
        $count = count($subscribers);
        $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, date_action) VALUES ('Envoi Newsletter', :details, NOW())");
        $log_stmt->execute(['details' => "Campagne globale envoyée avec succès à $count abonnés."]);

        echo "<script>alert('La newsletter a été envoyée avec succès à vos $count abonnés !'); window.location.href='diffusion_admin.php';</script>";
        exit();

    } catch (Exception $e) {
        echo "<script>alert('Erreur lors de la campagne d'envoi : {$mail->ErrorInfo}'); window.location.href='diffusion_admin.php';</script>";
        exit();
    }
} else {
    header("Location: diffusion_admin.php");
    exit();
}