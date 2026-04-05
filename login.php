<?php include 'header.php'; ?>

<div class="container d-flex justify-content-center" style="min-height: 70vh; align-items: center;">
    <div class="auth-container shadow-sm p-5 border rounded bg-white" style="width: 100%; max-width: 400px;">
        <h2 class="auth-title text-center mb-4">Connexion</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center mb-4">
                Identifiant ou mot de passe incorrect.
            </div>
        <?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Pseudo</label>
                <input type="text" name="pseudo" class="form-control py-2" placeholder="Votre pseudo" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="mdp" class="form-control py-2" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-auth w-100 py-2">Entrer</button>
        </form>
        <hr style="color: #93c47d;">
    <p class="text-center mb-0" style="font-size: 0.9rem;">
    Pas encore de compte ?
    <a href="inscription.php" style="color: #274e13; font-weight: bold;">S'inscrire</a>
    </p>
    </div>
</div>

<?php include 'footer.php'; ?>