<?php
session_start();
if (isset($_POST['connexion'])) {
    if ($_POST['user'] == "admin" && $_POST['pass'] == "1234") {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit();
    } else { $erreur = "Identifiants incorrects !"; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Connexion - Biblioccaz</title>
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow border-0">
            <div class="card-body p-4 text-center">
                <h2 class="text-success fw-bold mb-4">BIBLIOccaz</h2>
                <?php if(isset($erreur)) echo "<div class='alert alert-danger'>$erreur</div>"; ?>
                <form method="POST">
                    <input type="text" name="user" class="form-control mb-3" placeholder="Utilisateur" required>
                    <input type="password" name="pass" class="form-control mb-3" placeholder="Mot de passe" required>
                    <button type="submit" name="connexion" class="btn btn-success w-100">Entrer</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>