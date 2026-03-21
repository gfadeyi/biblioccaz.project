<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }

if (isset($_GET['logout'])) { session_destroy(); header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin - Ajouter un livre</title>
</head>
<body class="bg-white">
    <nav class="navbar navbar-dark bg-dark p-3">
        <span class="navbar-brand">Administration Biblioccaz</span>
        <a href="?logout=1" class="btn btn-outline-danger btn-sm">Déconnexion</a>
    </nav>
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white fw-bold">Ajouter un nouveau livre d'occasion</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3"><label>Titre du livre</label><input type="text" class="form-control" required></div>
                    <div class="mb-3"><label>Auteur</label><input type="text" class="form-control" required></div>
                    <div class="mb-3"><label>Prix (€)</label><input type="number" step="0.01" class="form-control" required></div>
                    <button type="submit" class="btn btn-success w-100">Enregistrer dans le catalogue</button>
                </form>
            </div>
        </div>
        <div class="text-center mt-3"><a href="index.php">← Retour au site</a></div>
    </div>
</body>
</html>