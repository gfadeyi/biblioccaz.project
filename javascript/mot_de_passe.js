(() => {
    document.querySelectorAll('.champ-mot-de-passe').forEach((conteneur) => {
        const champ = conteneur.querySelector('input');
        const bouton = conteneur.querySelector('.champ-mot-de-passe__oeil');
        if (!champ || !bouton) return;
        const icone = bouton.querySelector('i');
        bouton.hidden = false;
        conteneur.classList.add('champ-mot-de-passe--interactif');
        bouton.addEventListener('click', () => {
            const visible = champ.type === 'password';
            champ.type = visible ? 'text' : 'password';
            bouton.setAttribute('aria-pressed', visible ? 'true' : 'false');
            bouton.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            if (icone) icone.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
        champ.form?.addEventListener('submit', () => {
            champ.type = 'password';
            bouton.setAttribute('aria-pressed', 'false');
            bouton.setAttribute('aria-label', 'Afficher le mot de passe');
            if (icone) icone.className = 'bi bi-eye';
        });

        const minimum = parseInt(champ.dataset.longueurMin || '0', 10);
        const regle = minimum ? document.getElementById(champ.id + '-regle') : null;
        if (!regle) return;
        const iconeRegle = regle.querySelector('i');
        const compteur = regle.querySelector('.regle-mot-de-passe__compteur');
        const annonce = regle.querySelector('.regle-mot-de-passe__annonce');
        let etatPrecedent = regle.dataset.etat;
        const actualiser = () => {
            const longueur = Array.from(champ.value).length;
            const etat = longueur === 0 ? 'attente' : (longueur >= minimum ? 'valide' : 'invalide');
            regle.dataset.etat = etat;
            if (iconeRegle) iconeRegle.className = etat === 'valide' ? 'bi bi-check-circle-fill' : (etat === 'invalide' ? 'bi bi-x-circle' : 'bi bi-circle');
            if (compteur) compteur.textContent = longueur ? `(${Math.min(longueur, minimum)}/${minimum})` : '';
            if (annonce && etat !== etatPrecedent && etat !== 'attente') {
                annonce.textContent = etat === 'valide' ? 'Longueur minimale atteinte.' : `Encore ${minimum - longueur} caractère${minimum - longueur > 1 ? 's' : ''} au minimum.`;
            }
            etatPrecedent = etat;
        };
        champ.addEventListener('input', actualiser);
        actualiser();
    });
})();
