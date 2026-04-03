<?php
require_once 'config.php';
include 'header.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT l.*, e.prix, e.etat FROM livre l INNER JOIN exemplaire e ON l.id_livre = e.id_livre WHERE l.id_livre = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (!$livre) {
    echo "<div class='container mt-5'>Livre introuvable.</div>";
    include 'footer.php'; exit;
}
?>

<style>
    body { background-color: #f8f9fa; }
    .sticky-top { top: 20px; }
    .specs-table td { padding: 12px 0; border-bottom: 1px solid #eee; }
    .btn-custom-green { 
        background-color: #274e13; 
        color: white; 
        font-weight: bold;
        border: none;
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    .btn-custom-green:hover { background-color: #1a330d; color: white; transform: scale(1.02); }
    .section-header {
        color: #274e13;
        font-weight: bold;
        border-bottom: 2px solid #274e13;
        padding-bottom: 8px;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
</style>

<div class="container mt-5">
    <div class="row g-5">
        <div class="col-md-4">
            <div class="sticky-top">
                <div class="p-4 bg-white border rounded shadow-sm text-center">
                    <img src="img/<?= $livre['couverture'] ?: 'default.png' ?>" class="img-fluid" style="max-height: 450px;">
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <h1 class="fw-bold"><?= htmlspecialchars($livre['titre']) ?></h1>
            <p class="text-muted mb-4 h5">Par <?= htmlspecialchars($livre['auteur']) ?></p>
            <div class="mt-5">
                <h5 class="section-header">Résumé</h5>
                <p class="mt-3 text-secondary" style="line-height: 1.7; text-align: justify;">
                    <?= nl2br(htmlspecialchars($livre['description'] ?? 'Aucun résumé disponible.')) ?>
                </p>
            </div>
            <div class="mt-5">
                <h5 class="section-header">Caractéristiques</h5>
                <table class="table table-sm specs-table">
                    <tr><td class="text-muted">État</td><td class="fw-bold"><?= htmlspecialchars($livre['etat']) ?></td></tr>
                    <tr><td class="text-muted">Format</td><td class="fw-bold">Broché</td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 shadow-sm border-0 bg-white" style="border-radius: 15px;">
                <h2 class="fw-bold mb-3" style="color: #274e13;"><?= number_format($livre['prix'], 2) ?> €</h2>
                <p class="text-success small mb-4 fw-bold">En stock</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-custom-green py-3 shadow-sm">Ajouter au panier</button>
                    <button class="btn btn-outline-dark py-2" style="border-radius: 8px;">Acheter</button>
                </div>
                <hr class="my-4">
                <div class="small text-muted text-center">
                    <p class="mb-0 fw-bold" style="color: #274e13;">BIBLIOccaz</p>
                </div>
                <a href="index.php" class="btn btn-link btn-sm mt-3 text-decoration-none w-100 text-center" style="color: #274e13;">&larr; Retour</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>