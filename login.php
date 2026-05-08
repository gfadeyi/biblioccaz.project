<?php
include 'header.php';

$error = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $error = "Identifiants incorrects.";
    } elseif ($_GET['error'] == 'captcha') {
        $error = "Vérification échouée : Mauvaise pièce du puzzle.";
    } elseif ($_GET['error'] == 'empty') {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<style>
    .login-container { max-width: 450px; margin: 80px auto; }
    .input-recyclivre {
        border: none !important; border-bottom: 1px solid #ccc !important;
        border-radius: 0 !important; padding: 20px 0 !important;
        background: transparent !important; text-align: center;
        font-size: 1.1rem;
    }
    .input-recyclivre:focus { border-bottom-color: #274e13 !important; box-shadow: none !important; }
    .btn-continue {
        background-color: #274e13; color: white; border: none;
        border-radius: 50px; padding: 12px 0; font-weight: bold;
        width: 100%; margin-top: 30px; transition: 0.3s;
        cursor: pointer;
    }
    .btn-continue:hover { background-color: #1a330d; }
    .puzzle-choice input:checked + img { border: 3px solid #274e13 !important; background-color: rgba(39, 78, 19, 0.2); }
    .puzzle-choice img { cursor: pointer; width: 80px; height: 80px; object-fit: contain; background: #eee; border-radius: 8px; }
    .captcha-bg {
        background: url('img/fond_puzzle.jpg') no-repeat center; 
        background-size: cover; height: 180px; border-radius: 8px; border: 1px solid #ddd;
    }
</style>

<div class="container">
    <div class="login-container text-center">
        <h1 class="fw-bold mb-5">Se connecter</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <input type="text" name="pseudo" class="form-control input-recyclivre mb-2" placeholder="E-mail ou Pseudo" required>
            <input type="password" name="mdp" class="form-control input-recyclivre mb-4" placeholder="Mot de passe" required>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light small fw-bold">Sécurité : Complétez le puzzle</div>
                <div class="card-body">
                    <div class="captcha-bg mb-3"></div>
                    <div class="d-flex justify-content-around">
                        <label class="puzzle-choice">
                            <input type="radio" name="puzzle_pos" value="wrong1" class="btn-check" required>
                            <img src="img/piece_puzzle_false_1.jpg">
                        </label>
                        <label class="puzzle-choice">
                            <input type="radio" name="puzzle_pos" value="correct" class="btn-check">
                            <img src="img/piece_puzzle.jpg">
                        </label>
                        <label class="puzzle-choice">
                            <input type="radio" name="puzzle_pos" value="wrong2" class="btn-check">
                            <img src="img/piece_puzzle_false_2.jpg">
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-continue">CONTINUER</button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <p class="text-muted small mb-2">Nouveau sur BIBLIOccaz ?</p>
            <a href="inscription.php" class="btn btn-outline-success rounded-pill w-100 fw-bold py-2" style="border-color: #274e13; color: #274e13; text-decoration: none;">
                CRÉER UN COMPTE
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>