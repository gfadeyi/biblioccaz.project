document.addEventListener('DOMContentLoaded', function () {
    const zone = document.querySelector('[data-menu-compte]');
    if (!zone) return;
    const declencheur = zone.querySelector('.icone-entete');
    const menu = zone.querySelector('.menu-entete');
    const voile = zone.querySelector('.voile-menu-compte');
    const boutonFermer = zone.querySelector('.bouton-fermer-menu');
    const ecranLarge = window.matchMedia('(min-width: 992px)');

    function ajusterHauteur() {
        const basDeclencheur = zone.getBoundingClientRect().bottom;
        menu.style.setProperty('--hauteur-dispo', Math.max(220, window.innerHeight - basDeclencheur - 16) + 'px');
    }

    function panneauOuvert() {
        return zone.classList.contains('panneau-ouvert');
    }

    function ouvrirPanneau() {
        zone.classList.add('panneau-ouvert');
        voile.hidden = false;
        document.body.classList.add('menu-compte-actif');
        declencheur.setAttribute('aria-expanded', 'true');
        boutonFermer.focus();
    }

    function fermerPanneau(rendreLeFocus) {
        if (!panneauOuvert()) return;
        zone.classList.remove('panneau-ouvert');
        voile.hidden = true;
        document.body.classList.remove('menu-compte-actif');
        declencheur.setAttribute('aria-expanded', 'false');
        if (rendreLeFocus) declencheur.focus();
    }

    declencheur.addEventListener('click', function (evenement) {
        if (ecranLarge.matches) return;
        evenement.preventDefault();
        panneauOuvert() ? fermerPanneau(true) : ouvrirPanneau();
    });
    voile.addEventListener('click', function () { fermerPanneau(true); });
    boutonFermer.addEventListener('click', function () { fermerPanneau(true); });

    zone.addEventListener('mouseenter', ajusterHauteur);
    zone.addEventListener('focusin', function () {
        ajusterHauteur();
        if (ecranLarge.matches) declencheur.setAttribute('aria-expanded', 'true');
    });
    zone.addEventListener('focusout', function (evenement) {
        if (ecranLarge.matches && !zone.contains(evenement.relatedTarget)) declencheur.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('keydown', function (evenement) {
        if (evenement.key !== 'Escape') return;
        if (panneauOuvert()) {
            fermerPanneau(true);
        } else if (zone.contains(document.activeElement)) {
            declencheur.focus();
            zone.classList.add('menu-replie');
        }
    });
    zone.addEventListener('mouseleave', function () { zone.classList.remove('menu-replie'); });
    declencheur.addEventListener('blur', function () { zone.classList.remove('menu-replie'); });

    window.addEventListener('resize', ajusterHauteur, { passive: true });
    ecranLarge.addEventListener('change', function () { fermerPanneau(false); });
    ajusterHauteur();
});
