document.addEventListener('DOMContentLoaded', () => {
    const bandeau = document.querySelector('[data-bandeau-demo]');
    if (!bandeau) return;
    bandeau.querySelector('[data-fermer-bandeau-demo]').addEventListener('click', () => {
        document.cookie = bandeau.dataset.bandeauDemo + '=1; max-age=' + 60 * 60 * 24 * 30 + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        bandeau.remove();
        document.getElementById('barre-recherche')?.focus({preventScroll: true});
    });
});
