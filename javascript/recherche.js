document.addEventListener('DOMContentLoaded', function() {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');

    if (barre && suggestions) {
        barre.addEventListener('input', function(e) {
            let saisie = e.target.value.trim();
            if (saisie.length < 2) {
                suggestions.classList.add('d-none');
                suggestions.innerHTML = '';
                return;
            }

             let rechercheMinuscule = saisie.toLowerCase();
            if (rechercheMinuscule === "sananes") {
                suggestions.classList.remove('d-none');
                suggestions.innerHTML = `
                    <div class="list-group-item list-group-item-warning p-3" style="border: 2px dashed #ffc107;">
                        <h6 class="mb-1" style="font-weight: bold; color: #856404;"> Livre Secret Débloqué !</h6>
                        <p class="mb-1 small" style="font-weight: bold;">
                            "Comment mettre un 20/20 à BiblioOccaz" — par M. SANANES
                        </p>
                        <small class="text-muted d-block mt-1" style="font-style: italic;">
                            Ouvrage indisponible, déjà victime de son succès auprès du jury ! 
                        </small>
                    </div>
                `;
                return;
            }

            fetch('recherche.php?q=' + encodeURIComponent(saisie))
                .then(response => response.json())
                .then(livres => {
                    suggestions.innerHTML = '';

                    if (livres.length > 0) {
                        suggestions.classList.remove('d-none');
                    
                        livres.forEach(livre => {
                            let item = document.createElement('a');
                            item.href = 'detail_livre.php?id=' + livre.id;
                            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                            item.innerHTML = `
                                <span>${livre.titre}</span>
                                <span class="badge bg-success rounded-pill">${livre.auteur}</span>
                            `;
                            suggestions.appendChild(item);
                        });
                    } else {
                        suggestions.classList.remove('d-none');
                        suggestions.innerHTML = '<div class="list-group-item text-muted small">Aucun livre trouvé...</div>';
                    }
                })
                .catch(error => console.error('Erreur fetch:', error));            
        });

        document.addEventListener('click', function(e) {
            if (!barre.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.classList.add('d-none');
            }
        });
    }
});