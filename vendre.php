<?php
require_once 'config.php';
require_once 'dons_lib.php';

$nbDonsDisponibles = (int) $pdo->query("SELECT COUNT(*) FROM don WHERE statut = 'disponible' AND date_limite >= CURDATE()")->fetchColumn();
$nbDonsRecuperes = (int) $pdo->query("SELECT COUNT(*) FROM don WHERE statut = 'recupere'")->fetchColumn();

include 'header.php';
?>

<div class="container my-5" style="max-width: 1000px;">
    <div class="text-center mb-5">
        <h1 class="fw-bold" style="color:#274e13;">Donnez une seconde vie à vos livres</h1>
        <p class="text-muted">Vos livres dorment sur une étagère ? Offrez-les à d'autres lecteurs.</p>
        <?php if ($nbDonsRecuperes > 0): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                <i class="bi bi-heart-fill"></i> <?= $nbDonsRecuperes ?> livre<?= $nbDonsRecuperes > 1 ? 's' : '' ?> déjà donné<?= $nbDonsRecuperes > 1 ? 's' : '' ?> grâce à la communauté
            </span>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius: 20px;">
                <i class="bi bi-gift fs-1 mb-3" style="color:#274e13;"></i>
                <h3 class="fw-bold">Donner un livre</h3>
                <p class="text-muted">Publiez votre don en 2 minutes. Un lecteur le réserve et vient le chercher : il scanne le QR code de votre don, et c'est validé. Vous gagnez <strong><?= DON_POINTS ?> points</strong> par livre récupéré.</p>
                <div class="d-grid gap-2 mt-auto">
                    <a href="don_creer.php" class="btn btn-success rounded-pill fw-bold py-2">Proposer un don</a>
                    <a href="dons.php" class="btn btn-outline-success rounded-pill">Voir les <?= $nbDonsDisponibles ?> livre<?= $nbDonsDisponibles > 1 ? 's' : '' ?> à donner</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-radius: 20px;">
                <i class="bi bi-cash-coin fs-1 mb-3" style="color:#274e13;"></i>
                <h3 class="fw-bold">Revendre un livre</h3>
                <p class="text-muted">Fixez votre prix et publiez votre annonce : l'équipe la vérifie, puis les lecteurs vous envoient leurs demandes d'achat. Le paiement se fait entre vous, au moment de l'échange.</p>
                <div class="d-grid gap-2 mt-auto">
                    <a href="annonce_creer.php" class="btn btn-success rounded-pill fw-bold py-2">Vendre un livre</a>
                    <a href="catalogue.php?offre=particulier" class="btn btn-outline-success rounded-pill">Voir les annonces entre particuliers</a>
                </div>
            </div>
        </div>
    </div>

    <h4 class="fw-bold mt-5 mb-3">Comment ça marche ?</h4>
    <div class="row g-3 text-center">
        <?php foreach ([
            ['bi-pencil-square', 'Je publie', 'Le livre, son état, le lieu et les horaires de retrait.'],
            ['bi-bookmark-check', 'Un lecteur réserve', 'Vous recevez un mail avec son contact.'],
            ['bi-qr-code-scan', 'Il scanne le QR code', 'Au moment du retrait, ou il saisit le code à 6 caractères.'],
            ['bi-trophy', 'C\'est validé', 'Le don est archivé et vous gagnez vos points.'],
        ] as $i => [$icone, $titre, $texte]): ?>
            <div class="col-6 col-md-3">
                <div class="p-3 h-100 bg-white rounded-4 border">
                    <div class="fw-bold text-success small mb-1">Étape <?= $i + 1 ?></div>
                    <i class="bi <?= $icone ?> fs-2" style="color:#274e13;"></i>
                    <div class="fw-bold mt-2"><?= $titre ?></div>
                    <div class="small text-muted"><?= $texte ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
