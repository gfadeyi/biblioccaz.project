<?php
require 'vendor/autoload.php';
require_once 'config.php';
include 'header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    
    $role = 'client';
    if (isset($_POST['role']) && $_POST['role'] === 'auteur') {
        $role = 'auteur';
    }

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

            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, email_token, email_verifie) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            
            if ($stmt->execute([$pseudo, $email, $hash, $nom, $prenom, $role, $token])) {
                
        $url = "https://biblioccaz.fr/verification_mail.php?token=" . $token;

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
    $mail->addAddress($email, $prenom . ' ' . $nom);
    $mail->addReplyTo('biblioccaz.noreply@gmail.com', 'BIBLIOccaz');

  
    $mail->isHTML(false); 
    $mail->Subject = "BIBLOccaz - Votre compte est activé";
    
    $message = "Bienvenue $prenom !\n\n";
    $message .= "Merci de rejoindre BIBLIOccaz. Pour valider votre compte, veuillez cliquer sur le lien ci-dessous ou le copier/coller dans votre navigateur :\n";
    $message .= $url . "\n\n";
    $message .= "---------------\n";
    $message .= "Ceci est un mail automatique, merci de ne pas y répondre.";
    
    $mail->Body = $message;

    $mail->send();
    echo "<script>alert('Un mail de validation a été envoyé à $email'); window.location.href='login.php';</script>";
    exit();

} catch (Exception $e) {
    $erreur = "Le compte est créé mais le mail n'est pas parti. Erreur : {$mail->ErrorInfo}";
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
                    
                    <div class="col-12">
                        <label class="form-label fw-bold">Type de compte *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="roleClient" value="client" checked>
                            <label class="form-check-label" for="roleClient">
                                Client (Acheter / Vendre / Emprunter des livres)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="roleAuteur" value="auteur">
                            <label class="form-check-label" for="roleAuteur">
                                Auteur (Proposer mes propres œuvres au catalogue)
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-3 text-center">
                        <div class="p-3 rounded-3 bg-light border">
                            <p class="small text-muted mb-2">Vous souhaitez aider à faire grandir la communauté ?</p>
                            <a href="postuler_moderateur.php" class="btn btn-sm btn-outline-warning fw-bold text-dark rounded-pill px-3">
                                <i class="bi bi-shield-plus me-1"></i> Rejoindre l'équipe en tant que modérateur
                            </a>
                        </div>
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