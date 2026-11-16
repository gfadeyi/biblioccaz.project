document.addEventListener('DOMContentLoaded', function () {
    const zone = document.querySelector('[data-tiroir-notifications]');
    if (!zone) return;
    const declencheur = zone.querySelector('a.icone-entete');
    const tiroir = zone.querySelector('.menu-notifications');
    const voile = zone.querySelector('.voile-tiroir');
    const boutonFermer = zone.querySelector('.menu-notifications__fermer button');
    const ecranLarge = window.matchMedia('(min-width: 992px)');

    function estOuvert() {
        return zone.classList.contains('tiroir-ouvert');
    }

    function ouvrir() {
        zone.classList.add('tiroir-ouvert');
        voile.hidden = false;
        document.body.classList.add('tiroir-notifications-actif');
        declencheur.setAttribute('aria-expanded', 'true');
        boutonFermer.focus();
    }

    function fermer(rendreLeFocus) {
        if (!estOuvert()) return;
        zone.classList.remove('tiroir-ouvert');
        voile.hidden = true;
        document.body.classList.remove('tiroir-notifications-actif');
        declencheur.setAttribute('aria-expanded', 'false');
        if (rendreLeFocus) declencheur.focus();
    }

    declencheur.addEventListener('click', function (evenement) {
        if (ecranLarge.matches) return;
        evenement.preventDefault();
        estOuvert() ? fermer(true) : ouvrir();
    });
    voile.addEventListener('click', function () { fermer(true); });
    boutonFermer.addEventListener('click', function () { fermer(true); });

    window.addEventListener('keydown', function (evenement) {
        if (!estOuvert()) return;
        if (evenement.key === 'Escape') {
            evenement.preventDefault();
            evenement.stopPropagation();
            fermer(true);
            return;
        }
        if (evenement.key !== 'Tab') return;
        const controles = [...tiroir.querySelectorAll('a[href], button:not(:disabled)')].filter(element => element.getClientRects().length);
        if (!controles.length) return;
        const premier = controles[0];
        const dernier = controles[controles.length - 1];
        if (evenement.shiftKey && document.activeElement === premier) { evenement.preventDefault(); dernier.focus(); }
        else if (!evenement.shiftKey && document.activeElement === dernier) { evenement.preventDefault(); premier.focus(); }
    }, true);

    ecranLarge.addEventListener('change', function () { fermer(false); });
});
