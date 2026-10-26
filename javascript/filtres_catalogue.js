document.addEventListener('DOMContentLoaded', function () {
    const formulaire = document.getElementById('formulaireFiltres');
    const boutonVoir = document.querySelector('[data-voir-livres]');
    if (!formulaire || !boutonVoir) return;

    let minuterie = null;
    let requeteEnCours = null;

    function texteVoirLivres(nombre) {
        if (nombre === 0) return 'Aucun livre';
        if (nombre === 1) return 'Voir le livre';
        return 'Voir les ' + nombre + ' livres';
    }

    function compter() {
        const parametres = new URLSearchParams(new FormData(formulaire));
        parametres.set('compter', '1');
        if (requeteEnCours) requeteEnCours.abort();
        requeteEnCours = new AbortController();
        boutonVoir.classList.add('en-calcul');
        fetch(window.location.pathname + '?' + parametres.toString(), { signal: requeteEnCours.signal })
            .then(function (reponse) { return reponse.json(); })
            .then(function (donnees) {
                boutonVoir.textContent = texteVoirLivres(donnees.total);
                boutonVoir.classList.remove('en-calcul');
            })
            .catch(function () {});
    }

    function planifier() {
        clearTimeout(minuterie);
        minuterie = setTimeout(compter, 300);
    }

    formulaire.addEventListener('change', function (evenement) {
        if (evenement.target.matches('[data-filtre-immediat]') && window.matchMedia('(min-width: 992px)').matches) {
            formulaire.submit();
            return;
        }
        planifier();
    });
    formulaire.addEventListener('input', planifier);
});
