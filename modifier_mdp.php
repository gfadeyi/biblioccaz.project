<?php
require_once 'config.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

const MDP_LONGUEUR_MIN = 8;

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$erreur = '';
$user = null;
$mode = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT id, prenom FROM user WHERE reset_token_hash = ? AND reset_expire > NOW()");
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();
    $mode = 'reset';
} elseif (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, prenom, mot_de_passe FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $mode = 'connecte';
} else {
    header("Location: recuparation_mdp.php");
    exit();
}

$lienInvalide = ($mode === 'reset' && !$user);

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (!verifierCsrf()) {
        $erreur = "Session expirée, merci de réessayer.";
    } elseif ($mode === 'connecte' && !password_verify($_POST['mdp_actuel'] ?? '', $user['mot_de_passe'])) {
        $erreur = "Votre mot de passe actuel est incorrect.";
    } elseif (mb_strlen($nouveau) < MDP_LONGUEUR_MIN) {
        $erreur = "Le nouveau mot de passe doit contenir au moins " . MDP_LONGUEUR_MIN . " caractères.";
    } elseif ($nouveau !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("UPDATE user SET mot_de_passe = ?, reset_token_hash = NULL, reset_expire = NULL WHERE id = ?");
        $stmt->execute([password_hash($nouveau, PASSWORD_DEFAULT), $user['id']]);

        if ($mode === 'reset') {
            insertLog('SECURITE', "Mot de passe réinitialisé via le lien de récupération (user #{$user['id']})");
            header("Location: login.php?mdp=1");
        } else {
            insertLog('SECURITE', "Mot de passe modifié depuis le profil");
            header("Location: profil.php?mdp=1");
        }
        exit();
    }
}

include 'header.php';
?>

<main class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
        <h1><?= $mode === 'reset' ? 'Nouveau mot de passe' : 'Changer mon mot de passe' ?></h1>

        <?php if ($lienInvalide): ?>
            <p class="carte-formulaire__intro">Ce lien n'est plus valide : il a déjà été utilisé ou il a expiré (validité : 1 heure).</p>
            <a href="recuparation_mdp.php" class="bouton bouton-principal">Demander un nouveau lien</a>
        <?php else: ?>
            <p class="carte-formulaire__intro">
                <?= $mode === 'reset' ? 'Bonjour ' . htmlspecialchars($user['prenom'] ?? '') . ', choisissez votre nouveau mot de passe.' : 'Pour votre sécurité, confirmez d\'abord votre mot de passe actuel.' ?>
            </p>

            <?php if ($erreur): ?>
                <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                <?php if ($mode === 'reset'): ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <?php else: ?>
                    <div class="mb-3">
                        <label for="mdp-actuel" class="form-label">Mot de passe actuel *</label>
                        <input type="password" id="mdp-actuel" name="mdp_actuel" class="form-control" required autocomplete="current-password">
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nouveau-mdp" class="form-label">Nouveau mot de passe *</label>
                    <input type="password" id="nouveau-mdp" name="nouveau_mdp" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password" aria-describedby="aide-mdp">
                    <div class="form-text" id="aide-mdp">Au moins <?= MDP_LONGUEUR_MIN ?> caractères.</div>
                </div>
                <div class="mb-4">
                    <label for="confirmation-mdp" class="form-label">Confirmer le nouveau mot de passe *</label>
                    <input type="password" id="confirmation-mdp" name="confirmation" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
                </div>
                <button type="submit" class="bouton bouton-principal">Enregistrer</button>
            </form>
        <?php endif; ?>

        <p class="carte-formulaire__pied">
            <a href="<?= $mode === 'connecte' ? 'profil.php' : 'login.php' ?>"><?= $mode === 'connecte' ? 'Retour à mon compte' : 'Retour à la connexion' ?></a>
        </p>
    </div>
</main>

<?php include 'footer.php'; ?>
