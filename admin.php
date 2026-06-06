<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$countTitresValides = $pdo->query("SELECT COUNT(*) FROM livre WHERE is_valide = 1")->fetchColumn();
$countAttente = $pdo->query("SELECT COUNT(*) FROM livre WHERE is_valide = 0")->fetchColumn();
$countDisponibles = $pdo->query("SELECT SUM(quantite) FROM exemplaire WHERE is_disponible = 1")->fetchColumn() ?: 0;
$countUsers = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();

include 'header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Dashboard Général</h2>
        <span class="badge bg-dark rounded-pill px-3 py-2">Administration Active</span>
    </div>
    
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; background-color: #f4f7f4;">
                <i class="bi bi-book text-success fs-1 mb-2"></i>
                <h3 class="fw-bold"><?= $countTitresValides ?></h3>
                <p class="text-muted mb-0 small">Titres validés</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; background-color: #fff3cd;">
                <i class="bi bi-shield-exclamation text-warning fs-1 mb-2"></i>
                <h3 class="fw-bold text-warning"><?= $countAttente ?></h3>
                <p class="text-muted mb-0 small">En attente de validation</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; background-color: #f4f7f4;">
                <i class="bi bi-box-seam text-success fs-1 mb-2"></i>
                <h3 class="fw-bold"><?= $countDisponibles ?></h3>
                <p class="text-muted mb-0 small">Exemplaires en vente</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; background-color: #f4f7f4;">
                <i class="bi bi-people text-success fs-1 mb-2"></i>
                <h3 class="fw-bold"><?= $countUsers ?></h3>
                <p class="text-muted mb-0 small">Membres inscrits</p>
            </div>
        </div>
    </div>

    <div class="row mt-5 g-4">
        <div class="col-md-6">
            <h4 class="fw-bold mb-3">Gestion Catalogue</h4>
            <div class="list-group shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
                <a href="ajouter_livre.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-plus-circle me-2 text-success"></i> Ajouter un titre (ISBN)
                </a>
                <a href="moderation_catalogue.php" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check me-2 text-warning"></i> Modération des fiches</span>
                    <?php if($countAttente > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $countAttente ?></span>
                    <?php endif; ?>
                </a>
                <a href="inventaire_admin.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-box-seam me-2 text-success"></i> Inventaire des stocks
                </a>
                <a href="export-pdf.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-file-earmark-pdf me-2 text-success"></i> Export des inscrits (PDF)
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <h4 class="fw-bold mb-3">Utilisateurs & Logs</h4>
            <div class="list-group shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
                <a href="gestion_utilisateurs.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-people me-2 text-success"></i> Gestion des membres
                </a>
                <a href="diffusion_admin.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-megaphone me-2 text-success"></i> Diffusion Newsletter
                </a>
                <a href="admin_logs.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-journal-text me-2 text-success"></i> Journal d'activité (Logs)
                </a>
                <a href="admin_captcha.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-images me-2 text-success"></i> Gestion des images CAPTCHA 
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>