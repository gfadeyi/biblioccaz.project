(function () {
    const annonce = document.getElementById('annonce-panier');

    function fermerTout(sauf) {
        document.querySelectorAll('.suggestion-panier.ouvert').forEach(function (suggestion) {
            if (suggestion !== sauf) {
                suggestion.classList.remove('ouvert');
                suggestion.querySelector('.bouton-exemplaires').setAttribute('aria-expanded', 'false');
            }
        });
    }

    function remplacer(documentRecu, selecteur) {
        const ancien = document.querySelector(selecteur);
        const nouveau = documentRecu.querySelector(selecteur);
        if (ancien && nouveau) {
            ancien.replaceWith(nouveau);
        }
    }

    document.addEventListener('click', function (evenement) {
        const bouton = evenement.target.closest('.bouton-exemplaires');
        if (bouton) {
            const suggestion = bouton.closest('.suggestion-panier');
            const ouvrir = !suggestion.classList.contains('ouvert');
            fermerTout(suggestion);
            suggestion.classList.toggle('ouvert', ouvrir);
            bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
            if (ouvrir) {
                const premier = suggestion.querySelector('.bouton-ajout-suggestion');
                if (premier) {
                    premier.focus();
                }
            }
            return;
        }
        if (!evenement.target.closest('.suggestion-panier')) {
            fermerTout(null);
        }
    });

    document.addEventListener('keydown', function (evenement) {
        if (evenement.key !== 'Escape') {
            return;
        }
        const suggestion = document.activeElement ? document.activeElement.closest('.suggestion-panier') : null;
        fermerTout(null);
        if (suggestion) {
            suggestion.querySelector('.bouton-exemplaires').focus();
        }
    });

    document.addEventListener('submit', function (evenement) {
        const formulaire = evenement.target.closest('.formulaire-ajout-suggestion');
        if (!formulaire || !window.fetch || !window.DOMParser) {
            return;
        }
        evenement.preventDefault();
        const bouton = formulaire.querySelector('button');
        if (bouton.disabled) {
            return;
        }
        bouton.disabled = true;
        bouton.setAttribute('aria-busy', 'true');
        fetch(formulaire.getAttribute('action'), {
            method: 'POST',
            body: new FormData(formulaire),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (reponse) {
            if (reponse.status === 409) {
                throw new Error('indisponible');
            }
            if (!reponse.ok) {
                throw new Error('erreur');
            }
            return reponse.text();
        }).then(function (html) {
            const documentRecu = new DOMParser().parseFromString(html, 'text/html');
            if (!documentRecu.querySelector('#page-panier')) {
                throw new Error('erreur');
            }
            remplacer(documentRecu, '#page-panier');
            remplacer(documentRecu, '#entete-panier');
            annonce.textContent = '« ' + bouton.dataset.titre + ' » a été ajouté au panier. Total et suggestions mis à jour.';
            const titre = document.getElementById('titre-suggestions-panier');
            if (titre) {
                titre.focus({ preventScroll: true });
            }
        }).catch(function (erreur) {
            bouton.disabled = false;
            bouton.removeAttribute('aria-busy');
            annonce.textContent = erreur.message === 'indisponible'
                ? 'Cet exemplaire n’est plus disponible.'
                : 'L’ajout n’a pas abouti. Rechargez la page puis réessayez.';
            bouton.insertAdjacentHTML('afterend', '<span class="panneau-exemplaires__erreur">' + (erreur.message === 'indisponible' ? 'Plus disponible' : 'Échec, réessayez') + '</span>');
        });
    });
})();
