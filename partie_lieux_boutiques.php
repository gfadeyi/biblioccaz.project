<?php require_once __DIR__ . '/boutiques_lib.php'; ?>
<datalist id="lieux-reseau-boutiques">
    <?php foreach (BOUTIQUES as $boutique): ?><option value="BIBLIOccaz, <?= htmlspecialchars($boutique['ville'], ENT_QUOTES, 'UTF-8') ?>"></option><?php endforeach; ?>
</datalist>
<p class="form-text">Suggestions du réseau fictif BibliOccaz. Un lieu partenaire reste possible : indiquez toujours le lieu exact de la rencontre.</p>
