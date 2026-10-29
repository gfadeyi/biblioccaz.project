<?php
$valeurAuteur = fn(string $champ) => htmlspecialchars((string) ($valeursAuteur[$champ] ?? ''));
$lignesOuvrages = array_values($ouvragesAuteur ?? []);
$nbLignesVisibles = min(AUTEUR_OUVRAGES_MAX, max(AUTEUR_OUVRAGES_AFFICHES_MIN, count($lignesOuvrages) + 1));
?>
<fieldset class="fiche-auteur-form__bloc">
    <legend>Votre présentation</legend>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-bold small" for="nom_plume">Nom de plume *</label>
            <input type="text" id="nom_plume" name="nom_plume" class="form-control" minlength="2" maxlength="80" value="<?= $valeurAuteur('nom_plume') ?>" required aria-describedby="aide-nom-plume">
            <p class="form-text" id="aide-nom-plume">Le nom qui apparaîtra sur votre page publique.</p>
        </div>
        <div class="col-12">
            <label class="form-label fw-bold small" for="biographie">Biographie *</label>
            <textarea id="biographie" name="biographie" class="form-control" rows="6" minlength="50" maxlength="3000" required aria-describedby="aide-biographie"><?= $valeurAuteur('biographie') ?></textarea>
            <p class="form-text" id="aide-biographie">De 50 à 3 000 caractères : votre parcours, vos thèmes, ce qui vous fait écrire.</p>
        </div>
        <?php if (!empty($avecMotivation)): ?>
            <div class="col-12">
                <label class="form-label fw-bold small" for="motivation">Pourquoi rejoindre BIBLIOccaz ? *</label>
                <textarea id="motivation" name="motivation" class="form-control" rows="4" minlength="30" maxlength="2000" required aria-describedby="aide-motivation"><?= $valeurAuteur('motivation') ?></textarea>
                <p class="form-text" id="aide-motivation">De 30 à 2 000 caractères. Seule l'équipe lit ce texte.</p>
            </div>
        <?php endif; ?>
    </div>
</fieldset>

<fieldset class="fiche-auteur-form__bloc">
    <legend>Ouvrages publiés</legend>
    <p class="form-text mt-0">Au moins un ouvrage, <?= AUTEUR_OUVRAGES_MAX ?> au plus. Seul le titre est obligatoire ; l'ISBN est vérifié s'il est indiqué.</p>
    <ol class="liste-ouvrages-form" data-ouvrages>
        <?php for ($i = 0; $i < AUTEUR_OUVRAGES_MAX; $i++): ?>
            <?php $ouvrage = $lignesOuvrages[$i] ?? []; $numero = $i + 1; ?>
            <li class="ouvrage-form" <?= $i >= $nbLignesVisibles ? 'hidden' : '' ?>>
                <span class="ouvrage-form__numero" aria-hidden="true"><?= $numero ?></span>
                <div class="row g-2 flex-grow-1">
                    <div class="col-md-5">
                        <label class="form-label small" for="ouvrage-titre-<?= $numero ?>">Titre<?= $i === 0 ? ' *' : '' ?> <span class="visually-hidden">de l'ouvrage <?= $numero ?></span></label>
                        <input type="text" id="ouvrage-titre-<?= $numero ?>" name="ouvrage_titre[]" class="form-control form-control-sm" maxlength="200" value="<?= htmlspecialchars((string) ($ouvrage['titre'] ?? '')) ?>" <?= $i === 0 ? 'required' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small" for="ouvrage-editeur-<?= $numero ?>">Éditeur <span class="visually-hidden">de l'ouvrage <?= $numero ?></span></label>
                        <input type="text" id="ouvrage-editeur-<?= $numero ?>" name="ouvrage_editeur[]" class="form-control form-control-sm" maxlength="120" value="<?= htmlspecialchars((string) ($ouvrage['editeur'] ?? '')) ?>">
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small" for="ouvrage-annee-<?= $numero ?>">Année <span class="visually-hidden">de l'ouvrage <?= $numero ?></span></label>
                        <input type="text" id="ouvrage-annee-<?= $numero ?>" name="ouvrage_annee[]" class="form-control form-control-sm" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" value="<?= htmlspecialchars((string) ($ouvrage['annee'] ?? '')) ?>">
                    </div>
                    <div class="col-8 col-md-3">
                        <label class="form-label small" for="ouvrage-isbn-<?= $numero ?>">ISBN <span class="visually-hidden">de l'ouvrage <?= $numero ?></span></label>
                        <input type="text" id="ouvrage-isbn-<?= $numero ?>" name="ouvrage_isbn[]" class="form-control form-control-sm" maxlength="17" inputmode="numeric" placeholder="978…" value="<?= htmlspecialchars((string) ($ouvrage['isbn'] ?? '')) ?>">
                    </div>
                </div>
            </li>
        <?php endfor; ?>
    </ol>
    <button type="button" class="bouton bouton-discret bouton-petit" data-ajouter-ouvrage hidden><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter un ouvrage</button>
</fieldset>

<fieldset class="fiche-auteur-form__bloc">
    <legend>Liens et photo</legend>
    <div class="row g-3">
        <?php foreach (AUTEUR_LIENS as $champLien => $libelleLien): ?>
            <div class="col-md-4">
                <label class="form-label fw-bold small" for="<?= $champLien ?>"><?= $libelleLien ?></label>
                <input type="url" id="<?= $champLien ?>" name="<?= $champLien ?>" class="form-control" maxlength="255" placeholder="https://…" value="<?= $valeurAuteur($champLien) ?>">
            </div>
        <?php endforeach; ?>
        <div class="col-12">
            <label class="form-label fw-bold small" for="photo">Photo (facultative : JPG, PNG ou WebP, 2 Mo maximum)</label>
            <?php if (!empty($photoAuteurActuelle)): ?>
                <div class="photo-auteur-actuelle">
                    <img src="<?= htmlspecialchars($photoAuteurActuelle) ?>" alt="Photo actuelle">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="retirer_photo" value="1" id="retirer_photo">
                        <label class="form-check-label small" for="retirer_photo">Retirer la photo actuelle</label>
                    </div>
                </div>
            <?php endif; ?>
            <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
    </div>
</fieldset>
