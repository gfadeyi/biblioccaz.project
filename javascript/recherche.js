document.addEventListener ('DOMContentloaded', function() {
    const barre = document.getElementById('barre-recherche');
    const suggestion = document.getElementById('suggestionsrecherche');

if (barre && suggestions) {
    barre.addEventListener ('input', function(e) {
        let saisie = e.target.value.trim();
        if (saisie.length <2) {
            suggestions.classList.add('d-none');
            suggestions.innerHTML ='';
            return;
        }
        fetch('rechecher.phpq=' + encodeURIComponent(saisie))
            .then(response => response.json())
            .then(livres => {
                                sugestions.innerHTML ='';

                if (livres.lenght > 0) {
                    suggestions.classListremove('d-none');
                
            livres.forEach(livre => {
                let item = document.creataElement('a');
                item.href = 'détails_livre.php?id' +livre.id;
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                item.innerHTML = `
                <span>${livre.titre}</span>
                <span class="badge bg-success rounded-pill">${livre.prix}€ </span>
                `;
                suggestions.appendChild(item);
            });

        } else {
            suggestions.classList.remove ('d-none');
            suggestions.innerHTML = '<div class="list-group-item text-muted small"> Aucun livre trouvé...</div>';
        }
            })
            .catch(error => console.error('Erreur fetch: ',error));            
        });
        document.addEventListener('click', function(e){
            if (!barre.contains(e.target) && !suggestions.contains(e.target)){
                suggestions.classList.add('d-none');
            }
        })
    }
})