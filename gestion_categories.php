<?php
// Administration des catégories et des tags (admin uniquement)
require_once 'config.php';
require_once 'categories_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

include 'header.php';

function blocListe(string $type, string $titre, array $elements, string $cle): void { ?>
    <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3" style="color:#274e13;"><?= $titre ?></h4>

            <form method="POST" class="d-flex gap-2 mb-4">
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
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="action" value="renommer">
                        <input type="hidden" name="id" value="<?= $el[$cle] ?>">
                        <input type="text" name="nom" class="form-control form-control-sm" value="<?= htmlspecialchars($el['nom']) ?>" maxlength="100" required>
                        <button class="btn btn-outline-secondary btn-sm" title="Enregistrer le nom"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <span class="badge bg-light text-dark border" title="Livres concernés"><?= $el['nb_livres'] ?> livre<?= $el['nb_livres'] > 1 ? 's' : '' ?></span>
                    <form method="POST" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($el['nom'])) ?> » ? Les livres concernés seront simplement retirés de ce classement.');">
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

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Catégories & tags</h2>
        <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-2"></i>Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success rounded-3"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="alert alert-danger rounded-3"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <?php if ($sansCategorie > 0): ?>
        <div class="alert alert-warning rounded-3 small">
            <i class="bi bi-info-circle me-1"></i> <?= $sansCategorie ?> livre<?= $sansCategorie > 1 ? 's n\'ont' : ' n\'a' ?> pas de catégorie.
            Classe-les depuis <a href="inventaire_admin.php">l'inventaire</a> (bouton « Modifier la fiche » sur la page du livre).
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6"><?php blocListe('categorie', 'Catégories', $categories, 'id_categorie'); ?></div>
        <div class="col-lg-6"><?php blocListe('tag', 'Tags (thèmes)', $tags, 'id_tag'); ?></div>
    </div>
</div>

<?php include 'footer.php'; ?>
