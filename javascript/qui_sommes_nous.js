document.querySelectorAll('[data-histoire-repliable]').forEach(function (section) {
    const bouton = section.querySelector('.qsn-lire-suite');
    const libelle = bouton.querySelector('span');
    const petitEcran = window.matchMedia('(max-width: 767.98px)');

    function appliquer() {
        const replier = petitEcran.matches && !section.classList.contains('qsn-histoire--depliee');
        section.classList.toggle('qsn-histoire--repliee', replier);
        bouton.hidden = !petitEcran.matches;
    }

    bouton.addEventListener('click', function () {
        const deplier = !section.classList.contains('qsn-histoire--depliee');
        section.classList.toggle('qsn-histoire--depliee', deplier);
        bouton.setAttribute('aria-expanded', deplier ? 'true' : 'false');
        libelle.textContent = deplier ? 'Réduire' : 'Lire la suite';
        appliquer();
    });
    petitEcran.addEventListener('change', appliquer);
    appliquer();
});
