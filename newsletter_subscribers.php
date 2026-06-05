<?php
require_once 'config.php'; 


if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
    
    try {

        $check = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() == 0) {

            $req = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $req->execute([$email]);

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