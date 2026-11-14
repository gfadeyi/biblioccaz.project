document.querySelectorAll('form[data-tout-lire]').forEach(function (formulaire) {
    formulaire.addEventListener('submit', function (evenement) {
        if (!window.fetch || !window.FormData) {
            return;
        }
        evenement.preventDefault();
        const bouton = formulaire.querySelector('button');
        bouton.disabled = true;
        fetch(formulaire.getAttribute('action'), { method: 'POST', body: new FormData(formulaire), credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (reponse) {
                return reponse.json();
            })
            .then(function (donnees) {
                if (!donnees.ok) {
                    throw new Error(donnees.message || 'Erreur');
                }
                const zone = formulaire.closest('#entete-notifications');
                zone.querySelectorAll('.notification--non-lue').forEach(function (notification) {
                    notification.classList.remove('notification--non-lue');
                });
                const pastille = zone.querySelector('[data-compteur-notifications]');
                if (pastille) {
                    pastille.remove();
                }
                const lien = zone.querySelector('a.icone-entete');
                if (lien) {
                    lien.setAttribute('aria-label', 'Notifications');
                }
                const titre = zone.querySelector('[data-titre-notifications]');
                if (titre) {
                    titre.textContent = 'Notifications · tout est lu';
                }
                document.querySelectorAll('a.lien-menu-compte[href="notifications.php"] .compteur-menu').forEach(function (compteur) {
                    compteur.remove();
                });
                formulaire.remove();
            })
            .catch(function () {
                bouton.disabled = false;
                formulaire.submit();
            });
    });
});
