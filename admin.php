<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }

if (isset($_GET['logout'])) { session_destroy(); header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <title>Admin - Ajouter un livre</title>
    <style>
        body { 
            background-color: #d9ead3; 
            color: #274e13;
        }
        .navbar { 
            background-color: #274e13 !important; 
            border-bottom: 3px solid #8fce00;
        }
        .card {
            border: 2px solid #b6d7a8 !important;
            border-radius: 15px;
        }
        .card-header {
            background-color: #b6d7a8 !important;
            color: #274e13 !important;
            border-bottom: 2px solid #93c47d !important;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-control:focus {
            border-color: #8fce00 !important;
            box-shadow: 0 0 0 0.25rem rgba(143, 206, 0, 0.25) !important;
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
        .btn-logout {
            border-color: #8fce00 !important;
            color: #8fce00 !important;
        }
        .btn-logout:hover {
            background-color: #8fce00 !important;
            color: #274e13 !important;
        }
        .link-back {
            color: #274e13 !important;
            text-decoration: none;
            font-weight: bold;
        }
        .link-back:hover {
            color: #8fce00 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark p-3 shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold"><i class="bi bi-shield-lock"></i> Administration Biblioccaz</span>
            <a href="?logout=1" class="btn btn-logout btn-sm px-3">
                <i class="bi bi-power"></i> Déconnexion
            </a>
        </div>
    </nav>

    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter un nouveau livre d'occasion</h5>
            </div>
            <div class="card-body p-4 bg-white" style="border-radius: 0 0 15px 15px;">
                <form method="POST">
                    <div class="mb-3">
                        <label><i class="bi bi-bookmark-fill text-success"></i> Titre du livre</label>
                        <input type="text" class="form-control" placeholder="Ex: Les Misérables" required>
                    </div>
                    <div class="mb-3">
                        <label><i class="bi bi-person-fill text-success"></i> Auteur</label>
                        <input type="text" class="form-control" placeholder="Ex: Victor Hugo" required>
                    </div>
                    <div class="mb-3">
                        <label><i class="bi bi-currency-euro text-success"></i> Prix (€)</label>
                        <input type="number" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2 shadow-sm">
                        <i class="bi bi-cloud-arrow-up"></i> Enregistrer dans le catalogue
                    </button>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="link-back">
                <i class="bi bi-arrow-left"></i> Retour au site public
            </a>
        </div>
    </div>
</body>
</html>