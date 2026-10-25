<?php
// Champs "Catégorie" + "Tags" à inclure dans les formulaires de livre.
// Variables attendues : $pdo ; optionnelles : $categorieActuelle (int), $tagsActuels (tableau d'id).
require_once __DIR__ . '/categories_lib.php';

$listeCategories = listerCategories($pdo);
$listeTags = listerTags($pdo);
$categorieActuelle = $categorieActuelle ?? null;
$tagsActuels = $tagsActuels ?? [];
?>
<div class="col-md-6">
    <label class="form-label fw-bold small">Catégorie</label>
    <select name="id_categorie" class="form-select bg-light border-0">
        <option value="">— Aucune —</option>
        <?php foreach ($listeCategories as $c): ?>
            <option value="<?= $c['id_categorie'] ?>" <?= (int) $categorieActuelle === (int) $c['id_categorie'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label fw-bold small d-block">Tags</label>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($listeTags as $t): ?>
            <input type="checkbox" class="btn-check" name="tags[]" value="<?= $t['id_tag'] ?>"
                   id="tag_<?= $t['id_tag'] ?>" <?= in_array($t['id_tag'], $tagsActuels) ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-success rounded-pill" for="tag_<?= $t['id_tag'] ?>"><?= htmlspecialchars($t['nom']) ?></label>
        <?php endforeach; ?>
        <?php if (!$listeTags): ?>
            <span class="text-muted small">Aucun tag pour l'instant.</span>
        <?php endif; ?>
    </div>
</div>
