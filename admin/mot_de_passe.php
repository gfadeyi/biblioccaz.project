<?php
require_once __DIR__ . '/_init.php';
exigerDroit('equipe');

const MDP_LONGUEUR_MIN = 8;
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null)) { rediriger('mot_de_passe.php', 'danger', 'Requête invalide.'); }
    exigerPost('mot_de_passe.php');
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $actuel = (string) $stmt->fetchColumn();
    $nouveau = is_string($_POST['nouveau_mdp'] ?? null) ? $_POST['nouveau_mdp'] : '';
    $ancien = is_string($_POST['mdp_actuel'] ?? null) ? $_POST['mdp_actuel'] : '';

    if (!password_verify($ancien, $actuel)) {
        $erreur = "Votre mot de passe actuel est incorrect.";
    } elseif (mb_strlen($nouveau) < MDP_LONGUEUR_MIN) {
        $erreur = "Le nouveau mot de passe doit contenir au moins " . MDP_LONGUEUR_MIN . " caractères.";
    } elseif ($nouveau !== ($_POST['confirmation'] ?? '')) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } elseif (strlen($nouveau) > 72 || str_contains($nouveau, "\0")) {
        $erreur = 'Le nouveau mot de passe est trop long ou contient un caractère non autorisé.';
    } else {
        $nouveauHash = password_hash($nouveau, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE user SET mot_de_passe = ?, reset_token_hash = NULL, reset_expire = NULL WHERE id = ? AND mot_de_passe = ?");
        $stmt->execute([$nouveauHash, $_SESSION['user_id'], $actuel]);
        if ($stmt->rowCount() !== 1) { rediriger('mot_de_passe.php', 'danger', 'Le compte a changé. Merci de réessayer.'); }
        terminerSuiviSession($pdo);
        session_regenerate_id(true);
        try {
            enregistrerSessionUtilisateur($pdo, ['id' => $_SESSION['user_id'], 'mot_de_passe' => $nouveauHash]);
        } catch (RuntimeException $e) {
            refuserSession($pdo, 'Reconnexion nécessaire après modification simultanée du compte');
        }
        insertLog('SECURITE', "Mot de passe modifié depuis le back-office");
        rediriger('index.php', 'success', "Mot de passe modifié.");
    }
}

$titrePage = 'Changer mon mot de passe';
$pageActive = '';
include __DIR__ . '/_haut.php';
?>

<form method="POST" class="carte p-4" style="max-width: 460px;">
    <?= champCsrf() ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger small py-2"><?= e($erreur) ?></div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label small fw-bold" for="mdp_actuel">Mot de passe actuel</label>
        <input type="password" id="mdp_actuel" name="mdp_actuel" class="form-control" required autocomplete="current-password">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-bold" for="nouveau_mdp">Nouveau mot de passe</label>
        <input type="password" id="nouveau_mdp" name="nouveau_mdp" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
        <div class="form-text">Au moins <?= MDP_LONGUEUR_MIN ?> caractères.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-bold" for="confirmation">Confirmer le nouveau mot de passe</label>
        <input type="password" id="confirmation" name="confirmation" class="form-control" minlength="<?= MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
    </div>
    <button class="btn btn-vert px-4">Enregistrer</button>
</form>

<?php include __DIR__ . '/_bas.php'; ?>
