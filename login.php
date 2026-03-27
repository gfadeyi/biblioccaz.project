<?php
session_start();
require_once 'config.php'; 
include 'header.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <title>Connexion - Biblioccaz</title>
    <style>
        body { 
            background-color: #d9ead3 !important; 
        }
        .card {
            border: 2px solid #b6d7a8 !important;
        }
        .text-success { 
            color: #274e13 !important; 
        }
        .btn-success {
            background-color: #8fce00 !important;
            border-color: #274e13 !important;
            color: #274e13 !important;
            font-weight: bold;
        }
        .btn-success:hover {
            background-color: #93c47d !important;
            border-color: #274e13 !important;
        }
        .btn-outline-secondary {
            border-color: #93c47d !important;
            color: #274e13 !important;
        }
        .btn-outline-secondary:hover {
            background-color: #b6d7a8 !important;
            border-color: #274e13 !important;
            color: #274e13 !important;
        }
        .form-control:focus {
            border-color: #8fce00 !important;
            box-shadow: 0 0 0 0.25rem rgba(143, 206, 0, 0.25) !important;
        }
    </style>
</head>
<body class="d-flex align-items-center" style="height: 100vh;">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow border-0">
            <div class="card-body p-4 text-center">
                <h2 class="text-success fw-bold mb-4">BIBLIOccaz</h2>
                
                <?php if(isset($erreur)): ?>
                    <div class="alert alert-danger py-2"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <input type="text" name="user" class="form-control" placeholder="Utilisateur" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="pass" class="form-control" placeholder="Mot de passe" required>
                    </div>
                    <button type="submit" name="connexion" class="btn btn-success w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right"></i> Entrer
                    </button>
                </form>

                <hr style="color: #93c47d;">

                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-house-door"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
include'footer.php';

