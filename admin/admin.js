document.addEventListener('DOMContentLoaded', function () {
    const fenetre = document.getElementById('fenetreConfirmation');
    const modale = fenetre ? bootstrap.Modal.getOrCreateInstance(fenetre) : null;
    let formulaireEnAttente = null;

    document.querySelectorAll('form[data-confirmer]').forEach(function (formulaire) {
        if (modale) {
            formulaire.querySelectorAll('[data-confirmation-sans-js]').forEach(function (repli) {
                repli.hidden = true;
                repli.querySelectorAll('input').forEach(function (champ) { champ.required = false; });
            });
        }
        formulaire.addEventListener('submit', function (evenement) {
            if (formulaire.dataset.confirme === '1' || !modale) return;
            evenement.preventDefault();
            formulaireEnAttente = formulaire;
            document.getElementById('titreConfirmation').textContent = formulaire.dataset.titreConfirmer || "Confirmer l'action";
            document.getElementById('texteConfirmation').textContent = formulaire.dataset.confirmer;
            document.getElementById('boutonConfirmer').textContent = formulaire.dataset.boutonConfirmer || 'Confirmer';
            modale.show();
        });
    });

    const boutonConfirmer = document.getElementById('boutonConfirmer');
    if (boutonConfirmer) {
        boutonConfirmer.addEventListener('click', function () {
            if (!formulaireEnAttente) return;
            formulaireEnAttente.dataset.confirme = '1';
            formulaireEnAttente.querySelectorAll('[data-confirmation-sans-js] input').forEach(function (champ) { champ.checked = true; });
            boutonConfirmer.disabled = true;
            if (formulaireEnAttente.requestSubmit) {
                formulaireEnAttente.requestSubmit();
            } else {
                formulaireEnAttente.submit();
            }
        });
        fenetre.addEventListener('hidden.bs.modal', function () {
            boutonConfirmer.disabled = false;
            if (formulaireEnAttente && formulaireEnAttente.dataset.confirme !== '1') formulaireEnAttente = null;
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"], .actions-ligne [title], .bouton-action[title]').forEach(function (element) {
        if (!element.getAttribute('aria-label')) element.setAttribute('aria-label', element.getAttribute('title'));
        new bootstrap.Tooltip(element, { trigger: 'hover focus' });
    });

    document.querySelectorAll('[data-disparition]').forEach(function (message) {
        setTimeout(function () {
            message.classList.add('disparait');
            setTimeout(function () { message.remove(); }, 300);
        }, 6000);
    });

    document.querySelectorAll('[data-graphique]').forEach(function (cadre) {
        const bulle = cadre.querySelector('.bulle-graphique');
        if (!bulle) return;
        cadre.querySelectorAll('.barre-zone').forEach(function (zone) {
            function montrer() {
                const rectangleCadre = cadre.getBoundingClientRect();
                const rectangleBarre = zone.querySelector('.barre').getBoundingClientRect();
                const valeur = document.createElement('strong');
                valeur.textContent = zone.dataset.valeur;
                bulle.textContent = zone.dataset.libelle + ' · ';
                bulle.appendChild(valeur);
                bulle.style.left = (rectangleBarre.left + rectangleBarre.width / 2 - rectangleCadre.left) + 'px';
                bulle.style.top = Math.max(rectangleBarre.top - rectangleCadre.top, 18) + 'px';
                bulle.classList.add('visible');
            }
            zone.addEventListener('mouseenter', montrer);
            zone.addEventListener('focus', montrer);
            zone.addEventListener('mouseleave', function () { bulle.classList.remove('visible'); });
            zone.addEventListener('blur', function () { bulle.classList.remove('visible'); });
        });
    });
});
