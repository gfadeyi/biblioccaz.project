<?php
// "Mes avis" : liste des avis laissés par l'utilisateur connecté
require_once 'config.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT a.*, l.titre AS titre_livre, l.auteur, l.couverture,
           (SELECT COUNT(*) FROM avis_signalement s WHERE s.id_avis = a.id_avis AND s.traite = 0) AS nb_signalements
    FROM avis a INNER JOIN livre l ON l.id_livre = a.id_livre
    WHERE a.id_user = ?
    ORDER BY a.date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$mesAvis = $stmt->fetchAll();

$flash = $_SESSION['flash_avis'] ?? null;
unset($_SESSION['flash_avis']);
$csrf = jetonCsrf();

include 'header.php';
echo etoilesStyle();
?>

<div class="container mt-5 mb-5" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mes avis</h2>
        <a href="profil.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Mon compte</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> rounded-3"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!$mesAvis): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <i class="bi bi-chat-square-text text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Vous n'avez encore donné aucun avis.</h5>
            <p class="text-muted small">Ouvrez la fiche d'un livre que vous avez lu pour le noter.</p>
            <a href="catalogue.php" class="btn btn-success rounded-pill px-4 mt-2 mx-auto">Parcourir le catalogue</a>
        </div>
    <?php endif; ?>

    <?php foreach ($mesAvis as $a): ?>
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
            <div class="card-body d-flex gap-3">
                <a href="detail_livre.php?id=<?= $a['id_livre'] ?>#avis" class="flex-shrink-0">
                    <img src="img/<?= rawurlencode($a['couverture'] ?: 'default.png') ?>" class="rounded border" style="width: 60px; height: 85px; object-fit: cover;" alt="">
                </a>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <a href="detail_livre.php?id=<?= $a['id_livre'] ?>#avis" class="fw-bold text-dark text-decoration-none"><?= htmlspecialchars($a['titre_livre']) ?></a>
                            <div class="small text-muted"><?= htmlspecialchars($a['auteur']) ?></div>
                        </div>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($a['date_creation'])) ?><?= $a['date_modification'] ? ' · modifié le ' . date('d/m/Y', strtotime($a['date_modification'])) : '' ?></small>
                    </div>
                    <div class="mt-2"><?= etoiles((float) $a['note']) ?> <?php if ($a['titre']): ?><strong class="ms-1"><?= htmlspecialchars($a['titre']) ?></strong><?php endif; ?></div>
                    <p class="mb-2 mt-1 small" style="white-space: pre-line;"><?= htmlspecialchars($a['commentaire']) ?></p>

                    <?php if ($a['statut'] === 'masque'): ?>
                        <div class="alert alert-warning py-1 px-2 small mb-2">Masqué par la modération : cet avis n'est plus visible publiquement.</div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <a href="detail_livre.php?id=<?= $a['id_livre'] ?>#avis" class="btn btn-outline-success btn-sm rounded-pill"><i class="bi bi-pencil"></i> Modifier</a>
                        <form action="action_avis.php" method="POST" onsubmit="return confirm('Supprimer cet avis ?');">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="retour" value="mes_avis">
                            <input type="hidden" name="id_livre" value="<?= $a['id_livre'] ?>">
                            <input type="hidden" name="id_avis" value="<?= $a['id_avis'] ?>">
                            <button class="btn btn-outline-danger btn-sm rounded-pill"><i class="bi bi-trash"></i> Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
