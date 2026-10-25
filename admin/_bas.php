<?php
// Ce fichier est inclus par les pages du back-office : on refuse de l'ouvrir directement.
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Demande confirmation avant les actions sensibles : <form data-confirmer="Texte de la question">
document.querySelectorAll('form[data-confirmer]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (!confirm(form.dataset.confirmer)) e.preventDefault();
    });
});
</script>
<?php if (!empty($scriptsPage)) echo $scriptsPage; ?>
</body>
</html>
