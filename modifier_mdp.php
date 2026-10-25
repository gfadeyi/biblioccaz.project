<?php
// Changement de mot de passe, dans deux cas :
//  1. Mot de passe oublié : on arrive avec ?token=... depuis le mail de récupération
//  2. Utilisateur connecté : on doit saisir son mot de passe actuel
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

const MDP_LONGUEUR_MIN = 8;

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$erreur = '';
$user = null;
$mode = null;

if ($token !== '') {
    // On ne stocke que l'empreinte (hash) du jeton en base, jamais le jeton lui-même
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

    if ($mode === 'connecte' && !password_verify($_POST['mdp_actuel'] ?? '', $user['mot_de_passe'])) {
        $erreur = "Votre mot de passe actuel est incorrect.";
    } elseif (mb_strlen($nouveau) < MDP_LONGUEUR_MIN) {
        $erreur = "Le nouveau mot de passe doit contenir au moins " . MDP_LONGUEUR_MIN . " caractères.";
    } elseif ($nouveau !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        // Nouveau mot de passe + le lien de récupération devient inutilisable
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

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow" style="border: 2px solid #b6d7a8; border-radius: 15px;">
        <div class="card-body p-4">
            <h2 class="fw-bold text-bibli-green text-center mb-4">
                <i class="bi bi-shield-lock"></i> <?= $mode === 'reset' ? 'Nouveau mot de passe' : 'Changer mon mot de passe' ?>
            </h2>

            <?php if ($lienInvalide): ?>
                <div class="alert alert-warning small">
                    Ce lien n'est plus valide : il a déjà été utilisé ou il a expiré (validité : 1 heure).
                </div>
                <a href="recuparation_mdp.php" class="btn btn-bibli w-100 py-2">DEMANDER UN NOUVEAU LIEN</a>
            <?php else: ?>

                <?php if ($erreur): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>

                <?php if ($mode === 'reset'): ?>
                    <p class="text-muted small">Bonjour <?= htmlspecialchars($user['prenom'] ?? '') ?>, choisissez votre nouveau mot de passe.</p>
                <?php endif; ?>

                <form method="POST">
                    <?php if ($mode === 'reset'): ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mot de passe actuel *</label>
                            <input type="password" name="mdp_actuel" class="form-control" required autocomplete="current-password">
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nouveau mot de passe *</label>
                        <input type="password" name="nouveau_mdp" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
                        <div class="form-text">Au moins <?= MDP_LONGUEUR_MIN ?> caractères.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirmer le nouveau mot de passe *</label>
                        <input type="password" name="confirmation" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-bibli w-100 py-2">ENREGISTRER</button>
                </form>
            <?php endif; ?>

            <hr style="color: #93c47d;" class="my-3">
            <p class="text-center mb-0" style="font-size: 0.9rem;">
                <a href="<?= $mode === 'connecte' ? 'profil.php' : 'login.php' ?>" style="color: #274e13; font-weight: bold;">
                    <?= $mode === 'connecte' ? 'Retour à mon compte' : 'Retour à la connexion' ?>
                </a>
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
