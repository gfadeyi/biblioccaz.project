(() => {
    const champ = document.getElementById('don_titre');
    const liste = document.getElementById('don_suggestions');
    const idLivre = document.getElementById('don_id_livre');
    const info = document.getElementById('don_lien_info');
    const auteur = document.getElementById('don_auteur');
    const categorie = document.getElementById('don_categorie');
    if (!champ || !liste || !idLivre || !info) return;

    const aideInitiale = 'Tapez au moins deux lettres pour retrouver le livre dans le catalogue, ou saisissez un titre libre.';
    let resultats = [];
    let actif = -1;
    let minuterie = null;
    let controleur = null;
    let derniereRecherche = '';

    const fermer = () => {
        liste.hidden = true;
        champ.setAttribute('aria-expanded', 'false');
        champ.removeAttribute('aria-activedescendant');
        actif = -1;
    };

    const surligner = index => {
        actif = index;
        liste.querySelectorAll('[role="option"]').forEach((option, position) => {
            option.setAttribute('aria-selected', position === index ? 'true' : 'false');
            if (position === index) {
                champ.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }
        });
    };

    const choisir = livre => {
        champ.value = livre.titre;
        idLivre.value = livre.id;
        if (auteur && livre.auteur) auteur.value = livre.auteur;
        if (categorie && livre.categorie) categorie.value = String(livre.categorie);
        info.textContent = '✓ Livre lié au catalogue : ' + livre.titre + (livre.auteur ? ' — ' + livre.auteur : '');
        derniereRecherche = livre.titre;
        fermer();
    };

    const isbnDe = texte => String(texte || '').replace(/[^0-9X]/gi, '').toUpperCase();

    const afficher = (livres, texte) => {
        const scanne = champ.dataset.isbnScanne || '';
        delete champ.dataset.isbnScanne;
        if (scanne && scanne === texte) {
            const livreScanne = livres.find(livre => isbnDe(livre.isbn) === scanne);
            if (livreScanne) {
                choisir(livreScanne);
                return;
            }
            champ.value = '';
            derniereRecherche = '';
            fermer();
            info.textContent = 'ISBN ' + scanne + ' absent du catalogue : saisissez le titre du livre.';
            return;
        }
        resultats = livres;
        liste.replaceChildren();
        if (!livres.length) {
            const vide = document.createElement('li');
            vide.className = 'recherche-don__vide';
            vide.textContent = 'Aucun livre du catalogue ne correspond : vous pouvez garder ce titre libre.';
            liste.append(vide);
        }
        livres.forEach((livre, index) => {
            const option = document.createElement('li');
            option.id = 'don_suggestion_' + index;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.className = 'recherche-don__option';
            const image = document.createElement('img');
            image.src = livre.couverture;
            image.alt = '';
            image.loading = 'lazy';
            const texte = document.createElement('span');
            const titre = document.createElement('strong');
            titre.textContent = livre.titre;
            const details = document.createElement('small');
            details.textContent = [livre.auteur, livre.isbn ? 'ISBN ' + livre.isbn : ''].filter(Boolean).join(' · ');
            texte.append(titre, details);
            option.append(image, texte);
            option.addEventListener('mousedown', evenement => {
                evenement.preventDefault();
                choisir(livre);
            });
            liste.append(option);
        });
        liste.hidden = false;
        champ.setAttribute('aria-expanded', 'true');
        actif = -1;
    };

    const rechercher = () => {
        const texte = champ.value.trim();
        if (texte.length < 2 || texte === derniereRecherche) {
            if (texte.length < 2) fermer();
            return;
        }
        derniereRecherche = texte;
        if (controleur) controleur.abort();
        controleur = new AbortController();
        fetch('don_recherche_livre?q=' + encodeURIComponent(texte), { signal: controleur.signal, headers: { 'Accept': 'application/json' } })
            .then(reponse => reponse.ok ? reponse.json() : { livres: [] })
            .then(donnees => {
                if (champ.value.trim() === texte && document.activeElement === champ) afficher(donnees.livres || [], texte);
            })
            .catch(() => {});
    };

    champ.addEventListener('input', () => {
        if (idLivre.value) {
            idLivre.value = '';
            info.textContent = aideInitiale;
        }
        clearTimeout(minuterie);
        minuterie = setTimeout(rechercher, 250);
    });

    champ.addEventListener('keydown', evenement => {
        if (liste.hidden || !resultats.length) {
            if (evenement.key === 'ArrowDown' && champ.value.trim().length >= 2) {
                derniereRecherche = '';
                rechercher();
            }
            return;
        }
        if (evenement.key === 'ArrowDown') {
            evenement.preventDefault();
            surligner((actif + 1) % resultats.length);
        } else if (evenement.key === 'ArrowUp') {
            evenement.preventDefault();
            surligner(actif <= 0 ? resultats.length - 1 : actif - 1);
        } else if (evenement.key === 'Enter' && actif >= 0) {
            evenement.preventDefault();
            choisir(resultats[actif]);
        } else if (evenement.key === 'Escape') {
            evenement.preventDefault();
            fermer();
        }
    });

    champ.addEventListener('blur', () => setTimeout(fermer, 120));
})();
