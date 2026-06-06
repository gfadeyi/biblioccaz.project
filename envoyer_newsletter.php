<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email_newsletter'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Adresse email invalide.'); window.location.href='index.php';</script>";
        exit();
    }



    $apiKey = ''; // ⚠️ Laisse vide ici pour Git / Mets ta clé sur le VPS
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
        $response = curl_exec($ch);
        curl_close($ch);
        

 
        $dataEmail = [
            'sender' => [
                'name' => 'BIBLIOccaz', 
                'email' => 'biblioccaz.noreply@gmail.com' // 🟢 Ton adresse d'envoi
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
        $responseMail = curl_exec($chMail);
        curl_close($chMail);
    }

 
    echo "<script>alert('Inscription réussie ! Vous avez été ajouté à notre liste de diffusion.'); window.location.href='index.php';</script>";
    exit();
        
} else {
    header('Location: index.php');
    exit();
}
?>