<?php
require_once 'mailer.php';
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'anti_abus_lib.php';
require_once 'validation_email_lib.php';
require_once 'mot_de_passe_lib.php';
require_once 'captcha_lib.php';
$validationDisponible = validationEmailDisponible($pdo);

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$saisie = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => ''];
$emailPropose = ($_GET['depuis'] ?? '') === 'connexion' && is_string($_SESSION['email_inscription'] ?? null) ? $_SESSION['email_inscription'] : '';
unset($_SESSION['email_inscription']);
if ($emailPropose !== '' && mb_strlen($emailPropose) <= 150 && filter_var($emailPropose, FILTER_VALIDATE_EMAIL)) { $saisie['email'] = $emailPropose; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = (is_string($_POST['pseudo'] ?? null) ? trim($_POST['pseudo']) : '');
    $email = (is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '');
    $mdp = (is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '');
    $confirmation = (is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '');
    $nom = (is_string($_POST['nom'] ?? null) ? trim($_POST['nom']) : '');
    $prenom = (is_string($_POST['prenom'] ?? null) ? trim($_POST['prenom']) : '');
    $saisie = ['prenom' => $prenom, 'nom' => $nom, 'pseudo' => $pseudo, 'email' => $email];

    if (!$validationDisponible) {
        http_response_code(503);
        $erreur = 'La création de compte est momentanément indisponible.';
    } elseif (!verifierCsrf()) {
        http_response_code(403);
        $erreur = 'La session du formulaire a expiré. Rechargez la page.';
    } elseif (!captchaRecent()) {
        $erreur = CAPTCHA_MESSAGE_REQUIS;
    } elseif (!autoriserActionPublique('inscription', 10, 3600)) {
        http_response_code(429);
        $erreur = 'Trop de demandes. Merci de réessayer plus tard.';
    } elseif (!$pseudo || !$email || !$mdp || !$nom || !$prenom) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $erreur = 'Indiquez une adresse email valide (150 caractères maximum).';
    } elseif (!preg_match('/\A[\p{L}\p{N}._-]{3,50}\z/u', $pseudo) || mb_strlen($nom) > 100 || mb_strlen($prenom) > 100) {
        $erreur = 'Le pseudo doit contenir 3 à 50 lettres, chiffres, points, tirets ou traits de soulignement. Nom et prénom : 100 caractères maximum.';
    } elseif ($erreurMotDePasse = erreurMotDePasse($mdp)) {
        $erreur = $erreurMotDePasse;
    } elseif ($mdp !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);

        if ($stmt->fetch()) {
            $erreur = "Ce pseudo ou cet email est déjà utilisé.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);


            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, email_verifie) VALUES (?, ?, ?, ?, ?, 'client', 0)");

            if ($stmt->execute([$pseudo, $email, $hash, $nom, $prenom])) {
                $token = demanderValidationEmail($pdo, $email);
                if ($token === null) { throw new RuntimeException('Validation temporairement indisponible.'); }
                $url = SITE_URL . "/verification_mail?token=" . $token;
                $mail = creerMailer();
                try {
                    $mail->addAddress($email, $prenom . ' ' . $nom);
                    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
                    habillerMail($mail, "Confirmez votre adresse email",
                        "Bienvenue $prenom !\n\nMerci de rejoindre BIBLIOccaz. Pour activer votre compte, confirmez votre adresse email avec le bouton ci-dessous. Ce lien est valable 24 heures. Ouvrir le lien ne valide rien : confirmez ensuite sur la page.",
                        $url, "Confirmer mon adresse");
                    $mail->send();
                    header('Location: connexion?inscription=1');
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

        <?php if (!$validationDisponible): ?><p class="alert alert-info" role="status">La création de compte est momentanément indisponible.</p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
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
                    <?= champMotDePasse('mot-de-passe', 'mot_de_passe', 'Mot de passe *', 'new-password', true) ?>
                </div>
                <div class="col-md-6">
                    <?= champMotDePasse('confirmation', 'confirmation', 'Confirmation *', 'new-password') ?>
                </div>

                <div class="col-12">
                    <div class="carte-formulaire__encart">
                        <p>Vous êtes auteur ? Créez votre compte avec votre candidature : votre page d'auteur sera publiée après validation par l'équipe.</p>
                        <a href="candidature_auteur" class="bouton bouton-contour bouton-petit"><?= iconeDessin('plume') ?> Créer un compte auteur</a>
                    </div>
                </div>

                <div class="col-12">
                    <div class="carte-formulaire__encart">
                        <p>Vous souhaitez aider à faire grandir la communauté ?</p>
                        <a href="rejoindre_equipe" class="bouton bouton-contour bouton-petit"><?= iconeDessin('bulle') ?> Rejoindre l'équipe</a>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <?php include 'partie_captcha.php'; ?>
                    <button type="submit" class="bouton bouton-principal">Créer mon compte</button>
                    <p class="small text-muted mt-3 mb-0">Vos données servent à gérer votre compte et vos échanges sur BIBLIOccaz. Pour en savoir plus et exercer vos droits, consultez la <a href="confidentialite">politique de confidentialité</a>.</p>
                </div>
            </div>
        </form>
        <p><a href="renvoyer_validation">Recevoir un nouveau lien de validation</a></p>
        <?php include 'partie_bouton_google.php'; ?>
        <p class="carte-formulaire__pied">Déjà un compte ? <a href="connexion">Se connecter</a></p>
    </div>
</div>

<?php include 'footer.php'; ?>
