<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIBLIOccaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Francois+One&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --dark-green: #274E13; --accent-green: #8FCE00; --border-green: #93C47D; }
        body { background-color: #ffffff; color: var(--dark-green); font-family: 'Open Sans', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        h1, h2, h3, .navbar-brand { font-family: 'Francois One', sans-serif; color: var(--dark-green) !important; }
        header { background-color: #ffffff !important; border-bottom: 4px solid var(--border-green) !important; }
        .btn-accent { background-color: var(--accent-green) !important; color: var(--dark-green) !important; font-weight: bold; border-radius: 20px; border: none; }
        .card-custom { background-color: #ffffff !important; border: 2px solid var(--border-green) !important; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        main { flex: 1; padding-top: 20px; }
    </style>
</head>
<body>
<header class="py-3 mb-4 shadow-sm">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4"><a class="navbar-brand fs-2 text-decoration-none" href="index.php">BIBLIOccaz</a></div>
            <div class="col-md-4 text-center">
                <input type="text" class="form-control rounded-pill border-2" style="border-color: var(--border-green)" placeholder="Rechercher un livre...">
            </div>
            <div class="col-md-4 text-end">
                <?php if(isset($_SESSION['admin'])): ?>
                    <a href="admin.php" class="btn btn-outline-success btn-sm rounded-pill px-3">Admin</a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Deconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<main class="container">
    <div class="row justify-content-center">