<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
?>
    </main>
</div>

<div class="modal fade" id="fenetreConfirmation" tabindex="-1" aria-labelledby="titreConfirmation" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="icone-confirmation" aria-hidden="true"><i class="bi bi-exclamation-lg"></i></div>
                <h2 class="modal-title h5 mb-2" id="titreConfirmation">Confirmer l'action</h2>
                <p class="mb-0 text-muted" id="texteConfirmation"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="boutonConfirmer">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<?php if (estEquipe()): ?>
<dialog class="recherche-rapide" id="rechercheRapide" aria-labelledby="rechercheRapideTitre">
    <form method="dialog" class="recherche-rapide__entete">
        <label for="rechercheRapideChamp" id="rechercheRapideTitre" class="visually-hidden">Aller à une page du back-office</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="rechercheRapideChamp" class="recherche-rapide__champ" placeholder="Aller à une page…" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="true" aria-controls="rechercheRapideListe" aria-autocomplete="list">
        <button class="recherche-rapide__fermer" aria-label="Fermer la recherche rapide"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </form>
    <ul class="recherche-rapide__liste" id="rechercheRapideListe" role="listbox" aria-label="Pages du back-office"></ul>
    <p class="recherche-rapide__vide" id="rechercheRapideVide" role="status" hidden>Aucune page ne correspond.</p>
    <p class="recherche-rapide__aide" aria-hidden="true"><kbd>↑</kbd><kbd>↓</kbd> parcourir · <kbd>Entrée</kbd> ouvrir · <kbd>Échap</kbd> fermer</p>
</dialog>
<?php endif; ?>
<script src="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.bundle.min.js', '../') ?>"></script>
<script src="admin.js?v=<?= filemtime(__DIR__ . '/admin.js') ?>"></script>
<?php if (!empty($scriptsPage)) echo $scriptsPage; ?>
</body>
</html>
