<?php
require_once 'mailer.php';
require_once 'config.php';

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$succes = "";

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
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE user SET reset_token_hash = ?, reset_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([hash('sha256', $token), $user['id']]);

            $url = SITE_URL . "/modifier_mdp.php?token=" . $token;

            try {
                $mail = creerMailer();
                $mail->addAddress($email, $user['prenom']);
                habillerMail($mail, "Récupération de votre mot de passe",
                    "Bonjour " . $user['prenom'] . ",\n\nVous avez demandé la réinitialisation de votre mot de passe sur BIBLIOccaz. Le lien ci-dessous est valable 1 heure.\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce mail : votre mot de passe actuel reste valable.",
                    $url, "Choisir un nouveau mot de passe");
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

<main class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-key" aria-hidden="true"></i></div>
        <h1>Mot de passe oublié</h1>
        <p class="carte-formulaire__intro">Indiquez votre adresse email : nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success small" role="status"><?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Adresse email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="exemple@mail.com" autocomplete="email" required>
            </div>
            <button type="submit" class="bouton bouton-principal">Recevoir le lien</button>
        </form>
        <p class="carte-formulaire__pied"><a href="login.php">Retour à la connexion</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>