document.addEventListener('DOMContentLoaded', function () {
    const fenetre = document.getElementById('fenetreConfirmation');
    if (!fenetre) return;
    const boutonConfirmer = document.getElementById('boutonConfirmer');
    let formulaireEnAttente = null;

    function modale() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(fenetre) : null;
    }

    document.addEventListener('submit', function (evenement) {
        const formulaire = evenement.target;
        if (!formulaire.matches('form[data-confirmer]') || formulaire.dataset.confirme === '1') return;
        evenement.preventDefault();
        const fenetreBootstrap = modale();
        if (!fenetreBootstrap) {
            if (window.confirm(formulaire.dataset.confirmer)) {
                formulaire.dataset.confirme = '1';
                formulaire.submit();
            }
            return;
        }
        formulaireEnAttente = formulaire;
        document.getElementById('titreConfirmation').textContent = formulaire.dataset.titreConfirmer || 'Confirmer';
        document.getElementById('texteConfirmation').textContent = formulaire.dataset.confirmer;
        boutonConfirmer.textContent = formulaire.dataset.boutonConfirmer || 'Confirmer';
        fenetreBootstrap.show();
    });

    boutonConfirmer.addEventListener('click', function () {
        if (!formulaireEnAttente) return;
        formulaireEnAttente.dataset.confirme = '1';
        boutonConfirmer.disabled = true;
        if (formulaireEnAttente.requestSubmit) {
            formulaireEnAttente.requestSubmit();
        } else {
            formulaireEnAttente.submit();
        }
    });

    fenetre.addEventListener('shown.bs.modal', function () {
        fenetre.querySelector('[data-bs-dismiss="modal"]').focus();
    });

    fenetre.addEventListener('hidden.bs.modal', function () {
        boutonConfirmer.disabled = false;
        if (formulaireEnAttente && formulaireEnAttente.dataset.confirme !== '1') formulaireEnAttente = null;
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-confirme="1"]').forEach(function (formulaire) {
            delete formulaire.dataset.confirme;
        });
        boutonConfirmer.disabled = false;
    });
});
