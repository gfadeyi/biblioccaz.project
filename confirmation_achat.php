<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'panier_calcul.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$prenom  = trim($_POST['prenom'] ?? '');
$nom     = trim($_POST['nom'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');

if ($prenom === '' || $nom === '' || $adresse === '') {
    header("Location: achat.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. On relit chaque exemplaire en base (prix réel + stock), en le verrouillant
    //    pour éviter que deux clients achètent le dernier exemplaire en même temps.
    $stmtEx = $pdo->prepare("
        SELECT e.id_exemplaire, e.id_livre, e.prix, e.etat, e.quantite, l.titre
        FROM exemplaire e
        INNER JOIN livre l ON l.id_livre = e.id_livre
        WHERE e.id_exemplaire = ?
        FOR UPDATE");

    $lignes = [];
    foreach ($_SESSION['panier'] as $item) {
        $stmtEx->execute([$item['id_exemplaire']]);
        $ex = $stmtEx->fetch();
        $qte = (int) $item['quantite'];

        if (!$ex || $ex['quantite'] < $qte) {
            throw new RuntimeException("Stock insuffisant pour « " . $item['titre'] . " ».");
        }

        $lignes[] = [
            'id_exemplaire' => $ex['id_exemplaire'],
            'id_livre'      => $ex['id_livre'],
            'titre'         => $ex['titre'],
            'etat'          => $ex['etat'],
            'prix'          => (float) $ex['prix'],   // prix de la base, pas celui envoyé par le navigateur
            'quantite'      => $qte,
        ];
    }

    $totaux = calculerTotauxPanier($lignes);

    // 2. Enregistrement de la commande
    $stmtCmd = $pdo->prepare("
        INSERT INTO commande (id_user, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtCmd->execute([
        $_SESSION['user_id'], $prenom, $nom, $adresse,
        $totaux['sous_total'], $totaux['remise'], $totaux['frais_livraison'], $totaux['total'], $totaux['points'],
    ]);
    $idCommande = (int) $pdo->lastInsertId();

    // 3. Lignes de commande + mise à jour du stock
    $stmtLigne = $pdo->prepare("
        INSERT INTO commande_ligne (id_commande, id_livre, titre, etat, prix_unitaire, quantite)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE exemplaire SET quantite = quantite - ? WHERE id_exemplaire = ?");

    foreach ($lignes as $l) {
        $stmtLigne->execute([$idCommande, $l['id_livre'], $l['titre'], $l['etat'], $l['prix'], $l['quantite']]);
        $stmtStock->execute([$l['quantite'], $l['id_exemplaire']]);
        insertLog('ACHAT', "Commande #$idCommande : {$l['quantite']} x livre ID #{$l['id_livre']}. Stock mis a jour.");
    }

    $pdo->exec("DELETE FROM exemplaire WHERE quantite <= 0");

    // 4. Points fidélité
    if ($totaux['points'] > 0) {
        $pdo->prepare("UPDATE user SET solde_points = solde_points + ? WHERE id = ?")
            ->execute([$totaux['points'], $_SESSION['user_id']]);
    }

    $pdo->commit();
    insertLog('ACHAT', "Commande #$idCommande validee ({$totaux['total']} EUR, +{$totaux['points']} points).");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    insertLog('ERREUR', "Echec de la validation de commande : " . $e->getMessage());
    $message = $e instanceof RuntimeException ? $e->getMessage() : "Une erreur est survenue lors de la validation de la commande.";
    header("Location: panier.php?error=" . urlencode($message));
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
                <p class="text-secondary mb-2">Merci pour votre confiance. Votre commande <strong>n°<?= $idCommande ?></strong> a bien été enregistrée.</p>
                <p class="text-secondary mb-2">Montant payé : <strong><?= number_format($totaux['total'], 2) ?> €</strong></p>
                <?php if ($totaux['points'] > 0): ?>
                    <p class="text-success fw-bold mb-4"><i class="bi bi-coin me-1"></i> +<?= $totaux['points'] ?> points fidélité crédités</p>
                <?php else: ?>
                    <div class="mb-4"></div>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="commandes.php" class="btn btn-outline-success py-3 rounded-pill fw-bold text-decoration-none">Voir mes commandes</a>
                    <a href="index.php" class="btn btn-success py-3 rounded-pill text-white fw-bold text-decoration-none shadow-sm">Retourner à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>