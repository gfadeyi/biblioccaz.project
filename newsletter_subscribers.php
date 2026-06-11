<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; 

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


        $apiKey = '';
        $idListe = 2;

        if (!empty($apiKey)) {
            $url = 'https://api.brevo.com/v3/contacts';
            
            $data = [
                'email' => $email,
                'listIds' => [$idListe],
                'updateEnabled' => true
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
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