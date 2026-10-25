<?php
require_once 'mailer.php';
require_once 'config.php';

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$succes = "";

// Même message que l'adresse existe ou non : on ne révèle pas quels emails ont un compte
const MESSAGE_ENVOI = "Si cette adresse correspond à un compte, un lien de récupération vient d'être envoyé. Il est valable 1 heure.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez entrer une adresse email valide.";
    } else {
        $stmt = $pdo->prepare("SELECT id, prenom FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Le jeton part dans le mail ; en base on ne garde que son empreinte SHA-256
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE user SET reset_token_hash = ?, reset_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([hash('sha256', $token), $user['id']]);

            $url = SITE_URL . "/modifier_mdp.php?token=" . $token;

            try {
                $mail = creerMailer();
                $mail->addAddress($email, $user['prenom']);
                $mail->isHTML(false);
                $mail->Subject = "BIBLIOccaz - Récupération de votre mot de passe";

                $message = "Bonjour " . $user['prenom'] . ",\n\n";
                $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur BIBLIOccaz.\n";
                $message .= "Pour choisir un nouveau mot de passe, cliquez sur le lien ci-dessous (valable 1 heure) :\n";
                $message .= $url . "\n\n";
                $message .= "Si vous n'êtes pas à l'origine de cette demande, ignorez ce mail : votre mot de passe actuel reste valable.\n\n";
                $message .= "---------------\n";
                $message .= "Ceci est un mail automatique, merci de ne pas y répondre.";

                $mail->Body = $message;
                $mail->send();
                insertLog('SECURITE', "Demande de réinitialisation de mot de passe (user #{$user['id']})");
            } catch (Exception $e) {
                insertLog('ERREUR', "Echec envoi mail de récupération : " . $e->getMessage());
            }
        }

        $succes = MESSAGE_ENVOI;
    }
}

include 'header.php';
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