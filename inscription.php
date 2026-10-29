<?php
require_once 'mailer.php';
require_once 'config.php';

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$saisie = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $saisie = ['prenom' => $prenom, 'nom' => $nom, 'pseudo' => $pseudo, 'email' => $email];

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

            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, email_token, email_verifie) VALUES (?, ?, ?, ?, ?, 'client', ?, 0)");

            if ($stmt->execute([$pseudo, $email, $hash, $nom, $prenom, $token])) {
                $url = SITE_URL . "/verification_mail.php?token=" . $token;
                $mail = creerMailer();
                try {
                    $mail->addAddress($email, $prenom . ' ' . $nom);
                    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
                    habillerMail($mail, "Confirmez votre adresse email",
                        "Bienvenue $prenom !\n\nMerci de rejoindre BIBLIOccaz. Pour activer votre compte, confirmez votre adresse email avec le bouton ci-dessous.",
                        $url, "Confirmer mon adresse");
                    $mail->send();
                    header('Location: login.php?inscription=1');
                    exit();
                } catch (Exception $e) {
                    insertLog('ERREUR', "Mail de validation non envoyé : " . $mail->ErrorInfo);
                    $erreur = "Le compte est créé mais le mail de validation n'a pas pu partir. Réessayez plus tard ou contactez-nous.";
                }
            }
        }
    }
}

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--large">
        <div class="carte-formulaire__icone"><?= iconeDessin('lecteur') ?></div>
        <h1>Créer un compte</h1>
        <p class="carte-formulaire__intro">Achetez, donnez et retrouvez la communauté des lecteurs BIBLIOccaz.</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" autocomplete="given-name" value="<?= htmlspecialchars($saisie['prenom']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom *</label>
                    <input type="text" id="nom" name="nom" class="form-control" autocomplete="family-name" value="<?= htmlspecialchars($saisie['nom']) ?>" required>
                </div>
                <div class="col-12">
                    <label for="pseudo" class="form-label">Pseudo *</label>
                    <input type="text" id="pseudo" name="pseudo" class="form-control" autocomplete="nickname" value="<?= htmlspecialchars($saisie['pseudo']) ?>" required>
                </div>
                <div class="col-12">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?= htmlspecialchars($saisie['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="mot-de-passe" class="form-label">Mot de passe *</label>
                    <input type="password" id="mot-de-passe" name="mot_de_passe" class="form-control" autocomplete="new-password" required>
                </div>
                <div class="col-md-6">
                    <label for="confirmation" class="form-label">Confirmation *</label>
                    <input type="password" id="confirmation" name="confirmation" class="form-control" autocomplete="new-password" required>
                </div>

                <div class="col-12">
                    <div class="carte-formulaire__encart">
                        <p>Vous êtes auteur ? Créez votre compte avec votre candidature : votre page d'auteur sera publiée après validation par l'équipe.</p>
                        <a href="candidature_auteur.php" class="bouton bouton-contour bouton-petit"><?= iconeDessin('plume') ?> Créer un compte auteur</a>
                    </div>
                </div>

                <div class="col-12">
                    <div class="carte-formulaire__encart">
                        <p>Vous souhaitez aider à faire grandir la communauté ?</p>
                        <a href="postuler_moderateur.php" class="bouton bouton-contour bouton-petit"><i class="bi bi-shield-plus" aria-hidden="true"></i> Rejoindre l'équipe de modération</a>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="bouton bouton-principal">Créer mon compte</button>
                </div>
            </div>
        </form>
        <p class="carte-formulaire__pied">Déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</div>

<?php include 'footer.php'; ?>
