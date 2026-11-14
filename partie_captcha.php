<?php
require_once __DIR__ . '/captcha_lib.php';
?>
<?php if (captchaRecent()): ?>
    <p class="captcha-deja-valide"><i class="bi bi-shield-check" aria-hidden="true"></i> Vérification de sécurité déjà réussie : elle reste valable 30 minutes sur cet appareil.</p>
<?php else: ?>
    <div class="mb-4">
        <span class="form-label d-block">Vérification de sécurité</span>
        <button type="button" class="bouton-captcha" data-bs-toggle="modal" data-bs-target="#captchaModal" id="captchaTriggerBtn">
            <i class="bi bi-puzzle" aria-hidden="true"></i> Cliquez pour vérifier
        </button>
        <input type="hidden" name="captcha_token" id="captchaToken">
        <div class="form-text">Une seule fois : la vérification reste valable 30 minutes.</div>
    </div>
    <div class="modal fade" id="captchaModal" tabindex="-1" aria-labelledby="titreCaptcha" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 fw-bold" id="titreCaptcha">Reconstituez l'image</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted small mb-3">Cliquez sur deux cases pour échanger leurs places.</p>
                    <div id="captchaGrid"></div>
                    <p id="captchaMessage" class="mt-3 small fw-bold mb-0" style="min-height: 20px;" aria-live="polite"></p>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= urlStatique('javascript/captcha_puzzle.js') ?>"></script>
    <script>document.addEventListener('DOMContentLoaded', function () { initCaptchaPuzzle(''); });</script>
<?php endif; ?>
