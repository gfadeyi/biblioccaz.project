<?php
require 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email_newsletter'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?newsletter=error_email');
        exit();
    }

    try {
        $check = $pdo->prepare("SELECT id FROM user WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $req = $pdo->prepare("UPDATE user SET is_newsletter = 1, date_newsletter = NOW() WHERE email = ?");
            $req->execute([$email]);
        } else {
            $pseudo = explode('@', $email)[0]; 
            $password_fake = password_hash(uniqid(), PASSWORD_BCRYPT); 
            
            $req = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, role, is_newsletter, date_newsletter) VALUES (?, ?, ?, 'user', 1, NOW())");
            $req->execute([$pseudo, $email, $password_fake]);
        }

        
        $mail = new PHPMailer(true);

        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'biblioccaz.noreply@gmail.com';  
        $mail->Password   = 'awwjqhrexgeuqpns';        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        
        $mail->setFrom('biblioccaz.noreply@gmail.com', 'BIBLIOccaz');
        $mail->addAddress($email);

        
        $mail->isHTML(true); 
        $mail->Subject = 'Bienvenue dans la newsletter BIBLIOccaz !';
        
        $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #b6d7a8; border-radius: 10px;">
                <h2 style="color: #274e13;">Merci pour votre inscription !</h2>
                <p>Bonjour,</p>
                <p>Vous recevrez désormais nos dernières pépites littéraires d\'occasion chaque mois directement dans votre boîte mail.</p>
                <p>À très bientôt sur notre plateforme !</p>
                <br>
                <hr style="border: 0; border-top: 1px solid #93c47d;">
                <p style="font-size: 0.8rem; color: #777;">Ceci est un mail automatique, merci de ne pas y répondre.</p>
            </div>
        </body>
        </html>';

        $mail->send();

    } catch (Exception $e) {
    } catch (PDOException $e) {
        die("Erreur base de données : " . $e->getMessage());
    }

    header('Location: index.php?newsletter=success');
    exit();
        
} else {
    header('Location: index.php');
    exit();
}
?>