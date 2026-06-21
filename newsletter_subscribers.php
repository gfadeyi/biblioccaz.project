<?php
require 'vendor/autoload.php';
require_once 'config.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

if (isset($_POST['email_newsletter']) && !empty($_POST['email_newsletter'])) {
    $email = filter_var($_POST['email_newsletter'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
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
            $mail->addAddress($email); 


            $mail->isHTML(false); 
            $mail->Subject = "BIBLOccaz - Inscription à la newsletter validée";
            
            $message = "Bonjour,\n\n";
            $message .= "Merci de vous être inscrit à la newsletter de BIBLIOccaz !\n";
            $message .= "Vous recevrez désormais nos dernières actualités et nos nouveaux catalogues de livres d'occasion directement dans votre boîte mail.\n\n";
            $message .= "À très bientôt sur notre plateforme !\n\n";
            $message .= "---------------\n";
            $message .= "Ceci est un mail automatique, merci de ne pas y répondre.";
            
            $mail->Body = $message;

            
            $mail->send();

        } catch (Exception $e) {

        }
        
        header('Location: index.php?newsletter=success');
        exit();

    } catch (PDOException $e) {
        die("Erreur lors de l'inscription : " . $e->getMessage());
    }
} else {
    header('Location: index.php');
    exit();
}
?>