<?php
require_once 'config.php';
include 'header.php';

$erreur = "";
$succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $motivations = trim($_POST['motivations'] ?? '');

    if (!$prenom || !$nom || !$pseudo || !$email || !$mdp || !$motivations) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);

        if ($stmt->fetch()) {
            $erreur = "Ce pseudo ou cet email est déjà utilisé.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_token, email_verifie) VALUES (?, ?, ?, ?, ?, 'client', 'en_attente_moderateur', ?, 0)");
            
            if ($stmt->execute([$pseudo, $email, $hash, $nom, $prenom, $token])) {
                insertLog('CANDIDATURE', "Nouvelle demande de modération de @" . $pseudo);

                $destinataire = $email;
                $sujet = "BIBLOccaz - Validation de votre email de candidature";

                $headers = "From:biblioccaz.noreply@gmail.com\r\n";
                $headers .= "Reply-To: biblioccaz.noreply@gmail.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                $url = "http://91.134.143.156/verification_mail.php?token=" . $token . "&email=" . urlencode($email);

                $message = "Bonjour $prenom !\n\n";
                $message .= "Merci pour l'intérêt que vous portez à BIBLIOccaz. Pour valider votre candidature et confirmer votre adresse email, veuillez cliquer sur le lien ci-dessous :\n";
                $message .= $url . "\n\n";
                $message .= "Une fois votre email validé, les administrateurs recevront votre fiche de motivation.\n";
                $message .= "---------------\n";
                $message .= "Ceci est un mail automatique, merci de ne pas y répondre.";

                if (mail($destinataire, $sujet, $message, $headers)){
                    $succes = "Un mail de validation a été envoyé à l'adresse " . htmlspecialchars($email) . ". Veuillez cliquer sur le lien pour confirmer votre candidature.";
                } else {
                    $erreur = "Le compte est créé mais le mail de confirmation n'est pas parti.";
                }
            } else {
                $erreur = "Une erreur technique est survenue.";
            }
        }
    }
}
?>

<div class="container my-5" style="max-width: 600px;">
    <div class="card shadow border-0" style="border-radius: 15px;">
        <div class="card-body p-4">
            <h2 class="fw-bold text-center mb-2"><i class="bi bi-shield-plus text-warning"></i> Rejoindre l'équipe</h2>
            <p class="text-muted text-center small mb-4">Remplissez cette fiche pour soumettre votre candidature de gestionnaire au catalogue.</p>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            
            <?php if ($succes): ?>
                <div class="alert alert-success py-3 text-center mb-0" style="border-radius: 10px;">
                    <i class="bi bi-envelope-check-fill fs-3 d-block mb-2"></i>
                    <?= htmlspecialchars($succes) ?>
                    <a href="login.php" class="btn btn-success btn-sm d-block mt-3 rounded-pill fw-bold">Aller à la connexion</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Prénom *</label>
                            <input type="text" name="prenom" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Nom *</label>
                            <input type="text" name="nom" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Pseudo choisi *</label>
                            <input type="text" name="pseudo" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Adresse Email *</label>
                            <input type="email" name="email" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Mot de passe *</label>
                            <input type="password" name="mot_de_passe" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Vos motivations (Pourquoi vous ?) *</label>
                            <textarea name="motivations" class="form-control form-control-sm" rows="4" placeholder="Expliquez brièvement pourquoi vous souhaitez devenir modérateur..." required></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">ENVOYER MA CANDIDATURE</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>