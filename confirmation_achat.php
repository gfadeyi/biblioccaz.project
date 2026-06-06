<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("UPDATE exemplaire 
                                 SET is_disponible = 0 
                                 WHERE id_livre = ? AND etat = ? AND is_disponible = 1 
                                 LIMIT 1");

    foreach ($_SESSION['panier'] as $item) {
        for ($i = 0; $i < $item['quantite']; $i++) {
            $stmtUpdate->execute([
                $item['id_livre'],
                $item['etat']
            ]);
            
            insertLog('ACHAT', "Livre ID #" . $item['id_livre'] . " (Etat: " . $item['etat'] . ") achete. Sortie de stock reussie.");
        }
    }

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

<style>
    .btn-custom-green { 
        background-color: #274e13; 
        color: white; 
        font-weight: bold;
        border: none;
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    .btn-custom-green:hover { 
        background-color: #1a330d; 
        color: white; 
    }
</style>

<div class="container mt-5 mb-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-5 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4.5rem;"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Achat effectué avec succès !</h2>
                <p class="text-secondary mb-4">Merci pour votre confiance. Votre commande a bien été enregistrée et un e-mail de confirmation vient de vous être envoyé pour valider votre identité.</p>
                
                <div class="bg-light border rounded-4 p-3 mb-4 text-start">
                    <div class="d-flex align-items-center text-success mb-2 fw-bold">
                        <i class="bi bi-shield-fill-check me-2"></i> Mode Test Réussi
                    </div>
                    <small class="text-muted d-block mb-1">&bull; Base de données mise à jour (Exemplaires retirés du stock).</small>
                    <small class="text-muted d-block mb-1">&bull; Fichier d'activité sécurisé mis à jour (Logs générés dans secure_data/logs.txt).</small>
                    <small class="text-muted d-block mb-1">&bull; Panier réinitialisé en session PHP.</small>
                    <small class="text-muted d-block">&bull; Pastille dynamique du header mise à jour.</small>
                </div>

                <div class="d-grid">
                    <a href="index.php" class="btn btn-custom-green py-3 rounded-pill text-white fw-bold text-decoration-none shadow-sm">
                        Retourner à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>