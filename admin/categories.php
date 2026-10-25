<?php
// Administration des catégories et des tags (admin uniquement)
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../categories_lib.php';
exigerDroit('admin');

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('categories.php');
    $type = $_POST['type'] ?? '';           // 'categorie' ou 'tag'
    $action = $_POST['action'] ?? '';       // 'ajouter', 'renommer', 'supprimer'
    $table = $type === 'categorie' ? 'categorie' : ($type === 'tag' ? 'tag' : null);
    $cle = $type === 'categorie' ? 'id_categorie' : 'id_tag';
    $nom = trim($_POST['nom'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    try {
        if (!$table) {
            $erreur = "Action inconnue.";
        } elseif ($action === 'ajouter' || $action === 'renommer') {
            if ($nom === '' || mb_strlen($nom) > 100) {
                $erreur = "Le nom doit contenir entre 1 et 100 caractères.";
            } elseif ($action === 'ajouter') {
                $ordre = $type === 'categorie' ? (int) $pdo->query("SELECT COALESCE(MAX(ordre), 0) + 1 FROM categorie")->fetchColumn() : null;
                if ($type === 'categorie') {
                    $pdo->prepare("INSERT INTO categorie (nom, slug, ordre) VALUES (?, ?, ?)")->execute([$nom, creerSlug($nom), $ordre]);
                } else {
                    $pdo->prepare("INSERT INTO tag (nom, slug) VALUES (?, ?)")->execute([$nom, creerSlug($nom)]);
                }
                insertLog('CATALOGUE', "Ajout $type : $nom");
                $message = ucfirst($type) . " « $nom » ajouté" . ($type === 'categorie' ? 'e' : '') . ".";
            } else {
                // Renommer garde le même slug : les liens existants restent valides
                $pdo->prepare("UPDATE $table SET nom = ? WHERE $cle = ?")->execute([$nom, $id]);
                insertLog('CATALOGUE', "Renommage $type #$id : $nom");
                $message = "Modification enregistrée.";
            }
        } elseif ($action === 'supprimer') {
            // Les livres liés perdent simplement leur catégorie/tag (clés étrangères SET NULL / CASCADE)
            $pdo->prepare("DELETE FROM $table WHERE $cle = ?")->execute([$id]);
            insertLog('CATALOGUE', "Suppression $type #$id");
            $message = "Suppression effectuée.";
        }
    } catch (PDOException $e) {
        $erreur = $e->getCode() == 23000 ? "Ce nom existe déjà." : "Erreur : " . $e->getMessage();
    }
    if ($erreur === '') {
        rediriger('categories.php', 'success', $message);
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(l.id_livre) AS nb_livres
    FROM categorie c LEFT JOIN livre l ON l.id_categorie = c.id_categorie
    GROUP BY c.id_categorie ORDER BY c.ordre, c.nom")->fetchAll();
$tags = $pdo->query("
    SELECT t.*, COUNT(lt.id_livre) AS nb_livres
    FROM tag t LEFT JOIN livre_tag lt ON lt.id_tag = t.id_tag
    GROUP BY t.id_tag ORDER BY t.nom")->fetchAll();
$sansCategorie = (int) $pdo->query("SELECT COUNT(*) FROM livre WHERE id_categorie IS NULL")->fetchColumn();

$titrePage = 'Catégories & tags';
$pageActive = 'categories';
include __DIR__ . '/_haut.php';

function blocListe(string $type, string $titre, array $elements, string $cle): void { ?>
    <div class="carte h-100">
        <div class="p-4">
            <h2 class="h5 fw-bold mb-3" style="color:#274e13;"><?= $titre ?></h2>

            <form method="POST" class="d-flex gap-2 mb-4">
                <?= champCsrf() ?>
                <input type="hidden" name="type" value="<?= $type ?>">
                <input type="hidden" name="action" value="ajouter">
                <input type="text" name="nom" class="form-control form-control-sm" placeholder="Nouveau nom..." maxlength="100" required>
                <button class="btn btn-success btn-sm rounded-pill px-3 text-nowrap"><i class="bi bi-plus-lg"></i> Ajouter</button>
            </form>

            <?php if (!$elements): ?>
                <p class="text-muted small">Aucun élément.</p>
            <?php endif; ?>

            <?php foreach ($elements as $el): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <form method="POST" class="d-flex gap-2 flex-grow-1">
                        <?= champCsrf() ?>
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="action" value="renommer">
                        <input type="hidden" name="id" value="<?= $el[$cle] ?>">
                        <input type="text" name="nom" class="form-control form-control-sm" value="<?= htmlspecialchars($el['nom']) ?>" maxlength="100" required>
                        <button class="btn btn-outline-secondary btn-sm" title="Enregistrer le nom"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <span class="badge bg-light text-dark border" title="Livres concernés"><?= $el['nb_livres'] ?> livre<?= $el['nb_livres'] > 1 ? 's' : '' ?></span>
                    <form method="POST" data-confirmer="Supprimer « <?= e($el['nom']) ?> » ? Les livres concernés seront simplement retirés de ce classement.">
                        <?= champCsrf() ?>
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $el[$cle] ?>">
                        <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php }
?>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($sansCategorie > 0): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-info-circle me-1"></i> <?= $sansCategorie ?> livre<?= $sansCategorie > 1 ? 's n\'ont' : ' n\'a' ?> pas de catégorie.
        Classez-les depuis <a href="inventaire.php">l'inventaire</a> (bouton « Modifier la fiche »).
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6"><?php blocListe('categorie', 'Catégories', $categories, 'id_categorie'); ?></div>
    <div class="col-lg-6"><?php blocListe('tag', 'Tags (thèmes)', $tags, 'id_tag'); ?></div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
