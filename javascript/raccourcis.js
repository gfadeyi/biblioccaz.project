document.addEventListener('DOMContentLoaded', function () {
    const defilement = document.querySelector('.defilement-raccourcis');
    if (!defilement) return;

    function majFondus() {
        const deborde = defilement.scrollWidth > defilement.clientWidth + 1;
        defilement.classList.toggle('fondu-droite', deborde && defilement.scrollLeft + defilement.clientWidth < defilement.scrollWidth - 1);
        defilement.classList.toggle('fondu-gauche', deborde && defilement.scrollLeft > 1);
    }

    const actif = defilement.querySelector('.actif');
    if (actif) {
        defilement.scrollLeft = actif.offsetLeft - defilement.offsetLeft - 16;
    }
    defilement.addEventListener('scroll', majFondus, { passive: true });
    window.addEventListener('resize', majFondus);
    majFondus();
});
