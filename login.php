<?php
include 'header.php';
$error = isset($_GET['error']) ? "Identifiants incorrects." : "";
?>

<style>
    .login-container {
        max-width: 450px;
        margin: 80px auto;
    }
    .input-recyclivre {
        border: none !important;
        border-bottom: 1px solid #ccc !important;
        border-radius: 0 !important;
        padding: 20px 0 !important;
        background: transparent !important;
        text-align: center;
        font-size: 1.1rem;
        color: inherit !important;
    }
    .input-recyclivre:focus {
        box-shadow: none !important;
        border-bottom-color: #274e13 !important;
        outline: none;
    }
    .btn-continue {
        background-color: #274e13;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 0;
        font-weight: bold;
        width: 100%;
        margin-top: 40px;
        transition: 0.3s;
    }
    .btn-continue:hover {
        background-color: #1a330d;
    }
    .divider-container {
        position: relative;
        margin: 50px 0;
    }
    .divider-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 0 15px;
    }
    body.dark-mode .divider-text, 
    body.dark-mode .bg-custom {
        background-color: #121212 !important;
    }
    body:not(.dark-mode) .divider-text, 
    body:not(.dark-mode) .bg-custom {
        background-color: #ffffff !important;
    }
    .social-btn {
        border: 1px solid #eee !important;
        color: inherit !important;
        text-decoration: none !important;
    }
    body.dark-mode .social-btn {
        border-color: #444 !important;
    }
</style>

<div class="container">
    <div class="login-container text-center">
        <h1 class="fw-bold mb-5">Se connecter / S'inscrire</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <div class="mb-2">
                <input type="text" name="pseudo" class="form-control input-recyclivre" placeholder="E-mail ou Pseudo" required autocomplete="off">
            </div>
            
            <div class="mb-2">
                <input type="password" name="mdp" class="form-control input-recyclivre" placeholder="Mot de passe" required>
            </div>

            <button type="submit" class="btn-continue">CONTINUER</button>
        </form>

        <div class="divider-container">
            <hr>
            <span class="divider-text text-muted small">Ou</span>
        </div>

        <div class="d-grid gap-3">
            <a href="#" class="btn social-btn rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm bg-custom">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" width="18" class="me-2">
                <span>Avec Google</span>
            </a>
            <a href="#" class="btn social-btn rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm bg-custom">
                <i class="bi bi-facebook text-primary me-2 fs-5"></i>
                <span>Avec Facebook</span>
            </a>
            <a href="inscription.php" class="btn social-btn rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm bg-custom">
            <i class="bi bi-person-plus me-2 fs-5" style="color: #274e13;"></i>
            <span>Créer un compte</span>
        </a>
        </div>
    </div>
</div>

<footer class="mt-5 pt-5 border-top">
    <div class="container py-5">
        <div class="row text-center text-md-start">
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold text-success">BIBLIOccaz</h5>
                <p class="text-muted small">Donnez une seconde vie à vos lectures.</p>
            </div>
            <div class="col-md-8 text-md-end mb-4">
                <div class="d-flex justify-content-center justify-content-md-end gap-3 fs-4 mb-3">
                    <a href="#" class="text-dark"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-dark"><i class="bi bi-facebook"></i></a>
                </div>
                <p class="small text-muted mb-0">PAIEMENT 100% SÉCURISÉ</p>
                <div class="mt-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="Paypal" height="15" class="me-2" style="opacity: 0.6">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" height="10" class="me-2" style="opacity: 0.6">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" height="15" style="opacity: 0.6">
                </div>
                <p class="small text-muted mt-3">© 2026 BIBLIOccaz - ESGI</p>
            </div>
        </div>
    </div>
</footer>

<?php include 'footer.php'; ?>