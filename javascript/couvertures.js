(() => {
    const imageDefaut = new URL(document.currentScript.dataset.defaut, document.baseURI).href;
    const estCouverture = image => image instanceof HTMLImageElement && (image.alt.startsWith('Couverture de ') || /^https:\/\/covers\.openlibrary\.org\/b\/id\/[1-9][0-9]*-[SML]\.jpg$/.test(image.src));
    const remplacerImage = image => {
        if (estCouverture(image) && image.src !== imageDefaut) {
            image.src = imageDefaut;
        }
    };
    document.addEventListener('error', evenement => remplacerImage(evenement.target), true);
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('img').forEach(image => {
            if (image.complete && image.naturalWidth === 0) {
                remplacerImage(image);
            }
        });
    });
})();
