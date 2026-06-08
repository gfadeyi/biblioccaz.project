<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    $email = trim($_POST['email_newsletter'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?newsletter=error_email');
        exit();
    }


    $apiKey = ''; 
    $idListe = 2; 

    if (!empty($apiKey)) {
        

        $dataContact = [
            'email' => $email,
            'listIds' => [$idListe],
            'updateEnabled' => true
        ];

        $ch = curl_init('https://api.brevo.com/v3/contacts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataContact)); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        

        $dataEmail = [
            'sender' => [
                'name' => 'BIBLIOccaz', 
                'email' => 'biblioccaz.noreply@gmail.com' 
            ],
            'to' => [
                ['email' => $email]
            ],
            'subject' => 'Bienvenue dans la newsletter BIBLIOccaz !',
            'htmlContent' => '<html><body>
                                <h2>Merci pour votre inscription !</h2>
                                <p>Vous recevrez désormais nos dernières pépites littéraires d\'occasion chaque mois.</p>
                              </body></html>'
        ];

        $chMail = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($chMail, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chMail, CURLOPT_POST, true);
        curl_setopt($chMail, CURLOPT_POSTFIELDS, json_encode($dataEmail)); 
        curl_setopt($chMail, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);
        curl_setopt($chMail, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chMail, CURLOPT_FOLLOWLOCATION, true);
        
        $responseMail = curl_exec($chMail);
        curl_close($chMail);
    }


    header('Location: index.php?newsletter=success');
    exit();
        
} else {
    header('Location: index.php');
    exit();
}
?>