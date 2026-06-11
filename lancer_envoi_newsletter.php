<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    $stmt = $pdo->query("SELECT email FROM newsletter_subscribers");
    $abonnes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($abonnes)) {
        

        $html = "<html><body><h2>Les nouveautés de BIBLIOccaz !</h2><p>Découvrez nos derniers livres d'occasion.</p></body></html>";

        $data = [
            'sender' => ['email' => 'biblioccaz.noreply@gmail.com', 'name' => 'BIBLIOccaz'],
            'to' => array_map(fn($email) => ['email' => $email], $abonnes),
            'subject' => 'Newsletter BIBLIOccaz',
            'htmlContent' => $html
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key:'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

 
        $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, date_action) VALUES ('Envoi de Masse', :details, NOW())");
        
        if ($error) {
            $log_stmt->execute(['details' => "Échec de l'envoi de la newsletter. Erreur : " . $error]);
        } else {
            $log_stmt->execute(['details' => "Newsletter envoyée avec succès à " . count($abonnes) . " abonnés."]);
        }
    }


    header('Location: diffusion_admin.php');
    exit();
}
?>