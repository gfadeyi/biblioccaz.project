document.addEventListener('DOMContentLoaded', function () {
    const liste = document.querySelector('[data-ouvrages]');
    const bouton = document.querySelector('[data-ajouter-ouvrage]');
    if (!liste || !bouton) return;

    function lignesCachees() {
        return liste.querySelectorAll('.ouvrage-form[hidden]');
    }

    bouton.hidden = lignesCachees().length === 0;
    bouton.addEventListener('click', function () {
        const suivante = lignesCachees()[0];
        if (!suivante) return;
        suivante.hidden = false;
        suivante.querySelector('input').focus();
        bouton.hidden = lignesCachees().length === 0;
    });
});
