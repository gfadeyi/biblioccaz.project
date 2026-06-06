<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ?");
    
    foreach ($_SESSION['panier'] as $item) {
        for ($i = 0; $i < $item['quantite']; $i++) {
            $stmtUpdate->execute([$item['id_exemplaire']]);
            insertLog('ACHAT', "Livre ID #" . $item['id_livre'] . " achete. Stock mis a jour.");
        }
    }

    $pdo->exec("DELETE FROM exemplaire WHERE quantite <= 0");

    $pdo->commit();
    insertLog('ACHAT', "Commande validee globalement. Panier vide.");

} catch (Exception $e) {
    $pdo->rollBack();
    insertLog('ERREUR', "Echec de la validation de commande : " . $e->getMessage());
    header("Location: panier.php?error=" . urlencode("Une erreur est survenue lors de la validation du stock."));
    exit();
}

unset($_SESSION['panier']);
include 'header.php';
?>

<div class="container mt-5 mb-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-5 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4.5rem;"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Achat effectué avec succès !</h2>
                <p class="text-secondary mb-4">Merci pour votre confiance. Votre commande a bien été enregistrée.</p>
                <div class="d-grid">
                    <a href="index.php" class="btn btn-success py-3 rounded-pill text-white fw-bold text-decoration-none shadow-sm">Retourner à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>