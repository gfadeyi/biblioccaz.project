document.querySelectorAll('[data-reponses-types]').forEach(function (choix) {
    const formulaire = choix.closest('form');
    const zone = formulaire.querySelector('textarea[name="message"]');
    const resoudre = formulaire.querySelector('input[name="resoudre"]');
    choix.addEventListener('change', function () {
        const option = choix.selectedOptions[0];
        if (!option || !option.dataset.texte) {
            return;
        }
        const texte = option.dataset.texte;
        if (zone.value.trim() !== '' && zone.value.trim() !== texte.trim() && !window.confirm('Remplacer le texte déjà saisi par cette réponse type ?')) {
            choix.value = '';
            return;
        }
        zone.value = texte;
        if (resoudre && option.dataset.resoudre === '1') {
            resoudre.checked = true;
        }
        choix.value = '';
        zone.focus();
        const position = texte.indexOf(': \n') !== -1 ? texte.indexOf(': \n') + 3 : texte.length;
        zone.setSelectionRange(position, position);
    });
});
