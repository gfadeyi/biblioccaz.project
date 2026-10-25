<?php
require_once 'config.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT l.id_livre, l.titre, l.auteur, l.couverture, f.date_ajout,
           (SELECT COALESCE(SUM(e.quantite), 0) FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.is_disponible = 1 AND e.quantite > 0) AS stock,
           (SELECT MIN(e.prix) FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.is_disponible = 1 AND e.quantite > 0) AS prix_min,
           (SELECT COUNT(*) FROM alerte_stock a WHERE a.id_livre = l.id_livre AND a.id_user = f.id_user AND a.date_envoi IS NULL) AS alerte
    FROM favori f
    INNER JOIN livre l ON l.id_livre = f.id_livre
    WHERE f.id_user = ? AND l.is_valide = 1
    ORDER BY f.date_ajout DESC");
$stmt->execute([$idUser]);
$favoris = $stmt->fetchAll();
$csrf = jetonCsrf();

$flash = $_SESSION['flash_compte'] ?? null;
unset($_SESSION['flash_compte']);

function boutonCompte(string $action, int $idLivre, string $csrf, string $libelle, string $classe, string $retour): string
{
    return "<form action=\"compte_action.php\" method=\"POST\" class=\"d-inline\">
        <input type=\"hidden\" name=\"csrf\" value=\"$csrf\">
        <input type=\"hidden\" name=\"action\" value=\"$action\">
        <input type=\"hidden\" name=\"id_livre\" value=\"$idLivre\">
        <input type=\"hidden\" name=\"retour\" value=\"$retour\">
        <button class=\"btn btn-sm $classe rounded-pill\">$libelle</button>
    </form>";
}

include 'header.php';
?>

<div class="container my-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color:#274e13;"><i class="bi bi-heart"></i> Ma liste d'envies</h1>
            <p class="text-muted mb-0"><?= count($favoris) ?> livre<?= count($favoris) > 1 ? 's' : '' ?> mis de côté</p>
        </div>
        <a href="alertes.php" class="btn btn-outline-success rounded-pill"><i class="bi bi-bell"></i> Mes alertes</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if (!$favoris): ?>
        <div class="card border-0 shadow-sm p-5 text-center" style="border-radius: 15px;">
            <i class="bi bi-heart text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Votre liste d'envies est vide.</h5>
            <p class="text-muted small mb-0">Sur la page d'un livre, cliquez sur le cœur pour le garder ici.</p>
            <a href="catalogue.php" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Parcourir le catalogue</a>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-3">
        <?php foreach ($favoris as $f): ?>
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <a href="detail_livre.php?id=<?= $f['id_livre'] ?>">
                        <img src="img/<?= rawurlencode($f['couverture'] ?: 'default.png') ?>" alt="" class="rounded border" style="width: 60px; height: 85px; object-fit: cover;">
                    </a>
                    <div class="flex-grow-1" style="min-width: 180px;">
                        <a href="detail_livre.php?id=<?= $f['id_livre'] ?>" class="fw-bold text-dark text-decoration-none"><?= htmlspecialchars($f['titre']) ?></a>
                        <div class="small text-muted"><?= htmlspecialchars($f['auteur']) ?></div>
                        <?php if ($f['stock'] > 0): ?>
                            <div class="small mt-1"><span class="text-success fw-bold">En stock</span> · dès <?= number_format($f['prix_min'], 2, ',', ' ') ?> €</div>
                        <?php else: ?>
                            <div class="small mt-1 text-danger">Épuisé pour le moment<?= $f['alerte'] ? ' · <span class="text-success"><i class="bi bi-bell-fill"></i> alerte activée</span>' : '' ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($f['stock'] > 0): ?>
                            <a href="detail_livre.php?id=<?= $f['id_livre'] ?>" class="btn btn-sm btn-success rounded-pill">Voir les offres</a>
                        <?php elseif (!$f['alerte']): ?>
                            <?= boutonCompte('alerte_ajouter', (int) $f['id_livre'], $csrf, '<i class="bi bi-bell"></i> M\'alerter', 'btn-outline-success', 'wishlist') ?>
                        <?php endif; ?>
                        <?= boutonCompte('favori_retirer', (int) $f['id_livre'], $csrf, '<i class="bi bi-x-lg"></i> Retirer', 'btn-outline-secondary', 'wishlist') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
