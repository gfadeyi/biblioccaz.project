document.addEventListener('DOMContentLoaded', function () {
    const champ = document.getElementById('champPhoto');
    if (!champ) return;
    const cadre = document.getElementById('cadrePhoto');
    const apercu = document.getElementById('apercuPhoto');
    const choix = document.getElementById('choixPhoto');
    const aide = document.getElementById('aidePhoto');
    const boutonChoisir = document.getElementById('boutonChoisirPhoto');
    const boutonAnnuler = document.getElementById('annulerPhoto');
    const formulaireRetirer = document.getElementById('formulaireRetirerPhoto');
    const texteAide = aide.textContent;
    const typesAcceptes = ['image/jpeg', 'image/png', 'image/webp'];
    const tailleMax = parseInt(champ.dataset.tailleMax, 10);
    let adresseApercu = null;

    function reinitialiser(message) {
        champ.value = '';
        if (adresseApercu) URL.revokeObjectURL(adresseApercu);
        adresseApercu = null;
        apercu.hidden = true;
        apercu.removeAttribute('src');
        cadre.classList.remove('avec-apercu');
        choix.hidden = true;
        boutonChoisir.hidden = false;
        if (formulaireRetirer) formulaireRetirer.hidden = false;
        aide.textContent = message || texteAide;
        aide.classList.toggle('erreur', Boolean(message));
    }

    champ.addEventListener('change', function () {
        const fichier = champ.files[0];
        if (!fichier) {
            reinitialiser();
            return;
        }
        if (!typesAcceptes.includes(fichier.type)) {
            reinitialiser('Choisissez une image JPG, PNG ou WebP.');
            return;
        }
        if (fichier.size > tailleMax) {
            reinitialiser('Cette image dépasse 2 Mo, choisissez-en une plus légère.');
            return;
        }
        if (adresseApercu) URL.revokeObjectURL(adresseApercu);
        adresseApercu = URL.createObjectURL(fichier);
        apercu.src = adresseApercu;
        apercu.hidden = false;
        cadre.classList.add('avec-apercu');
        choix.hidden = false;
        boutonChoisir.hidden = true;
        if (formulaireRetirer) formulaireRetirer.hidden = true;
        aide.classList.remove('erreur');
        aide.textContent = 'Aperçu : enregistrez pour garder cette photo.';
    });

    boutonAnnuler.addEventListener('click', function () {
        reinitialiser();
        boutonChoisir.focus();
    });

    choix.hidden = true;
});
