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

<script src="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.bundle.min.js', '../') ?>"></script>
<script src="admin.js?v=<?= filemtime(__DIR__ . '/admin.js') ?>"></script>
<?php if (!empty($scriptsPage)) echo $scriptsPage; ?>
</body>
</html>
