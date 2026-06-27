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
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'inactive'): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <strong>Déconnexion automatique :</strong> Inactivité prolongée (3 min).
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('captchaGrid');
    const triggerBtn = document.getElementById('captchaTriggerBtn');
    const tokenInput = document.getElementById('captchaToken');
    const message = document.getElementById('captchaMessage');
    
    const cols = 3;
    const rows = 2;
    
    let tiles = [];
    let selectedTile = null;
    let isVerified = false;

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    function buildPuzzle(imgUrl) {
        tiles = [];
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                tiles.push({
                    correctIndex: r * cols + c,
                    currentIndex: r * cols + c,
                    posX: -(c * 100),
                    posY: -(r * 75)
                });
            }
        }

        let attempts = 0;
        do {
            shuffleArray(tiles);
            attempts++;
        } while (checkWin() && attempts < 10);
        
        tiles.forEach((tile, index) => { tile.currentIndex = index; });
        renderGrid(imgUrl);
    }

    function initPuzzle() {
        isVerified = false;
        selectedTile = null;
        grid.innerHTML = '';
        message.textContent = '';
        tokenInput.value = '';

        fetch('get_captcha_image.php')
            .then(response => response.json())
            .then(data => {
                let imgUrl = (data && data.image) ? data.image : 'img/fond_puzzle_2.jpg';
                buildPuzzle(imgUrl);
            })
            .catch(() => {
                buildPuzzle('img/fond_puzzle_2.jpg');
            });
    }

    function renderGrid(imgUrl) {
        grid.innerHTML = '';
        let currentOrder = [...tiles].sort((a, b) => a.currentIndex - b.currentIndex);

        currentOrder.forEach(tile => {
            const box = document.createElement('div');
            box.className = 'puzzle-tile';
            box.dataset.index = tile.currentIndex;
            box.style.backgroundImage = 'url(' + imgUrl + ')';
            box.style.backgroundPosition = tile.posX + 'px ' + tile.posY + 'px';
            
            box.addEventListener('click', function() {
                if (isVerified) return;
                
                if (!selectedTile) {
                    selectedTile = box;
                    box.classList.add('selected');
                } else if (selectedTile === box) {
                    selectedTile.classList.remove('selected');
                    selectedTile = null;
                } else {
                    const idx1 = parseInt(selectedTile.dataset.index);
                    const idx2 = parseInt(box.dataset.index);
                    
                    const t1 = tiles.find(t => t.currentIndex === idx1);
                    const t2 = tiles.find(t => t.currentIndex === idx2);
                    
                    t1.currentIndex = idx2;
                    t2.currentIndex = idx1;
                    
                    selectedTile.classList.remove('selected');
                    selectedTile = null;
                    
                    renderGrid(imgUrl);
                    
                    if (checkWin()) {
                        isVerified = true;
                        tokenInput.value = btoa('success_validated_' + Date.now());
                        
                        triggerBtn.innerHTML = "✅ Vérification Réussie";
                        triggerBtn.className = "btn btn-success w-100";
                        
                        message.textContent = "✅ Parfait ! Image reconstituée.";
                        message.className = "mt-3 small text-success fw-bold";
                        
                        setTimeout(() => {
                            const modalEl = document.getElementById('captchaModal');
                            if (window.bootstrap && bootstrap.Modal) {
                                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                    return;
                                }
                            }
                            const fallbackClose = modalEl.querySelector('[data-bs-dismiss="modal"]');
                            if (fallbackClose) fallbackClose.click();
                        }, 600);
                    }
                }
            });
            
            grid.appendChild(box);
        });
    }

    function checkWin() {
        return tiles.every(tile => tile.correctIndex === tile.currentIndex);
    }

    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!tokenInput.value) {
            e.preventDefault(); 
            alert("Veuillez d'abord réussir le puzzle de sécurité.");
            
            const modalEl = document.getElementById('captchaModal');
            if (window.bootstrap && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        }
    });

    const modalElement = document.getElementById('captchaModal');
    modalElement.addEventListener('shown.bs.modal', function () {
        initPuzzle();
    });
});
</script>
<?php include 'footer.php'; ?>