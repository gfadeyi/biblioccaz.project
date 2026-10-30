(() => {
    const champ = document.getElementById('don-photo');
    const apercu = document.getElementById('don-photo-apercu');
    const erreur = document.getElementById('don-photo-erreur');
    if (!champ || !apercu || !erreur) return;
    let adresse = null;
    const nettoyer = () => {
        if (adresse) URL.revokeObjectURL(adresse);
        adresse = null;
        apercu.hidden = true;
        apercu.removeAttribute('src');
    };
    const signaler = message => {
        champ.setCustomValidity(message);
        erreur.textContent = message;
        erreur.hidden = !message;
    };
    champ.addEventListener('change', () => {
        nettoyer();
        signaler('');
        const fichier = champ.files[0];
        if (!fichier) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(fichier.type)) {
            signaler('Choisissez une photo JPG, PNG ou WebP.');
            return;
        }
        if (fichier.size > 2 * 1024 * 1024) {
            signaler('La photo est trop lourde : 2 Mo maximum.');
            return;
        }
        adresse = URL.createObjectURL(fichier);
        apercu.src = adresse;
        apercu.hidden = false;
    });
    apercu.addEventListener('error', () => {
        nettoyer();
        signaler('Cette image ne peut pas être lue. Choisissez une autre photo.');
    });
    window.addEventListener('pagehide', nettoyer);
})();
