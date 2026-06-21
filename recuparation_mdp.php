<?php
require 'vendor/autoload.php';
require_once 'config.php';
include 'header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$erreur = "";
$succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $erreur = "Veuillez entrer votre adresse email.";
    } else {
        $stmt = $pdo->prepare("SELECT id, prenom FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $erreur = "Si cet email correspond à un compte, un mail vous a été envoyé.";
        } else {
            $token = bin2hex(random_bytes(32));
            

            $stmt = $pdo->prepare("UPDATE user SET email_token = ? WHERE email = ?");
            $stmt->execute([$token, $email]);

            $url = "https://biblioccaz.fr/modifier_mdp.php?token=" . $token;
            
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
                $mail->addAddress($email, $user['prenom']);

                $mail->isHTML(false); 
                $mail->Subject = "BIBLOccaz - Récupération de votre mot de passe";
                
                $message = "Bonjour " . $user['prenom'] . ",\n\n";
                $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur BIBLIOccaz.\n";
                $message .= "Pour changer votre mot de passe, veuillez cliquer sur le lien ci-dessous :\n";
                $message .= $url . "\n\n";
                $message .= "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce mail.\n\n";
                $message .= "---------------\n";
                $message .= "Ceci est un mail automatique, merci de ne pas y répondre.";
                
                $mail->Body = $message;

                $mail->send();
                $succes = "Un lien de récupération a été envoyé à l'adresse email indiquée.";

            } catch (Exception $e) {
                $erreur = "L'envoi du mail a échoué. Erreur technique : {$mail->ErrorInfo}";
            }
        }
    }
}
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow" style="border: 2px solid #b6d7a8; border-radius: 15px;">
        <div class="card-body p-4">
            <h2 class="fw-bold text-bibli-green text-center mb-4">
                <i class="bi bi-key"></i> Mot de passe oublié
            </h2>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            
            <?php if ($succes): ?>
                <div class="alert alert-success py-2 small"><?= htmlspecialchars($succes) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Saisissez votre adresse email *</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
                </div>
                <button type="submit" class="btn btn-bibli w-100 py-2">RECEVOIR LE LIEN</button>
            </form>
            
            <hr style="color: #93c47d;" class="my-3">
            <p class="text-center mb-0" style="font-size: 0.9rem;">
                <a href="login.php" style="color: #274e13; font-weight: bold;">Retour à la connexion</a>
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>