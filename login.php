<?php
include 'header.php';

$error = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') { $error = "Identifiants incorrects."; }
    elseif ($_GET['error'] == 'captcha') { $error = "Veuillez compléter le puzzle."; }
    elseif ($_GET['error'] == 'not_verified') { $error = "Veuillez confirmer votre email avant de vous connecter."; }
}
if (isset($_GET['verif'])) { $success = "Compte validé ! Vous pouvez vous connecter."; }
?>

<style>
    .login-container { max-width: 450px; margin: 80px auto; }
    .input-recyclivre { border: none !important; border-bottom: 1px solid #ccc !important; padding: 15px 0 !important; background: transparent !important; text-align: center; }
    .btn-continue { background-color: #274e13; color: white; border-radius: 50px; padding: 12px 0; width: 100%; margin-top: 20px; border: none; font-weight: bold; }
    .puzzle-choice img { cursor: pointer; width: 75px; height: 75px; object-fit: contain; background: #eee; border-radius: 8px; }
    .puzzle-choice input:checked + img { border: 3px solid #274e13 !important; background: #e2efda; }
    .captcha-bg { background: url('img/fond_puzzle.jpg') no-repeat center; background-size: cover; height: 160px; border-radius: 8px; }
</style>

<div class="container text-center">
    <div class="login-container">
        <h1 class="fw-bold mb-4">Se connecter</h1>
        
        <?php if ($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
        <?php if (isset($success)): ?><div class="alert alert-success small"><?= $success ?></div><?php endif; ?>

        <form action="traitement_login.php" method="POST">
            <input type="text" name="pseudo" class="form-control input-recyclivre mb-3" placeholder="Email ou Pseudo" required>
            <input type="password" name="mdp" class="form-control input-recyclivre mb-4" placeholder="Mot de passe" required>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header small fw-bold">Sécurité : Complétez le puzzle</div>
                <div class="card-body">
                    <div class="captcha-bg mb-3"></div>
                    <div class="d-flex justify-content-around">
                        <label class="puzzle-choice"><input type="radio" name="puzzle_pos" value="wrong1" class="btn-check" required><img src="img/piece_puzzle_false_1.jpg"></label>
                        <label class="puzzle-choice"><input type="radio" name="puzzle_pos" value="correct" class="btn-check"><img src="img/piece_puzzle.jpg"></label>
                        <label class="puzzle-choice"><input type="radio" name="puzzle_pos" value="wrong2" class="btn-check"><img src="img/piece_puzzle_false_2.jpg"></label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-continue">CONTINUER</button>
        </form>
        <a href="inscription.php" class="d-block mt-4 text-success fw-bold text-decoration-none">CRÉER UN COMPTE</a>
    </div>
</div>
<?php include 'footer.php'; ?>