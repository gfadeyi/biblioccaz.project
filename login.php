<?php
session_start();
require_once 'config.php'; 

if (isset($_POST['connexion'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ?");
    $stmt->execute([$user]);
    $resultat = $stmt->fetch();

    
   
    if ($resultat && $pass === $resultat['mot_de_passe']) {
        $_SESSION['admin'] = true;
        $_SESSION['pseudo'] = $resultat['pseudo'];
        $_SESSION['role'] = $resultat['role']; 
        
        header("Location: admin.php");
        exit();
    } else {
        $erreur = "Identifiants incorrects !";
    }
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
                
                <?php if(isset($erreur)): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
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