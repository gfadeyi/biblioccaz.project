(function () {
    const messagerie = document.querySelector('.messagerie-equipe[data-actualisation]');
    if (!messagerie || !window.fetch) {
        return;
    }
    const DELAI_VISIBLE = 5000;
    const DELAI_MAXIMUM = 60000;
    const idConversation = parseInt(messagerie.dataset.conversation, 10) || 0;
    let dernier = parseInt(messagerie.dataset.dernier, 10) || 0;
    let delai = DELAI_VISIBLE;
    let minuterie = null;
    let enCours = false;
    const fil = messagerie.querySelector('[data-fil-messages]');
    const liste = messagerie.querySelector('[data-liste-conversations]');
    const bandeau = messagerie.querySelector('[data-nouveaux-messages]');
    const titreInitial = document.title;
    let derniereListe = null;

    const procheDuBas = function () {
        return !fil || fil.scrollHeight - fil.scrollTop - fil.clientHeight < 80;
    };
    const descendre = function () {
        if (fil) {
            fil.scrollTop = fil.scrollHeight;
        }
        if (bandeau) {
            bandeau.hidden = true;
        }
    };
    if (bandeau) {
        bandeau.querySelector('button').addEventListener('click', descendre);
    }
    if (fil) {
        fil.addEventListener('scroll', function () {
            if (bandeau && procheDuBas()) {
                bandeau.hidden = true;
            }
        });
    }

    const mettreAJourBadge = function (nonLus) {
        const lien = document.querySelector('[data-menu="messagerie_equipe"]');
        if (lien) {
            let badge = lien.querySelector('.badge');
            if (nonLus > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge rounded-pill ms-auto';
                    lien.appendChild(badge);
                }
                badge.textContent = nonLus;
            } else if (badge) {
                badge.remove();
            }
        }
        document.title = nonLus > 0 ? '(' + nonLus + ') ' + titreInitial : titreInitial;
    };

    const ajouterMessages = function (messages) {
        if (!fil || messages.length === 0) {
            return;
        }
        const etaitEnBas = procheDuBas();
        const vide = fil.querySelector('[data-fil-vide]');
        if (vide) {
            vide.remove();
        }
        let recusDesAutres = 0;
        messages.forEach(function (message) {
            if (fil.querySelector('[data-message="' + message.id + '"]')) {
                return;
            }
            const modele = document.createElement('template');
            modele.innerHTML = message.html;
            fil.appendChild(modele.content);
            if (!message.moi) {
                recusDesAutres++;
            }
        });
        if (etaitEnBas) {
            descendre();
        } else if (bandeau && recusDesAutres > 0) {
            bandeau.querySelector('span').textContent = recusDesAutres > 1 ? recusDesAutres + ' nouveaux messages' : 'Nouveau message';
            bandeau.hidden = false;
        }
    };

    const remplacerListe = function (html) {
        if (!liste || typeof html !== 'string' || html === derniereListe || liste.contains(document.activeElement)) {
            return;
        }
        liste.innerHTML = html;
        derniereListe = html;
    };

    const programmer = function () {
        clearTimeout(minuterie);
        minuterie = setTimeout(verifier, document.hidden ? Math.max(delai, 30000) : delai);
    };

    const verifier = function () {
        if (enCours) {
            return;
        }
        enCours = true;
        const parametres = new URLSearchParams({ c: idConversation, dernier: dernier, lu: document.hidden ? '0' : '1' });
        fetch(messagerie.dataset.actualisation + '?' + parametres.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (reponse) {
                if (reponse.status === 401) {
                    window.location.assign('login.php');
                    throw new Error('Session terminée');
                }
                if (reponse.status === 404) {
                    window.location.assign('messagerie_equipe.php');
                    throw new Error('Conversation introuvable');
                }
                if (!reponse.ok) {
                    throw new Error('Réponse ' + reponse.status);
                }
                return reponse.json();
            })
            .then(function (donnees) {
                if (donnees.authentification_requise === true) {
                    window.location.assign('login.php');
                    return;
                }
                ajouterMessages(donnees.messages || []);
                if (typeof donnees.dernier === 'number' && donnees.dernier > dernier) {
                    dernier = donnees.dernier;
                }
                remplacerListe(donnees.liste);
                mettreAJourBadge(parseInt(donnees.non_lus, 10) || 0);
                delai = DELAI_VISIBLE;
            })
            .catch(function () {
                delai = Math.min(delai * 2, DELAI_MAXIMUM);
            })
            .finally(function () {
                enCours = false;
                programmer();
            });
    };

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            delai = DELAI_VISIBLE;
            clearTimeout(minuterie);
            verifier();
        }
    });
    if (fil && location.hash === '#dernier') {
        descendre();
    }
    programmer();
})();
