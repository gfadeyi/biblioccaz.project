(function () {
    const autonome = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
    if (!autonome) return;
    document.documentElement.classList.add('application-autonome');
    const manifeste = document.querySelector('link[rel="manifest"]');
    const portee = new URL('./', manifeste ? manifeste.href : window.location.href).href;

    function lienInterne(lien) {
        if (!lien.href || lien.hasAttribute('download')) return false;
        const adresse = new URL(lien.href, window.location.href);
        if (!['http:', 'https:'].includes(adresse.protocol) || adresse.origin !== window.location.origin) return false;
        return adresse.href.startsWith(portee);
    }

    function memeDocument(lien) {
        const adresse = new URL(lien.href, window.location.href);
        return adresse.hash !== '' && adresse.pathname === window.location.pathname && adresse.search === window.location.search;
    }

    window.addEventListener('click', function (evenement) {
        if (evenement.defaultPrevented || evenement.button !== 0 || evenement.metaKey || evenement.ctrlKey || evenement.shiftKey || evenement.altKey) return;
        const lien = evenement.target.closest('a[href]');
        if (!lien || lien.closest('[data-bs-toggle], [role="button"]') || !lienInterne(lien) || memeDocument(lien)) return;
        evenement.preventDefault();
        window.location.assign(lien.href);
    });
}());
