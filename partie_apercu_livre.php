<?php if (!isset($pdo)) { return; } ?>
<dialog class="apercu-livre" id="apercu-livre" aria-labelledby="apercu-titre">
    <div class="apercu-livre__entete">
        <span class="apercu-livre__libelle">Un premier regard</span>
        <button type="button" class="apercu-livre__fermer" data-fermer-apercu aria-label="Fermer l’aperçu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div id="apercu-contenu" class="apercu-livre__contenu" aria-busy="false">
        <h2 id="apercu-titre">Aperçu du livre</h2>
    </div>
</dialog>
<div class="notification-boutique" id="notification-boutique" role="status" aria-live="polite" hidden></div>
