<?php
require_once 'mailer.php';
require_once 'config.php';

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$succes = "";
$saisie = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => '', 'motivations' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $motivations = trim($_POST['motivations'] ?? '');
    $saisie = ['prenom' => $prenom, 'nom' => $nom, 'pseudo' => $pseudo, 'email' => $email, 'motivations' => $motivations];

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
                $idCandidat = (int) $pdo->lastInsertId();
                try {
                    $pdo->prepare("INSERT INTO candidature_moderateur (id_user, motivations) VALUES (?, ?)")->execute([$idCandidat, mb_substr($motivations, 0, 5000)]);
                } catch (PDOException $e) {
                    insertLog('ERREUR', "Motivations de @$pseudo non enregistrées : table candidature_moderateur absente");
                }
                insertLog('CANDIDATURE', "Nouvelle demande de modération de @" . $pseudo);
                $url = SITE_URL . "/verification_mail.php?token=" . $token . "&email=" . urlencode($email);
                $mail = creerMailer();
                try {
                    $mail->addAddress($email, $prenom . ' ' . $nom);
                    habillerMail($mail, "Confirmez votre candidature",
                        "Bonjour $prenom !\n\nMerci pour l'intérêt que vous portez à BIBLIOccaz. Pour valider votre candidature, confirmez votre adresse email avec le bouton ci-dessous.\n\nUne fois votre adresse confirmée, l'équipe étudiera votre candidature.",
                        $url, "Confirmer mon adresse");
                    $mail->send();
                    $succes = "Un mail de validation a été envoyé à l'adresse $email. Cliquez sur son lien pour confirmer votre candidature.";
                } catch (Exception $e) {
                    insertLog('ERREUR', "Mail de candidature non envoyé : " . $mail->ErrorInfo);
                    $erreur = "Le compte est créé mais le mail de confirmation n'a pas pu partir.";
                }
            } else {
                $erreur = "Une erreur technique est survenue.";
            }
        }
    }
}

include 'header.php';
?>

<main class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--large">
        <div class="carte-formulaire__icone"><i class="bi bi-shield-plus" aria-hidden="true"></i></div>
        <h1>Rejoindre l'équipe</h1>
        <p class="carte-formulaire__intro">Présentez-vous : l'équipe étudie chaque candidature à la modération.</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if ($succes): ?>
            <div class="carte-formulaire__encart" role="status">
                <p class="mb-3"><i class="bi bi-envelope-check" aria-hidden="true"></i> <?= htmlspecialchars($succes) ?></p>
                <a href="login.php" class="bouton bouton-principal bouton-petit">Aller à la connexion</a>
            </div>
        <?php else: ?>
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
                        <label for="pseudo" class="form-label">Pseudo choisi *</label>
                        <input type="text" id="pseudo" name="pseudo" class="form-control" autocomplete="nickname" value="<?= htmlspecialchars($saisie['pseudo']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">Adresse email *</label>
                        <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?= htmlspecialchars($saisie['email']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="mot-de-passe" class="form-label">Mot de passe *</label>
                        <input type="password" id="mot-de-passe" name="mot_de_passe" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <label for="motivations" class="form-label">Vos motivations *</label>
                        <textarea id="motivations" name="motivations" class="form-control" rows="4" maxlength="5000" placeholder="Expliquez brièvement pourquoi vous souhaitez devenir modérateur…" required><?= htmlspecialchars($saisie['motivations']) ?></textarea>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="bouton bouton-principal">Envoyer ma candidature</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <p class="carte-formulaire__pied">Vous cherchez plutôt un compte lecteur ? <a href="inscription.php">Créer un compte</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>
