document.querySelectorAll('.conversation').forEach(function (conversation) {
    const fil = conversation.querySelector('.conversation__fil');
    if (fil) {
        fil.scrollTop = fil.scrollHeight;
    }

    const brancherSignalements = function (racine) {
        racine.querySelectorAll('.message__signaler').forEach(function (signaler) {
            signaler.addEventListener('toggle', function () {
                if (signaler.open) {
                    signaler.querySelector('form').scrollIntoView({ block: 'nearest', behavior: 'instant' });
                    signaler.querySelector('select').focus({ preventScroll: true });
                }
            });
        });
    };
    brancherSignalements(conversation);

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
        const cleBrouillon = 'brouillon-message-' + conversation.dataset.membre + '-' + conversation.dataset.conversation;
        const lireBrouillon = function () {
            try {
                return sessionStorage.getItem(cleBrouillon) || '';
            } catch (erreur) {
                return '';
            }
        };
        const ecrireBrouillon = function (texte) {
            try {
                if (texte.trim() === '') {
                    sessionStorage.removeItem(cleBrouillon);
                } else {
                    sessionStorage.setItem(cleBrouillon, texte);
                }
            } catch (erreur) {}
        };
        if (conversation.dataset.envoi === 'reussi') {
            ecrireBrouillon('');
        } else if (saisie.value === '') {
            saisie.value = lireBrouillon();
            mettreAJour();
        } else {
            ecrireBrouillon(saisie.value);
        }
        saisie.addEventListener('input', mettreAJour);
        saisie.addEventListener('input', function () {
            ecrireBrouillon(saisie.value);
        });
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
    if (!bandeau || !saisie || !fil || !window.fetch || !window.FormData) {
        return;
    }
    const formulaire = saisie.form;
    const bouton = formulaire.querySelector('button');
    const indicateur = conversation.querySelector('.conversation__ecrit-texte');
    const erreurEnvoi = conversation.querySelector('.conversation__erreur-envoi');
    const alerteConnexion = conversation.querySelector('.conversation__connexion');
    const zoneStatut = bandeau.parentElement;
    const annonce = document.createElement('span');
    annonce.className = 'visually-hidden';
    zoneStatut.appendChild(annonce);
    const intervalle = 4000;
    let minuteur = null;
    let actualisationEnCours = false;
    let envoiEnCours = false;
    let echecs = 0;
    let arrete = false;
    let dernierSignalEcriture = 0;
    let nonLusAffiches = 0;

    const champ = function (nom) {
        const element = formulaire.querySelector('[name="' + nom + '"]');
        return element ? element.value : '';
    };
    const procheDuBas = function () {
        return fil.scrollHeight - fil.scrollTop - fil.clientHeight < 80;
    };
    const allerEnBas = function () {
        fil.scrollTop = fil.scrollHeight;
        bandeau.hidden = true;
        nonLusAffiches = 0;
    };
    bandeau.querySelector('a').addEventListener('click', function (evenement) {
        evenement.preventDefault();
        allerEnBas();
        fil.focus({ preventScroll: true });
    });
    fil.addEventListener('scroll', function () {
        if (!bandeau.hidden && procheDuBas()) {
            bandeau.hidden = true;
            nonLusAffiches = 0;
        }
    });

    const requete = function (action, valeurs) {
        const corps = new FormData();
        corps.append('action', action);
        corps.append('csrf', champ('csrf'));
        corps.append('id_conversation', conversation.dataset.conversation);
        corps.append('retour', champ('retour'));
        corps.append('dernier', conversation.dataset.dernier || '0');
        corps.append('visible', document.hidden ? '0' : '1');
        Object.keys(valeurs || {}).forEach(function (cle) {
            corps.append(cle, valeurs[cle]);
        });
        return fetch('message_nouveaux.php', { method: 'POST', body: corps, credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (reponse) {
                return reponse.json().then(function (donnees) {
                    return { statut: reponse.status, donnees: donnees };
                });
            });
    };

    const arreter = function (texte) {
        arrete = true;
        clearTimeout(minuteur);
        indicateur.hidden = true;
        if (alerteConnexion) {
            alerteConnexion.textContent = texte;
            alerteConnexion.hidden = false;
        }
    };
    const sessionPerdue = function (donnees, statut) {
        if (donnees && (donnees.authentification_requise === true || donnees.recharger === true)) {
            arreter(statut === 401 || donnees.authentification_requise ? 'Votre session a expiré : reconnectez-vous pour continuer la conversation.' : 'Session expirée : rechargez la page pour continuer.');
            return true;
        }
        return false;
    };

    const majCompteurs = function (total, parConversation) {
        document.querySelectorAll('a[href="messages.php"]').forEach(function (lien) {
            const classe = lien.classList.contains('icone-entete') ? 'pastille' : (lien.classList.contains('lien-menu-compte') ? 'compteur-menu' : '');
            if (!classe) {
                return;
            }
            let pastille = lien.querySelector('.' + classe);
            if (total > 0) {
                if (!pastille) {
                    pastille = document.createElement('span');
                    pastille.className = classe;
                    lien.appendChild(pastille);
                }
                pastille.textContent = total;
            } else if (pastille) {
                pastille.remove();
            }
            if (classe === 'pastille') {
                if (total > 0) {
                    lien.setAttribute('aria-label', 'Messages, ' + total + ' non lu' + (total > 1 ? 's' : ''));
                } else {
                    lien.removeAttribute('aria-label');
                }
            }
        });
        document.querySelectorAll('.ligne-conversation').forEach(function (ligne) {
            const identifiant = new URL(ligne.href, window.location.href).searchParams.get('id');
            const nombre = parConversation && parConversation[identifiant] ? parConversation[identifiant] : 0;
            let pastille = ligne.querySelector('.ligne-conversation__non-lus');
            ligne.classList.toggle('ligne-conversation--non-lue', nombre > 0);
            if (nombre > 0) {
                if (!pastille) {
                    pastille = document.createElement('span');
                    pastille.className = 'ligne-conversation__non-lus';
                    ligne.appendChild(pastille);
                }
                pastille.textContent = nombre;
                const texte = document.createElement('span');
                texte.className = 'visually-hidden';
                texte.textContent = ' non lu' + (nombre > 1 ? 's' : '');
                pastille.appendChild(texte);
            } else if (pastille) {
                pastille.remove();
            }
        });
    };

    const ajouterMessages = function (messages) {
        const enBas = procheDuBas();
        let recus = 0;
        messages.forEach(function (message) {
            if (document.getElementById('message-' + message.id)) {
                return;
            }
            if (conversation.dataset.jour !== message.jour) {
                const jour = document.createElement('p');
                jour.className = 'conversation__jour';
                jour.dataset.jour = message.jour;
                const libelle = document.createElement('span');
                libelle.textContent = message.libelle_jour;
                jour.appendChild(libelle);
                fil.appendChild(jour);
                conversation.dataset.jour = message.jour;
            }
            fil.insertAdjacentHTML('beforeend', message.html);
            const ajout = fil.lastElementChild;
            brancherSignalements(ajout);
            if (!ajout.classList.contains('message--moi')) {
                recus++;
            }
        });
        if (recus > 0) {
            annonce.textContent = recus > 1 ? recus + ' nouveaux messages' : 'Nouveau message';
        }
        if (enBas) {
            allerEnBas();
        } else if (recus > 0) {
            nonLusAffiches += recus;
            bandeau.querySelector('span').textContent = nonLusAffiches > 1 ? nonLusAffiches + ' nouveaux messages' : 'Nouveau message';
            bandeau.hidden = false;
        }
    };

    const fermerSaisie = function () {
        const rappel = conversation.querySelector('.conversation__rappel');
        const fin = document.createElement('p');
        fin.className = 'conversation__fermee';
        fin.textContent = 'Cette conversation est terminée : elle reste consultable, mais on ne peut plus y répondre.';
        formulaire.replaceWith(fin);
        if (rappel) {
            rappel.remove();
        }
        arreter('');
        if (alerteConnexion) {
            alerteConnexion.hidden = true;
        }
    };

    const appliquer = function (donnees) {
        ajouterMessages(donnees.messages || []);
        if (donnees.dernier > parseInt(conversation.dataset.dernier || '0', 10)) {
            conversation.dataset.dernier = donnees.dernier;
        }
        indicateur.hidden = !donnees.ecrit;
        majCompteurs(donnees.non_lus_total || 0, donnees.non_lus || {});
        if (donnees.ouverte === false) {
            fermerSaisie();
        }
        return donnees.suite === true;
    };

    const planifier = function (delai) {
        clearTimeout(minuteur);
        if (!arrete && !document.hidden) {
            minuteur = setTimeout(actualiser, delai);
        }
    };
    const actualiser = function () {
        if (actualisationEnCours || arrete || document.hidden) {
            return;
        }
        actualisationEnCours = true;
        let suite = false;
        requete('actualiser')
            .then(function (resultat) {
                if (sessionPerdue(resultat.donnees, resultat.statut)) {
                    return;
                }
                if (!resultat.donnees.ok) {
                    throw new Error('Réponse ' + resultat.statut);
                }
                echecs = 0;
                if (alerteConnexion) {
                    alerteConnexion.hidden = true;
                }
                suite = appliquer(resultat.donnees);
            })
            .catch(function () {
                echecs++;
                if (echecs >= 3 && alerteConnexion && !arrete) {
                    alerteConnexion.hidden = false;
                }
            })
            .finally(function () {
                actualisationEnCours = false;
                planifier(suite ? 0 : (echecs ? Math.min(30000, intervalle * Math.pow(2, echecs)) : intervalle));
            });
    };

    const signalerEcriture = function (ecrit) {
        if (arrete) {
            return;
        }
        dernierSignalEcriture = ecrit ? Date.now() : 0;
        requete('ecrire', { ecrit: ecrit ? '1' : '0' }).catch(function () {});
    };
    saisie.addEventListener('input', function () {
        if (saisie.value.trim() === '') {
            if (dernierSignalEcriture) {
                signalerEcriture(false);
            }
        } else if (Date.now() - dernierSignalEcriture > 3000) {
            signalerEcriture(true);
        }
    });

    formulaire.addEventListener('submit', function (evenement) {
        evenement.preventDefault();
        if (envoiEnCours || arrete || saisie.value.trim() === '') {
            bouton.disabled = envoiEnCours;
            return;
        }
        envoiEnCours = true;
        bouton.disabled = true;
        erreurEnvoi.hidden = true;
        requete('envoyer', { contenu: saisie.value })
            .then(function (resultat) {
                if (sessionPerdue(resultat.donnees, resultat.statut)) {
                    return;
                }
                if (!resultat.donnees.ok) {
                    erreurEnvoi.textContent = resultat.donnees.message || "Le message n'a pas pu être envoyé.";
                    erreurEnvoi.hidden = false;
                    return;
                }
                saisie.value = '';
                dernierSignalEcriture = 0;
                saisie.dispatchEvent(new Event('input'));
                appliquer(resultat.donnees);
                allerEnBas();
            })
            .catch(function () {
                erreurEnvoi.textContent = "Le message n'a pas pu partir. Vérifiez votre connexion, il est conservé dans le champ.";
                erreurEnvoi.hidden = false;
            })
            .finally(function () {
                envoiEnCours = false;
                bouton.disabled = false;
                if (!arrete) {
                    saisie.focus();
                }
            });
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearTimeout(minuteur);
            if (dernierSignalEcriture) {
                signalerEcriture(false);
            }
        } else {
            planifier(0);
        }
    });
    planifier(intervalle);
});
