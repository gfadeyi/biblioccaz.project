<?php
require_once 'config.php';
require_once 'google_lib.php';
require_once 'avis_lib.php';

header('Cache-Control: no-store');

$liaison = $_SESSION['liaison_google'] ?? null;
if (!connexionGoogleActive() || isset($_SESSION['user_id']) || !is_array($liaison) || time() - (int) $liaison['cree_le'] > GOOGLE_DUREE_DEMANDE) {
    unset($_SESSION['liaison_google']);
    header('Location: login.php' . (is_array($liaison) ? '?error=google_expire' : ''));
    exit();
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'La page a expiré. Merci de réessayer.';
    } elseif (isset($_POST['annuler'])) {
        unset($_SESSION['liaison_google']);
        header('Location: login.php');
        exit();
    } else {
        $requete = $pdo->prepare('SELECT * FROM user WHERE id = ? AND email = ?');
        $requete->execute([$liaison['id_user'], $liaison['email']]);
        $user = $requete->fetch(PDO::FETCH_ASSOC);
        $_SESSION['liaison_google']['essais'] = ++$liaison['essais'];
        if ($user && is_string($_POST['mdp'] ?? null) && password_verify($_POST['mdp'], $user['mot_de_passe'])) {
            $refus = refusConnexionGoogle(array_merge($user, ['email_verifie' => 1]));
            $liaisonFaite = false;
            if ($refus === '') {
                $miseAJour = $pdo->prepare('UPDATE user SET google_sub = ?, email_verifie = 1 WHERE id = ? AND google_sub IS NULL');
                $miseAJour->execute([$liaison['sub'], $user['id']]);
                $liaisonFaite = $miseAJour->rowCount() === 1;
            }
            unset($_SESSION['liaison_google']);
            if (!$liaisonFaite) {
                header('Location: login.php?error=' . ($refus !== '' ? $refus : 'google_autre_compte'));
                exit();
            }
            insertLog('COMPTE', "Compte @{$user['pseudo']} lié à Google");
            $user['email_verifie'] = 1;
            header('Location: ' . ouvrirSessionMembre($pdo, $user, 'Connexion réussie avec Google'));
            exit();
        }
        if ($liaison['essais'] >= GOOGLE_ESSAIS_LIAISON) {
            unset($_SESSION['liaison_google']);
            insertLog('SECURITE', "Liaison Google abandonnée après " . GOOGLE_ESSAIS_LIAISON . " mots de passe incorrects pour le compte #{$liaison['id_user']}");
            header('Location: login.php?error=google_essais');
            exit();
        }
        $essaisRestants = GOOGLE_ESSAIS_LIAISON - $liaison['essais'];
        $erreur = "Mot de passe incorrect. Il vous reste $essaisRestants essai" . ($essaisRestants > 1 ? 's.' : '.');
    }
}

$titreDocument = 'Lier votre compte Google';
include 'header.php';
?>
<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><?= iconeDessin('lecteur') ?></div>
        <h1>Lier votre compte Google</h1>
        <p class="carte-formulaire__intro">Un compte BIBLIOccaz existe déjà avec l'adresse <strong><?= htmlspecialchars($liaison['email']) ?></strong>. Confirmez votre mot de passe BIBLIOccaz une seule fois : vous pourrez ensuite vous connecter avec Google.</p>
        <?php if ($erreur): ?><div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>">
            <div class="mb-4">
                <label for="mot-de-passe" class="form-label">Mot de passe BIBLIOccaz</label>
                <input type="password" id="mot-de-passe" name="mdp" class="form-control" autocomplete="current-password" required autofocus>
            </div>
            <button type="submit" class="bouton bouton-principal">Lier et me connecter</button>
        </form>
        <form method="POST" class="mt-2">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>">
            <button type="submit" name="annuler" value="1" class="bouton bouton-discret">Annuler</button>
        </form>
        <p class="carte-formulaire__pied"><a href="recuparation_mdp.php">Mot de passe oublié ?</a></p>
    </div>
</div>
<?php include 'footer.php'; ?>
