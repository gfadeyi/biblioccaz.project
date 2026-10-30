(() => {
    const imageDefaut = new URL(document.currentScript.dataset.defaut, document.baseURI).href;
    const estCouvertureDistante = image => image instanceof HTMLImageElement && /^https:\/\/covers\.openlibrary\.org\/b\/id\/[1-9][0-9]*-[SML]\.jpg$/.test(image.src);
    const remplacerImage = image => {
        if (estCouvertureDistante(image)) {
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
