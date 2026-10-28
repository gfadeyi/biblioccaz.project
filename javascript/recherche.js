document.addEventListener('DOMContentLoaded', function () {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');
    if (!barre || !suggestions) return;
    let minuterie = null;
    let requeteEnCours = null;
    let version = 0;

    function masquerSuggestions() {
        suggestions.classList.add('d-none');
        barre.setAttribute('aria-expanded', 'false');
    }

    function montrerSuggestions() {
        suggestions.classList.remove('d-none');
        barre.setAttribute('aria-expanded', 'true');
    }

    function afficherMessage(texte) {
        suggestions.innerHTML = '';
        const message = document.createElement('div');
        message.className = 'list-group-item text-muted small';
        message.textContent = texte;
        suggestions.appendChild(message);
        montrerSuggestions();
    }

    barre.addEventListener('input', function (e) {
        clearTimeout(minuterie);
        if (requeteEnCours) requeteEnCours.abort();
        const versionSaisie = ++version;
        const saisie = e.target.value.trim();
        masquerSuggestions();
        if (saisie.length < 2) {
            suggestions.innerHTML = '';
            return;
        }

        if (saisie.toUpperCase() === 'SANANES') {
            montrerSuggestions();
            suggestions.innerHTML = `
                <div class="list-group-item suggestion-secrete">
                    <p class="suggestion-secrete__titre"><i class="bi bi-stars" aria-hidden="true"></i> Livre secret débloqué !</p>
                    <p class="suggestion-secrete__livre">« Comment mettre un 20/20 à BiblioOccaz » — par M. SANANES</p>
                    <p class="suggestion-secrete__note">Ouvrage indisponible, déjà victime de son succès auprès du jury !</p>
                </div>
            `;
            return;
        }

        minuterie = setTimeout(function () {
            requeteEnCours = new AbortController();
            fetch('recherche.php?q=' + encodeURIComponent(saisie), { signal: requeteEnCours.signal })
                .then(reponse => {
                    if (!reponse.ok) throw new Error('Recherche indisponible');
                    return reponse.json();
                })
                .then(livres => {
                    if (versionSaisie !== version) return;
                    if (livres.length === 0) {
                        afficherMessage('Aucun livre trouvé...');
                        return;
                    }
                    suggestions.innerHTML = '';
                    livres.forEach(livre => {
                        const lien = document.createElement('a');
                        lien.href = 'detail_livre.php?id=' + encodeURIComponent(livre.id_livre || livre.id);
                        lien.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2';
                        const titre = document.createElement('span');
                        titre.textContent = livre.titre;
                        const auteur = document.createElement('small');
                        auteur.className = 'text-muted text-truncate';
                        auteur.textContent = livre.auteur || '';
                        lien.append(titre, auteur);
                        suggestions.appendChild(lien);
                    });
                    montrerSuggestions();
                })
                .catch(erreur => {
                    if (erreur.name !== 'AbortError' && versionSaisie === version) afficherMessage('Recherche indisponible pour le moment.');
                });
        }, 250);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            clearTimeout(minuterie);
            if (requeteEnCours) requeteEnCours.abort();
            version++;
            masquerSuggestions();
            if (suggestions.contains(document.activeElement)) barre.focus();
        }
    });

    document.addEventListener('click', function (e) {
        if (!barre.contains(e.target) && !suggestions.contains(e.target)) {
            clearTimeout(minuterie);
            if (requeteEnCours) requeteEnCours.abort();
            version++;
            masquerSuggestions();
        }
    });
});
