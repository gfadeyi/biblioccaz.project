<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'recuperation_mdp_lib.php';
require_once 'mot_de_passe_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$jetonSaisi = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($jetonSaisi) ? $jetonSaisi : '';
$erreur = '';
$user = null;
$mode = null;

if (isset($_POST['token']) || isset($_GET['token'])) {
    if (preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        $stmt = $pdo->prepare("SELECT id, prenom FROM user WHERE reset_token_hash = ? AND reset_expire > NOW()");
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch();
    }
    $mode = 'reset';
} elseif (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, prenom, mot_de_passe FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $mode = 'connecte';
} else {
    header("Location: recuparation_mdp");
    exit();
}

$lienInvalide = ($mode === 'reset' && !$user);

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = is_string($_POST['nouveau_mdp'] ?? null) ? $_POST['nouveau_mdp'] : '';
    $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';
    $motDePasseActuel = is_string($_POST['mdp_actuel'] ?? null) ? $_POST['mdp_actuel'] : '';

    if (!is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
        $erreur = "Session expirée, merci de réessayer.";
    } elseif ($mode === 'connecte' && !password_verify($motDePasseActuel, $user['mot_de_passe'])) {
        $erreur = "Votre mot de passe actuel est incorrect.";
    } elseif ($erreurMotDePasse = erreurMotDePasse($nouveau)) {
        $erreur = $erreurMotDePasse;
    } elseif ($nouveau !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        if ($mode === 'reset') {
            $modifie = consommerJetonRecuperation($pdo, (int) $user['id'], $token, $nouveau);
        } else {
            $nouveauHash = password_hash($nouveau, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET mot_de_passe = ?, reset_token_hash = NULL, reset_expire = NULL WHERE id = ? AND mot_de_passe = ?");
            $stmt->execute([$nouveauHash, $user['id'], $user['mot_de_passe']]);
            $modifie = $stmt->rowCount() === 1;
        }

        if (!$modifie) {
            $erreur = "Ce lien ou cette session n'est plus valide. Merci de recommencer.";
        } elseif ($mode === 'reset') {
            insertLog('SECURITE', "Mot de passe réinitialisé via le lien de récupération (user #{$user['id']})");
            terminerSuiviSession($pdo);
            $_SESSION = [];
            session_regenerate_id(true);
            header("Location: connexion?mdp=1");
            exit();
        } else {
            insertLog('SECURITE', "Mot de passe modifié depuis le profil");
            terminerSuiviSession($pdo);
            session_regenerate_id(true);
            try {
                enregistrerSessionUtilisateur($pdo, ['id' => $user['id'], 'mot_de_passe' => $nouveauHash]);
            } catch (RuntimeException $e) {
                refuserSession($pdo, 'Reconnexion nécessaire après modification simultanée du compte');
            }
            header("Location: mon-compte?mdp=1");
            exit();
        }
    }
}

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
        <h1><?= $mode === 'reset' ? 'Nouveau mot de passe' : 'Changer mon mot de passe' ?></h1>

        <?php if ($lienInvalide): ?>
            <p class="carte-formulaire__intro">Ce lien n'est plus valide : il a déjà été utilisé ou il a expiré (validité : 1 heure).</p>
            <a href="recuparation_mdp" class="bouton bouton-principal">Demander un nouveau lien</a>
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
                        <?= champMotDePasse('mdp-actuel', 'mdp_actuel', 'Mot de passe actuel *', 'current-password') ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <?= champMotDePasse('nouveau-mdp', 'nouveau_mdp', 'Nouveau mot de passe *', 'new-password', true) ?>
                </div>
                <div class="mb-4">
                    <?= champMotDePasse('confirmation-mdp', 'confirmation', 'Confirmer le nouveau mot de passe *', 'new-password') ?>
                </div>
                <button type="submit" class="bouton bouton-principal">Enregistrer</button>
            </form>
        <?php endif; ?>

        <p class="carte-formulaire__pied">
            <a href="<?= $mode === 'connecte' ? 'mon-compte' : 'connexion' ?>"><?= $mode === 'connecte' ? 'Retour à mon compte' : 'Retour à la connexion' ?></a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>
