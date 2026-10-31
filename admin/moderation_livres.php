<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../archivage_catalogue_lib.php';
exigerDroit('moderation');
$exclureArchives = archivageCatalogueDisponible($pdo) ? ' AND NOT EXISTS (SELECT 1 FROM livre_archive ar WHERE ar.id_livre = l.id_livre AND ar.actif = 1)' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('moderation_livres.php');
    $id = (int) ($_POST['id_livre'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'modifier_description') {
        $pdo->prepare("UPDATE livre SET description = ? WHERE id_livre = ?")->execute([trim($_POST['description'] ?? ''), $id]);
        insertLog('MODERATION', "Modification de la description du livre #$id");
        rediriger('moderation_livres.php', 'success', 'Description enregistrée.');
    }

    if ($action === 'valider') {
        $pdo->prepare("UPDATE livre l SET l.is_valide = 1 WHERE l.id_livre = ? AND l.is_valide = 0 $exclureArchives AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.id_proprietaire IS NOT NULL)")->execute([$id]);
        insertLog('MODERATION', "Validation du livre #$id");
        rediriger('moderation_livres.php', 'success', 'Fiche validée : elle est maintenant visible dans le catalogue.');
    }

    if ($action === 'refuser') {
        if (!aLeDroit('admin')) {
            rediriger('moderation_livres.php', 'warning', "Seul un administrateur peut refuser (archiver) une fiche.");
        }
        [$type, $message] = changerArchivageLivre($pdo, $id, (int) $_SESSION['user_id'], true);
        rediriger('moderation_livres.php', $type, $message);
    }

    rediriger('moderation_livres.php');
}

$livres = $pdo->query("
    SELECT l.*, u.pseudo AS pseudo_auteur
    FROM livre l LEFT JOIN user u ON u.id = l.id_user_auteur
    WHERE l.is_valide = 0 $exclureArchives AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.id_proprietaire IS NOT NULL) ORDER BY l.id_livre DESC")->fetchAll();

$titrePage = 'Fiches à valider';
$pageActive = 'moderation_livres';
include __DIR__ . '/_haut.php';
?>

<p class="text-muted">Les fiches proposées par les auteurs n'apparaissent dans le catalogue qu'une fois validées. Les fiches créées avec une annonce de vente se valident depuis <a href="annonces.php">Annonces</a>.</p>

<?php if (!$livres): ?>
    <div class="carte p-5 text-center">
        <i class="bi bi-check2-circle fs-1 text-success"></i>
        <p class="text-muted mb-0 mt-2">Aucune fiche en attente de validation.</p>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($livres as $l): ?>
        <div class="col-xl-6">
            <div class="carte p-3 h-100">
                <div class="d-flex gap-3">
                    <img src="<?= imageLivre($l['couverture']) ?>" alt="" class="rounded shadow-sm flex-shrink-0" style="width: 90px; height: 128px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-1"><?= e($l['titre']) ?></h2>
                        <p class="text-muted small mb-2">
                            par <?= e($l['auteur']) ?>
                            <?php if ($l['pseudo_auteur']): ?> · proposé par <strong>@<?= e($l['pseudo_auteur']) ?></strong><?php endif; ?>
                            <?php if ($l['isbn']): ?> · ISBN <?= e($l['isbn']) ?><?php endif; ?>
                        </p>

                        <form method="POST" class="mb-2">
                            <?= champCsrf() ?>
                            <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                            <input type="hidden" name="action" value="modifier_description">
                            <textarea name="description" class="form-control form-control-sm mb-1" rows="3" placeholder="Aucune description fournie..."><?= e($l['description']) ?></textarea>
                            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-save"></i> Enregistrer la description</button>
                        </form>

                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST">
                                <?= champCsrf() ?>
                                <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                <input type="hidden" name="action" value="valider">
                                <button class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg"></i> Valider</button>
                            </form>
                            <?php if (aLeDroit('catalogue')): ?>
                                <a href="livre.php?id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier la fiche</a>
                            <?php endif; ?>
                            <?php if (aLeDroit('admin')): ?>
                                <form method="POST" data-confirmer="Refuser et archiver cette fiche en conservant son historique ?">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Refuser</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
