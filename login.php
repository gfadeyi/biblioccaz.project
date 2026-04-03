<?php
require_once 'config.php';
include 'header.php';

if (isset($_POST['connexion'])) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ?");
    $stmt->execute([$_POST['user']]);
    $u = $stmt->fetch();

    // Vérification du mot de passe (esgiroot601 d'après tes tests)
    if ($u && $_POST['pass'] === $u['mot_de_passe']) {
        $_SESSION['admin'] = true;
        $_SESSION['id_user'] = $u['id_user']; // Indispensable pour l'admin
        $_SESSION['pseudo'] = $u['pseudo'];
        header("Location: admin.php");
        exit();
    } else {
        $erreur = "Identifiants incorrects";
    }
}
?>

<div class="col-md-5">
    <div class="card card-custom p-5 text-center">
        <h2 class="mb-4">Connexion</h2>
        <?php if(isset($erreur)) echo "<div class='alert alert-danger py-2 small'>$erreur</div>"; ?>
        <form method="POST">
            <input type="text" name="user" class="form-control mb-3 rounded-pill" placeholder="Pseudo" required>
            <input type="password" name="pass" class="form-control mb-4 rounded-pill" placeholder="Mot de passe" required>
            <button type="submit" name="connexion" class="btn btn-accent w-100 py-2 rounded-pill shadow-sm">Entrer</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>