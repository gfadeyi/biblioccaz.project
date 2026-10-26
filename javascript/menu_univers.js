document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('.menu-univers');
    if (!menu) return;

    const grandEcran = window.matchMedia('(min-width: 992px)');
    const elements = Array.from(menu.querySelectorAll('.univers-menu'));

    function ouvrir(element) {
        elements.forEach(function (autre) {
            const estCible = autre === element;
            autre.classList.toggle('ouvert', estCible);
            autre.querySelector('.bouton-univers').setAttribute('aria-expanded', estCible ? 'true' : 'false');
        });
    }

    function fermer(element) {
        element.classList.remove('ouvert');
        element.querySelector('.bouton-univers').setAttribute('aria-expanded', 'false');
    }

    elements.forEach(function (element) {
        const bouton = element.querySelector('.bouton-univers');
        bouton.addEventListener('click', function () {
            if (!grandEcran.matches && element.classList.contains('ouvert')) {
                fermer(element);
            } else {
                ouvrir(element);
            }
        });
        bouton.addEventListener('mouseenter', function () {
            if (grandEcran.matches) ouvrir(element);
        });
        bouton.addEventListener('focus', function () {
            if (grandEcran.matches) ouvrir(element);
        });
    });

    if (grandEcran.matches && !menu.querySelector('.univers-menu.ouvert') && elements[0]) {
        ouvrir(elements[0]);
    }
});
