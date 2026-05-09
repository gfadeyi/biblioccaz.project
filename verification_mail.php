
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

$mail = new PHPMailer(true);

try {
   
    $mail->isSMTP();                                            
    $mail->Host       = 'smtp.gmail.com';                     
    $mail->SMTPAuth   = true;                                   
    $mail->Username   = 'votre-email@gmail.com';               
    $mail->Password   = 'votre-mot-de-passe-d-application';    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
    $mail->Port       = 587;                                    

  
    $mail->setFrom('votre-email@gmail.com', 'Nom de votre Site');
    $mail->addAddress($userEmail);    

    
    $token = bin2hex(random_bytes(32)); 
    $url = "https://votresite.com/verifier.php?token=" . $token;

    $mail->isHTML(true);                                  
    $mail->Subject = 'Confirmez votre inscription';
    $mail->Body    = "<h1>Bienvenue !</h1>
                      <p>Cliquez sur le lien ci-dessous pour valider votre compte :</p>
                      <a href='$url'>Confirmer mon email</a>";
    $mail->AltBody = "Veuillez copier ce lien pour confirmer votre email : " . $url;

    $mail->send();
    echo 'Le message de confirmation a été envoyé.';
} catch (Exception $e) {
    echo "Le message n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}";
}
