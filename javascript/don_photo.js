(() => {
    const typesAcceptes = ['image/jpeg', 'image/png', 'image/webp'];
    const tailleMax = 2 * 1024 * 1024;
    const champ = document.getElementById('don-photo');
    const apercu = document.getElementById('don-photo-apercu');
    const erreur = document.getElementById('don-photo-erreur');
    const adresses = [];
    const liberer = () => {
        while (adresses.length) URL.revokeObjectURL(adresses.pop());
    };
    window.addEventListener('pagehide', liberer);

    if (champ && apercu && erreur) {
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
            if (!typesAcceptes.includes(fichier.type)) {
                signaler('Choisissez une photo JPG, PNG ou WebP.');
                return;
            }
            if (fichier.size > tailleMax) {
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
    }

    const champAutres = document.getElementById('don-photos');
    const apercuAutres = document.getElementById('don-photos-apercu');
    const erreurAutres = document.getElementById('don-photos-erreur');
    if (!champAutres || !apercuAutres || !erreurAutres) return;
    const retraits = document.querySelectorAll('input[name="retirer_photos[]"]');
    const verifier = () => {
        liberer();
        apercuAutres.replaceChildren();
        const maximum = parseInt(champAutres.dataset.photosMax, 10);
        const conservees = parseInt(champAutres.dataset.photosExistantes, 10) - [...retraits].filter(c => c.checked).length;
        const fichiers = [...champAutres.files];
        let message = '';
        if (conservees + fichiers.length > maximum) {
            message = `Vous pouvez ajouter ${Math.max(0, maximum - conservees)} photo${maximum - conservees > 1 ? 's' : ''} de plus (${maximum + 1} au total avec la principale).`;
        } else if (fichiers.some(f => !typesAcceptes.includes(f.type))) {
            message = 'Choisissez des photos JPG, PNG ou WebP.';
        } else if (fichiers.some(f => f.size > tailleMax)) {
            message = 'Une photo est trop lourde : 2 Mo maximum chacune.';
        }
        champAutres.setCustomValidity(message);
        erreurAutres.textContent = message;
        erreurAutres.hidden = !message;
        if (message) return;
        fichiers.forEach(fichier => {
            const image = document.createElement('img');
            const adresse = URL.createObjectURL(fichier);
            adresses.push(adresse);
            image.src = adresse;
            image.alt = 'Aperçu : ' + fichier.name;
            apercuAutres.appendChild(image);
        });
    };
    champAutres.addEventListener('change', verifier);
    retraits.forEach(case_ => case_.addEventListener('change', verifier));
})();
