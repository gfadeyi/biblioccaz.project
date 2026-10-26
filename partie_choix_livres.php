<?php
$racineImages = $racineImages ?? '';
$livresCoches = $livresCoches ?? [];
?>
<fieldset class="choix-livres" data-maximum="<?= EVT_LIVRES_MAX ?>">
    <legend class="form-label small fw-bold mb-1">Livres associés <span class="fw-normal text-muted">(facultatif, <?= EVT_LIVRES_MAX ?> au maximum)</span></legend>
    <p class="small text-muted mb-2">Ils seront présentés sur la page de l'événement.</p>
    <?php if (!$livresProposes): ?>
        <p class="small text-muted mb-0">Aucun livre n'est encore en ligne dans le catalogue.</p>
    <?php else: ?>
        <div class="choix-livres__outils">
            <input type="search" class="form-control form-control-sm choix-livres__filtre" placeholder="Filtrer par titre ou auteur" aria-label="Filtrer les livres" hidden>
            <span class="choix-livres__compteur small text-muted" aria-live="polite"><?= count($livresCoches) ?> / <?= EVT_LIVRES_MAX ?></span>
        </div>
        <div class="choix-livres__liste">
            <?php foreach ($livresProposes as $livre): ?>
                <?php $coche = in_array((int) $livre['id_livre'], $livresCoches, true); ?>
                <label class="choix-livres__livre" data-recherche="<?= htmlspecialchars(mb_strtolower($livre['titre'] . ' ' . $livre['auteur'])) ?>">
                    <input type="checkbox" class="form-check-input" name="livres[]" value="<?= (int) $livre['id_livre'] ?>" <?= $coche ? 'checked' : '' ?>>
                    <img src="<?= htmlspecialchars($racineImages . 'img/' . rawurlencode($livre['couverture'] ?: 'default.png')) ?>" alt="" loading="lazy">
                    <span class="choix-livres__texte">
                        <strong><?= htmlspecialchars($livre['titre']) ?></strong>
                        <small><?= htmlspecialchars($livre['auteur'] ?? '') ?><?php if (!empty($livre['est_a_moi'])): ?> · <em>votre livre</em><?php endif; ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</fieldset>
