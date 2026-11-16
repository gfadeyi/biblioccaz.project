document.addEventListener('DOMContentLoaded', () => {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');
    const statut = document.getElementById('statut-recherche');
    if (!barre || !suggestions || !statut) return;
    let minuterie = null;
    let attente = null;
    let requeteEnCours = null;
    let version = 0;
    let indexActif = -1;
    let rechercheAffichee = '';
    const cacheSuggestions = new Map();

    function annoncer(texte, discret = false) {
        statut.classList.remove('d-none');
        statut.classList.toggle('visually-hidden', discret);
        statut.textContent = texte;
    }

    function masquerSuggestions() {
        suggestions.classList.add('d-none');
        barre.setAttribute('aria-expanded', 'false');
        barre.removeAttribute('aria-activedescendant');
        suggestions.querySelectorAll('[role="option"]').forEach(lien => {
            lien.setAttribute('aria-selected', 'false');
            lien.classList.remove('suggestion-active');
        });
        indexActif = -1;
    }

    function abandonner() {
        clearTimeout(minuterie);
        clearTimeout(attente);
        if (requeteEnCours) requeteEnCours.abort();
        requeteEnCours = null;
        version++;
        barre.setAttribute('aria-busy', 'false');
        masquerSuggestions();
        statut.textContent = '';
        statut.classList.add('d-none');
    }

    function afficherLivres(livres, saisie) {
        suggestions.replaceChildren();
        rechercheAffichee = saisie;
        if (!livres.length) {
            masquerSuggestions();
            annoncer('Aucune suggestion. Appuyez sur Entrée pour rechercher dans le catalogue.');
            return;
        }
        livres.forEach((livre, index) => {
            const lien = document.createElement('a');
            lien.href = 'livre?id=' + encodeURIComponent(livre.id_livre || livre.id);
            lien.id = 'suggestion-livre-' + index;
            lien.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2';
            lien.setAttribute('role', 'option');
            lien.setAttribute('aria-selected', 'false');
            lien.tabIndex = -1;
            const titre = document.createElement('span');
            titre.textContent = livre.titre;
            const auteur = document.createElement('small');
            auteur.className = 'text-muted text-truncate';
            auteur.textContent = livre.auteur || '';
            lien.append(titre, auteur);
            suggestions.appendChild(lien);
        });
        indexActif = -1;
        suggestions.classList.remove('d-none');
        barre.setAttribute('aria-expanded', 'true');
        annoncer(livres.length + ' livre' + (livres.length > 1 ? 's suggérés.' : ' suggéré.') + ' Utilisez les flèches puis Entrée.', true);
    }

    barre.addEventListener('input', () => {
        abandonner();
        const versionSaisie = version;
        const saisie = barre.value.trim();
        suggestions.replaceChildren();
        if (saisie.length < 2 && !/^[0-9]$/.test(saisie)) return;
        if (saisie.toUpperCase() === 'SANANES') {
            annoncer('Clin d’œil au projet étudiant : « Comment mettre un 20/20 à BIBLIOccaz » est un titre imaginaire, pas un livre du catalogue.');
            return;
        }
        minuterie = setTimeout(async () => {
            const memorise = cacheSuggestions.get(saisie);
            const utiliserCache = memorise && Date.now() - memorise.date < 30000;
            requeteEnCours = new AbortController();
            if (!utiliserCache) {
                barre.setAttribute('aria-busy', 'true');
                attente = setTimeout(() => {
                    if (versionSaisie === version) annoncer('Recherche en cours…');
                }, 200);
            }
            try {
                let livres;
                if (utiliserCache) livres = memorise.livres;
                else {
                    const reponse = await fetch('recherche?q=' + encodeURIComponent(saisie), {signal: requeteEnCours.signal});
                    if (!reponse.ok) throw new Error('Recherche indisponible');
                    livres = await reponse.json();
                }
                if (versionSaisie !== version) return;
                if (!Array.isArray(livres)) throw new Error('Réponse de recherche invalide');
                if (!utiliserCache) {
                    cacheSuggestions.delete(saisie);
                    cacheSuggestions.set(saisie, {date: Date.now(), livres});
                    if (cacheSuggestions.size > 20) cacheSuggestions.delete(cacheSuggestions.keys().next().value);
                }
                afficherLivres(livres, saisie);
            } catch (erreur) {
                if (erreur.name !== 'AbortError' && versionSaisie === version) {
                    masquerSuggestions();
                    annoncer('Les suggestions sont indisponibles. Appuyez sur Entrée pour rechercher dans le catalogue.');
                }
            } finally {
                if (versionSaisie === version) {
                    clearTimeout(attente);
                    barre.setAttribute('aria-busy', 'false');
                }
            }
        }, 250);
    });

    barre.addEventListener('keydown', evenement => {
        const liens = [...suggestions.querySelectorAll('[role="option"]')];
        if (['ArrowDown', 'ArrowUp'].includes(evenement.key) && liens.length && rechercheAffichee === barre.value.trim()) {
            evenement.preventDefault();
            suggestions.classList.remove('d-none');
            barre.setAttribute('aria-expanded', 'true');
            indexActif = indexActif < 0 ? (evenement.key === 'ArrowDown' ? 0 : liens.length - 1) : (indexActif + (evenement.key === 'ArrowDown' ? 1 : -1) + liens.length) % liens.length;
            liens.forEach((lien, index) => {
                lien.setAttribute('aria-selected', index === indexActif ? 'true' : 'false');
                lien.classList.toggle('suggestion-active', index === indexActif);
            });
            barre.setAttribute('aria-activedescendant', liens[indexActif].id);
            liens[indexActif].scrollIntoView({block: 'nearest'});
        } else if (evenement.key === 'Enter' && indexActif >= 0 && !suggestions.classList.contains('d-none')) {
            evenement.preventDefault();
            liens[indexActif].click();
        } else if (evenement.key === 'Escape' || evenement.key === 'Tab') abandonner();
    });

    document.addEventListener('keydown', evenement => {
        if (evenement.key === 'Escape') abandonner();
    });
    document.addEventListener('click', evenement => {
        if (!barre.contains(evenement.target) && !suggestions.contains(evenement.target)) abandonner();
    });
});
