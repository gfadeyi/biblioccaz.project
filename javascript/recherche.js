document.addEventListener('DOMContentLoaded', function () {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');
    if (!barre || !suggestions) return;

    function afficherMessage(texte) {
        suggestions.innerHTML = '';
        const message = document.createElement('div');
        message.className = 'list-group-item text-muted small';
        message.textContent = texte;
        suggestions.appendChild(message);
        suggestions.classList.remove('d-none');
    }

    barre.addEventListener('input', function (e) {
        const saisie = e.target.value.trim();
        if (saisie.length < 2) {
            suggestions.classList.add('d-none');
            suggestions.innerHTML = '';
            return;
        }

        if (saisie.toUpperCase() === 'SANANES') {
            suggestions.classList.remove('d-none');
            suggestions.innerHTML = `
                <div class="list-group-item suggestion-secrete">
                    <p class="suggestion-secrete__titre"><i class="bi bi-stars" aria-hidden="true"></i> Livre secret débloqué !</p>
                    <p class="suggestion-secrete__livre">« Comment mettre un 20/20 à BiblioOccaz » — par M. SANANES</p>
                    <p class="suggestion-secrete__note">Ouvrage indisponible, déjà victime de son succès auprès du jury !</p>
                </div>
            `;
            return;
        }

        fetch('recherche.php?q=' + encodeURIComponent(saisie))
            .then(reponse => reponse.json())
            .then(livres => {
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
                suggestions.classList.remove('d-none');
            })
            .catch(() => afficherMessage('Recherche indisponible pour le moment.'));
    });

    document.addEventListener('click', function (e) {
        if (!barre.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.classList.add('d-none');
        }
    });
});
