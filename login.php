<?php
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
    elseif ($_GET['error'] == 'en_attente_moderateur') { $error = "Votre demande d'inscription en tant que modérateur est en cours d'examen par l'administrateur."; }
    elseif ($_GET['error'] == 'refuse_temporaire') { $error = "Votre candidature n'a pas été retenue pour l'instant car nous ne recherchons pas de modérateur actuellement. Vous pourrez retenter votre chance ultérieurement."; }
    elseif ($_GET['error'] == 'refuse_definitif') { $error = "Votre demande a été rejetée définitivement. Vous ne pouvez plus soumettre de candidature suite à un trop grand nombre de demandes après des refus temporaires ou pour cause de spam."; }
}

if (isset($_GET['verif'])) { $success = "Compte validé ! Vous pouvez vous connecter."; }
?>

<style>
    .login-container { max-width: 450px; margin: 80px auto; }
    .input-recyclivre { border: none !important; border-bottom: 1px solid #ccc !important; padding: 15px 0 !important; background: transparent !important; text-align: center; }
    .btn-continue { background-color: #274e13; color: white; border-radius: 50px; padding: 12px 0; width: 100%; margin-top: 20px; border: none; font-weight: bold; }
    
    #pieceCanvas {
        position: absolute !important;
        left: 0px !important;
        z-index: 99;
    }
</style>

<div class="container text-center">
    <div class="login-container">
        <h1 class="fw-bold mb-4">Se connecter</h1>
        
        <?php if ($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success small"><?= $success ?></div><?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <input type="text" name="pseudo" class="form-control input-recyclivre mb-3" placeholder="Email ou Pseudo" required>
            <input type="password" name="mdp" class="form-control input-recyclivre mb-4" placeholder="Mot de passe" required>

            <div class="mb-3">
                <label class="form-label">Vérification de sécurité</label>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#captchaModal" id="captchaTriggerBtn">
                    <i class="bi bi-puzzle"></i> Cliquez pour vérifier
                </button>
                <input type="hidden" name="captcha_token" id="captchaToken">
            </div>

            <div class="modal fade" id="captchaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title">Faites glisser pour compléter</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            
                            <div class="position-relative d-inline-block shadow-sm rounded overflow-hidden" id="captchaBox" style="width: 300px; height: 150px;">
                                <canvas id="mainCanvas" width="300" height="150"></canvas>
                                <canvas id="pieceCanvas" width="50" height="50"></canvas>
                            </div>

                            <div class="mt-4 px-3">
                                <input type="range" class="form-range captcha-slider" id="captchaSlider" min="0" max="250" value="0">
                            </div>
                            
                            <p id="captchaMessage" class="mt-2 small fw-bold"></p>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-continue">CONTINUER</button>
        </form>
        <a href="inscription.php" class="d-block mt-4 text-success fw-bold text-decoration-none">CRÉER UN COMPTE</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('captchaSlider');
    const pieceCanvas = document.getElementById('pieceCanvas');
    const mainCanvas = document.getElementById('mainCanvas');
    const triggerBtn = document.getElementById('captchaTriggerBtn');
    const tokenInput = document.getElementById('captchaToken');
    const message = document.getElementById('captchaMessage');
    
    let targetX = 0; 
    const pieceSize = 50; 

    function initPuzzle() {
        const ctxMain = mainCanvas.getContext('2d');
        const ctxPiece = pieceCanvas.getContext('2d');
        
        fetch('get_captcha_image.php')
            .then(response => response.json())
            .then(data => {
                const img = new Image();
                img.src = data.image; 
                
                img.onload = function() {
                    targetX = Math.floor(Math.random() * 140) + 70; 
                    const targetY = Math.floor(Math.random() * 60) + 20;  

                    ctxMain.clearRect(0, 0, mainCanvas.width, mainCanvas.height);
                    ctxPiece.clearRect(0, 0, pieceCanvas.width, pieceCanvas.height);

                    ctxMain.drawImage(img, 0, 0, mainCanvas.width, mainCanvas.height);

                    ctxMain.fillStyle = 'rgba(0, 0, 0, 0.5)';
                    ctxMain.fillRect(targetX, targetY, pieceSize, pieceSize);

                    pieceCanvas.width = pieceSize;
                    pieceCanvas.height = pieceSize;
                    pieceCanvas.style.top = targetY + 'px';
                    pieceCanvas.style.transform = 'translateX(0px)';

                    ctxPiece.drawImage(img, targetX, targetY, pieceSize, pieceSize, 0, 0, pieceSize, pieceSize);
                    
                    slider.disabled = false;
                    slider.value = 0;
                    slider.max = 250; 
                    message.textContent = '';
                    tokenInput.value = ''; 
                };
            })
            .catch(error => console.error('Erreur de chargement de l\'image CAPTCHA:', error));
    }

    slider.addEventListener('input', function() {
        const xPosition = slider.value;
        pieceCanvas.style.transform = 'translateX(' + xPosition + 'px)';
    });

    slider.addEventListener('change', function() {
        const userX = parseInt(slider.value);
        const difference = Math.abs(userX - targetX);
        const tolerance = 10; 

        if (difference <= tolerance) {
            tokenInput.value = btoa('success_validated_' + Date.now());
            
            triggerBtn.innerHTML = "✅ Vérification Réussie";
            triggerBtn.className = "btn btn-success w-100";
            
            message.textContent = "✅ Parfait ! Humain validé.";
            message.className = "mt-2 small text-success";
            
            slider.disabled = true;

            setTimeout(() => {
                const modalEl = document.getElementById('captchaModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }, 500); 
        } else {
            message.textContent = "❌ Mauvais alignement. Réessayez.";
            message.className = "mt-2 small text-danger";
            
            setTimeout(() => {
                slider.value = 0;
                pieceCanvas.style.transform = 'translateX(0px)';
                message.textContent = '';
                tokenInput.value = '';
            }, 1000);
        }
    });

    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!tokenInput.value) {
            e.preventDefault(); 
            alert("Veuillez d'abord réussir le puzzle de sécurité.");
            
            const modalEl = document.getElementById('captchaModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else {
            
            console.log("Jeton envoyé :", tokenInput.value);
        }
    });

    document.getElementById('captchaModal').addEventListener('shown.bs.modal', initPuzzle);
});
</script>
<?php include 'footer.php'; ?>