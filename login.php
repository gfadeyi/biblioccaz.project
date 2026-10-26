<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0");
include 'header.php';

$error = "";
$success = "";

if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') { $error = "Identifiants incorrects."; }
    elseif ($_GET['error'] == 'captcha') { $error = "Veuillez compléter le puzzle."; }
    elseif ($_GET['error'] == 'not_verified') { $error = "Veuillez confirmer votre email avant de vous connecter."; }
    elseif ($_GET['error'] == 'en_attente_moderateur') { $error = "Votre demande d'inscription en tant que modérateur est en cours d'examen."; }
    elseif ($_GET['error'] == 'refuse_temporaire') { $error = "Votre candidature n'a pas été retenue pour l'instant."; }
    elseif ($_GET['error'] == 'refuse_definitif') { $error = "Votre demande a été rejetée définitivement."; }
}

if (isset($_GET['verif'])) { $success = "Compte validé ! Vous pouvez vous connecter."; }
if (isset($_GET['mdp'])) { $success = "Mot de passe modifié ! Vous pouvez vous connecter avec le nouveau."; }
if (isset($_GET['inscription'])) { $success = "Compte créé ! Un mail de validation vient de vous être envoyé : cliquez sur son lien avant de vous connecter."; }
?>

<main class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><?= iconeDessin('lecteur') ?></div>
        <h1>Se connecter</h1>
        <p class="carte-formulaire__intro">Retrouvez vos favoris, vos points et vos commandes.</p>

        <?php if (isset($_GET['timeout']) || (isset($_GET['msg']) && $_GET['msg'] === 'inactive')): ?>
            <div class="alert alert-info small" role="status"><strong>Déconnexion automatique</strong> après 10 minutes d'inactivité.</div>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger small" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success small" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <div class="mb-3">
                <label for="identifiant" class="form-label">Email ou pseudo</label>
                <input type="text" id="identifiant" name="pseudo" class="form-control" autocomplete="username" required>
            </div>
            <div class="mb-2">
                <label for="mot-de-passe" class="form-label">Mot de passe</label>
                <input type="password" id="mot-de-passe" name="mdp" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="text-end mb-4">
                <a href="recuparation_mdp.php" class="carte-formulaire__lien-discret">Mot de passe oublié ?</a>
            </div>

            <div class="mb-4">
                <span class="form-label d-block">Vérification de sécurité</span>
                <button type="button" class="bouton-captcha" data-bs-toggle="modal" data-bs-target="#captchaModal" id="captchaTriggerBtn">
                    <i class="bi bi-puzzle" aria-hidden="true"></i> Cliquez pour vérifier
                </button>
                <input type="hidden" name="captcha_token" id="captchaToken">
            </div>

            <button type="submit" class="bouton bouton-principal">Se connecter</button>
        </form>
        <p class="carte-formulaire__pied">Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
    </div>
</main>

<div class="modal fade" id="captchaModal" tabindex="-1" aria-labelledby="titreCaptcha" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="titreCaptcha">Reconstituez l'image</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-muted small mb-3">Cliquez sur deux cases pour échanger leurs places.</p>
                <div id="captchaGrid"></div>
                <p id="captchaMessage" class="mt-3 small fw-bold mb-0" style="min-height: 20px;" aria-live="polite"></p>
            </div>
        </div>
    </div>
</div>

<script src="javascript/captcha_puzzle.js?v=<?= filemtime(__DIR__ . '/javascript/captcha_puzzle.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
initCaptchaPuzzle('');
});
</script>
<?php include 'footer.php'; ?>