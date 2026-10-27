document.querySelectorAll('.conversation').forEach(function (conversation) {
    const fil = conversation.querySelector('.conversation__fil');
    if (fil) {
        fil.scrollTop = fil.scrollHeight;
    }

    conversation.querySelectorAll('.message__signaler').forEach(function (signaler) {
        signaler.addEventListener('toggle', function () {
            if (signaler.open) {
                signaler.querySelector('form').scrollIntoView({ block: 'nearest', behavior: 'instant' });
                signaler.querySelector('select').focus({ preventScroll: true });
            }
        });
    });

    const saisie = conversation.querySelector('textarea[data-compteur]');
    if (saisie) {
        const compteur = document.getElementById(saisie.dataset.compteur);
        const maximum = parseInt(saisie.getAttribute('maxlength'), 10);
        const mettreAJour = function () {
            const reste = maximum - saisie.value.length;
            compteur.textContent = reste <= 200 ? reste + ' caractère' + (reste > 1 ? 's' : '') + ' restant' + (reste > 1 ? 's' : '') : '';
            saisie.style.height = 'auto';
            saisie.style.height = Math.min(saisie.scrollHeight, 220) + 'px';
        };
        saisie.addEventListener('input', mettreAJour);
        saisie.addEventListener('keydown', function (evenement) {
            if (evenement.key === 'Enter' && (evenement.ctrlKey || evenement.metaKey) && saisie.value.trim() !== '') {
                evenement.preventDefault();
                saisie.form.requestSubmit();
            }
        });
        saisie.form.addEventListener('submit', function () {
            const bouton = saisie.form.querySelector('button');
            bouton.disabled = true;
        });
    }

    const bandeau = conversation.querySelector('.conversation__nouveaux');
    if (!bandeau || !saisie) {
        return;
    }
    bandeau.querySelector('a').addEventListener('click', function (evenement) {
        evenement.preventDefault();
        window.location.reload();
    });
    let verifications = 0;
    const verifier = function () {
        if (document.hidden || verifications >= 20) {
            return;
        }
        verifications++;
        fetch('message_nouveaux.php?id=' + conversation.dataset.conversation + '&dernier=' + conversation.dataset.dernier, { credentials: 'same-origin' })
            .then(function (reponse) { return reponse.ok ? reponse.json() : { nouveaux: 0 }; })
            .then(function (donnees) {
                if (donnees.nouveaux > 0) {
                    bandeau.querySelector('span').textContent = donnees.nouveaux > 1 ? donnees.nouveaux + ' nouveaux messages' : 'Nouveau message';
                    bandeau.hidden = false;
                }
            })
            .catch(function () {});
    };
    setInterval(verifier, 30000);
});
