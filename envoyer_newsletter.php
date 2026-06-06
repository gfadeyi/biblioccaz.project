<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email_newsletter'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Adresse email invalide.'); window.location.href='index.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);

    if (
        $stmt->fetch()) {
        echo "<script>alert('Vous êtes déjà inscrit à notre newsletter !'); window.location.href='index.php';</script>";
        exit();
    }


    $insert = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    
    if ($insert->execute([$email])) {
        
        $apiKey = ''; 
        $idListe = 2; 
        $data = [
            'email' => $email,
            'listIds' => [$idListe],
            'updateEnabled' => true
        ];

        $ch = curl_init('https://api.brevo.com/v3/contacts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        curl_close($ch);


        echo "<script>alert('Inscription réussie ! Vous avez été ajouté à notre liste de diffusion.'); window.location.href='index.php';</script>";
        exit();
        
    } else {
        echo "<script>alert('Une erreur est survenue, veuillez réessayer.'); window.location.href='index.php';</script>";
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>