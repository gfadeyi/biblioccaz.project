(() => {
    const champ = document.querySelector('textarea[data-longueur-points]');
    const regle = document.getElementById('avis-regle-points');
    if (!champ || !regle) return;
    const minimum = parseInt(champ.dataset.longueurPoints, 10);
    const icone = regle.querySelector('i');
    const compteur = regle.querySelector('.regle-avis-points__compteur');
    const annonce = regle.querySelector('.regle-avis-points__annonce');
    let etatPrecedent = regle.dataset.etat;
    const actualiser = () => {
        const longueur = Array.from(champ.value.replace(/\r\n?/g, '\n').trim()).length;
        const etat = longueur >= minimum ? 'valide' : (longueur === 0 ? 'attente' : 'en_cours');
        regle.dataset.etat = etat;
        if (icone) icone.className = etat === 'valide' ? 'bi bi-check-circle-fill' : 'bi bi-info-circle';
        if (compteur) compteur.textContent = `${Math.min(longueur, minimum)}/${minimum}`;
        if (annonce && etat !== etatPrecedent && etat !== 'attente') {
            annonce.textContent = etat === 'valide' ? 'Votre avis est assez long pour rapporter des points.' : `Encore ${minimum - longueur} caractère${minimum - longueur > 1 ? 's' : ''} pour rapporter des points.`;
        }
        etatPrecedent = etat;
    };
    champ.addEventListener('input', actualiser);
    actualiser();
})();
