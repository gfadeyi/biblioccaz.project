<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center mb-5">
            <h1 class="display-5 fw-bold" style="color: #274e13;">Espace Administration</h1>
            <p class="lead">Bienvenue, <?php echo htmlspecialchars($_SESSION['pseudo']); ?></p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="ajouter_livre.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center admin-card">
                    <i class="bi bi-plus-circle-dotted display-4 text-success mb-3"></i>
                    <h3 class="h5 text-dark">Ajouter un livre</h3>
                    <p class="text-muted small">Enregistrer une nouvelle référence</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="inventaire_admin.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center admin-card">
                    <i class="bi bi-journal-check display-4 text-primary mb-3"></i>
                    <h3 class="h5 text-dark">Inventaire</h3>
                    <p class="text-muted small">Modifier ou supprimer des livres</p>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="gestion_users.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center admin-card">
                    <i class="bi bi-people display-4 text-info mb-3"></i>
                    <h3 class="h5 text-dark">Utilisateurs</h3>
                    <p class="text-muted small">Gérer les comptes admin</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
.admin-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 15px;
}
.admin-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>

<?php include 'footer.php'; ?>