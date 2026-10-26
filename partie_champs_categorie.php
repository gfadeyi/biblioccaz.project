<?php
require_once __DIR__ . '/categories_lib.php';

$arbreCategoriesLivre = arbreCategories($pdo);
$listeTags = listerTags($pdo);
$categorieActuelle = $categorieActuelle ?? null;
$tagsActuels = $tagsActuels ?? [];
?>
<div class="col-md-6">
    <label for="champ-categorie" class="form-label fw-bold small">Catégorie</label>
    <select id="champ-categorie" name="id_categorie" class="form-select bg-light border-0">
        <option value="">— Aucune —</option>
        <?= optionsCategories($arbreCategoriesLivre, $categorieActuelle ?? '') ?>
    </select>
</div>
<div class="col-md-6">
    <p class="form-label fw-bold small d-block mb-2">Tags</p>
    <?php foreach (tagsParFamille($listeTags) as $cleFamille => $tagsFamille): ?>
        <fieldset class="mb-2">
            <legend class="small text-muted mb-1" style="font-size: .8rem;"><?= htmlspecialchars(FAMILLES_TAGS[$cleFamille]) ?></legend>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($tagsFamille as $t): ?>
                    <input type="checkbox" class="btn-check" name="tags[]" value="<?= $t['id_tag'] ?>"
                           id="tag_<?= $t['id_tag'] ?>" <?= in_array($t['id_tag'], $tagsActuels) ? 'checked' : '' ?>>
                    <label class="btn btn-sm btn-outline-success rounded-pill" for="tag_<?= $t['id_tag'] ?>"><?= htmlspecialchars($t['nom']) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach; ?>
    <?php if (!$listeTags): ?>
        <span class="text-muted small">Aucun tag pour l'instant.</span>
    <?php endif; ?>
</div>
