<?php
session_start();


if (!isset($_POST['captcha_choice']) || $_POST['captcha_choice'] !== 'correct_answer') {
    header("Location: login.php?error=captcha");
    exit();
}


$user = $_POST['username'];
$pass = $_POST['password'];


?>
<?php if(isset($_GET['error']) && $_GET['error'] == 'captcha'): ?>
    <div class="alert alert-danger">
        Vérification échouée : Vous n'avez pas cliqué sur la bonne pièce du puzzle.
    </div>
<?php endif; ?>