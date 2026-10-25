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
?>

<style>
    .login-container { max-width: 450px; margin: 80px auto; }
    .input-recyclivre { border: none !important; border-bottom: 1px solid #ccc !important; padding: 15px 0 !important; background: transparent !important; text-align: center; }
    .btn-continue { background-color: #274e13; color: white; border-radius: 50px; padding: 12px 0; width: 100%; margin-top: 20px; border: none; font-weight: bold; }
    
    #captchaGrid {
        display: grid;
        grid-template-columns: repeat(3, 100px);
        grid-template-rows: repeat(2, 75px);
        gap: 2px;
        width: 304px;
        height: 154px;
        background-color: #333;
        margin: 0 auto;
        border-radius: 4px;
        overflow: hidden;
    }
    .puzzle-tile {
        width: 100px;
        height: 75px;
        cursor: pointer;
        border: 1px solid rgba(255,255,255,0.2);
        transition: opacity 0.2s, transform 0.1s;
        box-sizing: border-box;
        background-repeat: no-repeat;
        background-size: 300px 150px;
        background-color: #555;
    }
    .puzzle-tile:hover {
        opacity: 0.9;
    }
    .puzzle-tile.selected {
        outline: 3px solid #274e13;
        outline-offset: -3px;
        transform: scale(0.95);
    }
</style>

<div class="container text-center">
    <div class="login-container">
        
        <?php if (isset($_GET['timeout']) || (isset($_GET['msg']) && $_GET['msg'] === 'inactive')): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <strong>Déconnexion automatique :</strong> Inactivité prolongée (10 min).
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h1 class="fw-bold mb-4">Se connecter</h1>
        
        <?php if ($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success small"><?= $success ?></div><?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <input type="text" name="pseudo" class="form-control input-recyclivre mb-3" placeholder="Email ou Pseudo" required>
            <input type="password" name="mdp" class="form-control input-recyclivre mb-1" placeholder="Mot de passe" required>
            <div class="text-end mb-4">
                <a href="recuparation_mdp.php" class="small text-muted text-decoration-none">Mot de passe oublié ?</a>
            </div>

            <div class="mb-3">
                <label class="form-label">Vérification de sécurité</label>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#captchaModal" id="captchaTriggerBtn">
                    <i class="bi bi-puzzle"></i> Cliquez pour vérifier
                </button>
                <input type="hidden" name="captcha_token" id="captchaToken">
            </div>

            <div class="modal fade" id="captchaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h1 class="modal-title">Reconstituez l'image</h1> 
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <p class="text-muted small mb-3">Cliquez sur deux cases pour échanger leurs places.</p>
                            
                            <div id="captchaGrid"></div>
                            
                            <p id="captchaMessage" class="mt-3 small fw-bold mb-0" style="min-height: 20px;"></p>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-continue">CONTINUER</button>
        </form>
        <a href="inscription.php" class="d-block mt-4 text-success fw-bold text-decoration-none">CRÉER UN COMPTE</a>
    </div>
</div>

<script src="javascript/captcha_puzzle.js?v=<?= filemtime(__DIR__ . '/javascript/captcha_puzzle.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initCaptchaPuzzle('');
});
</script>
<?php include 'footer.php'; ?>