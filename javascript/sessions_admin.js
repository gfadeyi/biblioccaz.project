(() => {
    const suivi = document.getElementById('suiviSessions');
    if (!suivi) return;
    const corps = document.getElementById('corpsEnLigne');
    const statut = document.getElementById('nbEnLigne');
    const actualiser = document.getElementById('actualiserSessions');
    let chargement = false;
    function element(nom, texte, classe = '') {
        const noeud = document.createElement(nom);
        noeud.textContent = texte;
        if (classe) noeud.className = classe;
        return noeud;
    }
    function champ(formulaire, nom, valeur) {
        const saisie = document.createElement('input');
        saisie.type = 'hidden';
        saisie.name = nom;
        saisie.value = valeur;
        formulaire.appendChild(saisie);
    }
    async function rafraichir(manuel = false) {
        if (chargement || (!manuel && (corps.contains(document.activeElement) || corps.querySelector('input:checked')))) return;
        chargement = true;
        actualiser.disabled = true;
        try {
            const reponse = await fetch('en_ligne_donnees.php', { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (reponse.redirected || reponse.status === 403) {
                corps.replaceChildren();
                throw new Error('Session expirée ou accès refusé. Rechargez la page pour vous reconnecter.');
            }
            if (!reponse.ok) throw new Error('Impossible de charger les connexions. Réessayez avec Actualiser.');
            const sessions = await reponse.json();
            if (!Array.isArray(sessions)) throw new Error('Réponse indisponible. Rechargez la page.');
            corps.replaceChildren();
            sessions.forEach(session => {
                const ligne = document.createElement('tr');
                const membre = element('td', '@' + session.pseudo, 'fw-bold');
                membre.appendChild(element('div', session.role, 'small text-muted fw-normal'));
                ligne.appendChild(membre);
                const connexion = element('td', session.espace, 'small');
                connexion.appendChild(element('div', session.connexion, 'text-muted'));
                if (session.courante) connexion.appendChild(element('span', 'Cette connexion', 'badge text-bg-success'));
                ligne.appendChild(connexion);
                ligne.appendChild(element('td', session.activite, 'small text-muted'));
                const actions = document.createElement('td');
                if (session.modifiable) {
                    const formulaire = document.createElement('form');
                    formulaire.method = 'POST';
                    formulaire.action = 'en_ligne.php';
                    champ(formulaire, 'csrf', suivi.dataset.csrf);
                    champ(formulaire, 'id_user', session.id_user);
                    champ(formulaire, 'id_suivi', session.id_suivi);
                    const label = element('label', '', 'form-check py-3 mb-2');
                    const confirmation = document.createElement('input');
                    confirmation.type = 'checkbox';
                    confirmation.name = 'confirmer';
                    confirmation.value = '1';
                    confirmation.required = true;
                    confirmation.className = 'form-check-input';
                    label.appendChild(confirmation);
                    label.appendChild(element('span', 'Confirmer la fermeture pour @' + session.pseudo, 'form-check-label small'));
                    formulaire.appendChild(label);
                    const boutons = element('div', '', 'd-flex flex-wrap gap-2');
                    [['une', 'Fermer cette connexion'], ['toutes', 'Fermer toutes ses connexions']].forEach(([action, texte]) => {
                        const bouton = element('button', texte, 'btn btn-sm btn-outline-danger py-3');
                        bouton.type = 'submit';
                        bouton.name = 'action';
                        bouton.value = action;
                        boutons.appendChild(bouton);
                    });
                    formulaire.appendChild(boutons);
                    actions.appendChild(formulaire);
                } else {
                    actions.appendChild(element('span', 'Administrateur protégé', 'small text-muted'));
                }
                ligne.appendChild(actions);
                corps.appendChild(ligne);
            });
            if (!sessions.length) {
                const ligne = document.createElement('tr');
                const cellule = element('td', 'Aucune connexion active.', 'text-center text-muted py-4');
                cellule.colSpan = 4;
                ligne.appendChild(cellule);
                corps.appendChild(ligne);
            }
            statut.textContent = sessions.length + ' connexion(s) active(s)';
        } catch (erreur) {
            statut.textContent = erreur.message;
        } finally {
            chargement = false;
            actualiser.disabled = false;
        }
    }
    actualiser.addEventListener('click', () => rafraichir(true));
    rafraichir();
    setInterval(() => rafraichir(), 15000);
})();
