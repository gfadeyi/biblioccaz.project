<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'config.php';
include 'header.php';

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');

    if (!$pseudo || !$email || !$mdp || !$nom || !$prenom) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif ($mdp !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);

        if ($stmt->fetch()) {
            $erreur = "Ce pseudo ou cet email est déjà utilisé.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, code_verification, est_verifie) VALUES (?, ?, ?, ?, ?, 'client', ?, 0)");
            
            if ($stmt->execute([$pseudo, $email, $hash, $nom, $prenom, $token])) {
                
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'biblioccaz.noreply@gmail.com'; 
                    $mail->Password   = 'ton code 16 caracteres ici'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('biblioccaz.noreply@gmail.com', 'BIBLIOccaz');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Activez votre compte BIBLIOccaz';
                    
                    $url = "http://91.134.143.156/verifier.php?token=" . $token;
                    $mail->Body = "<h1>Bienvenue $prenom !</h1><p>Merci de rejoindre BIBLIOccaz. Cliquez ci-dessous pour valider votre compte :</p><p><a href='$url' style='padding:10px 20px; background:#274e13; color:white; text-decoration:none; border-radius:5px;'>Valider mon compte</a></p>";

                    $mail->send();
                    echo "<script>alert('Un mail de validation a été envoyé à $email'); window.location.href='login.php';</script>";
                    exit();
                } catch (Exception $e) {
                    $erreur = "Le compte est créé mais le mail n'est pas parti. Erreur : " . $mail->ErrorInfo;
                }
            }
        }
    }
}
?>

<div class="container my-5" style="max-width: 550px;">
    <div class="card shadow" style="border: 2px solid #b6d7a8; border-radius: 15px;">
        <div class="card-body p-4">
            <h2 class="fw-bold text-bibli-green text-center mb-4">
                <i class="bi bi-person-plus"></i> Créer un compte
            </h2>
            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Prénom *</label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Pseudo *</label>
                        <input type="text" name="pseudo" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mot de passe *</label>
                        <input type="password" name="mot_de_passe" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Confirmation *</label>
                        <input type="password" name="confirmation" class="form-control" required>
                    </div>
                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-bibli w-100 py-2">S'INSCRIRE</button>
                    </div>
                </div>
            </form>
            <hr style="color: #93c47d;" class="my-3">
            <p class="text-center mb-0" style="font-size: 0.9rem;">
                Déjà un compte ?
                <a href="login.php" style="color: #274e13; font-weight: bold;">Se connecter</a>
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>