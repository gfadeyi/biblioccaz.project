(() => {
    const argent = valeur => new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(valeur);
    const csrf = () => document.querySelector('meta[name="csrf"]')?.content || '';
    const dialogue = document.getElementById('apercu-livre');
    const contenu = document.getElementById('apercu-contenu');
    const notification = document.getElementById('notification-boutique');
    const reduction = window.matchMedia('(prefers-reduced-motion: reduce)');
    let retourFocus = null;
    let requeteApercu = null;
    let minuterieNotification = null;
    let verificationExpiration = false;
    const etat = {
        expiration: Number(document.querySelector('meta[name="panier-expiration"]')?.content || 0),
        decalage: Number(document.querySelector('meta[name="heure-serveur"]')?.content || Date.now() / 1000) * 1000 - Date.now(),
    };
    function informer(message) {
        if (!notification) return;
        notification.textContent = message;
        notification.hidden = false;
        clearTimeout(minuterieNotification);
        minuterieNotification = setTimeout(() => { notification.hidden = true; }, 7000);
    }
    function actualiserCompteur(resultat) {
        etat.expiration = Number(resultat.expiration || 0);
        etat.decalage = Number(resultat.maintenant || Date.now() / 1000) * 1000 - Date.now();
        const lien = document.querySelector('#entete-panier > a');
        let compteur = lien?.querySelector('[data-panier-compteur]');
        if (resultat.articles > 0 && lien) {
            if (!compteur) { compteur = document.createElement('span'); compteur.className = 'pastille'; compteur.dataset.panierCompteur = ''; lien.append(compteur); }
            compteur.textContent = resultat.articles;
            lien.setAttribute('aria-label', `Mon panier : ${resultat.articles} article(s)`);
            compteur.classList.remove('rebond');
            if (!reduction.matches) { requestAnimationFrame(() => compteur.classList.add('rebond')); }
        } else { compteur?.remove(); lien?.setAttribute('aria-label', 'Mon panier vide'); }
        afficherTemps();
    }
    async function actualiserPagePanier() {
        const reponse = await fetch('panier.php', {cache: 'no-store', credentials: 'same-origin'});
        if (!reponse.ok) return;
        const page = new DOMParser().parseFromString(await reponse.text(), 'text/html');
        const actuel = document.getElementById('page-panier');
        const nouveau = page.getElementById('page-panier');
        if (actuel && nouveau) actuel.replaceWith(nouveau);
        const menu = document.querySelector('#entete-panier .dropdown-menu');
        const nouveauMenu = page.querySelector('#entete-panier .dropdown-menu');
        if (menu && nouveauMenu) menu.replaceWith(nouveauMenu);
        afficherTemps();
    }
    function envoler(image) {
        const cible = document.querySelector('#entete-panier > a');
        if (reduction.matches || !image || !cible) return;
        const depart = image.getBoundingClientRect(), arrivee = cible.getBoundingClientRect();
        if (!depart.width || !depart.height) return;
        const copie = image.cloneNode();
        copie.setAttribute('aria-hidden', 'true');
        copie.className = 'couverture-envol';
        Object.assign(copie.style, {left: `${depart.left}px`, top: `${depart.top}px`, width: `${depart.width}px`, height: `${depart.height}px`});
        document.body.append(copie);
        const animation = copie.animate([{transform: 'translate(0, 0) scale(1)', opacity: .8}, {transform: `translate(${arrivee.left - depart.left}px, ${arrivee.top - depart.top}px) scale(.12)`, opacity: 0}], {duration: 480, easing: 'cubic-bezier(.2,.8,.2,1)'});
        animation.finished.catch(() => {}).finally(() => copie.remove());
    }
    async function envoyerPanier(donnees, image = null) {
        if (!donnees.has('csrf')) donnees.set('csrf', csrf());
        const reponse = await fetch('panier_action.php', {method: 'POST', body: donnees, headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
        const resultat = await reponse.json();
        if (!reponse.ok || !resultat.ok) throw new Error(resultat.message || 'Le panier n’a pas pu être mis à jour.');
        actualiserCompteur(resultat);
        informer(resultat.message);
        if (image) envoler(image);
        await actualiserPagePanier();
        document.dispatchEvent(new CustomEvent('panier:modifie', {detail: resultat}));
        return resultat;
    }
    async function verifierPanier() {
        if (verificationExpiration) return;
        verificationExpiration = true;
        try {
            const reponse = await fetch('panier_action.php', {cache: 'no-store', credentials: 'same-origin'});
            if (!reponse.ok) return;
            const resultat = await reponse.json();
            const avaitReservation = etat.expiration > 0;
            actualiserCompteur(resultat);
            if (!resultat.expiration && avaitReservation) informer(resultat.message || 'Votre réservation de 15 minutes a expiré. Les articles ont été libérés.');
            await actualiserPagePanier();
        } catch (_) { } finally { verificationExpiration = false; }
    }
    function afficherTemps() {
        const restant = etat.expiration ? Math.max(0, Math.ceil((etat.expiration * 1000 - Date.now() - etat.decalage) / 1000)) : 0;
        for (const element of document.querySelectorAll('[data-panier-minute]')) {
            element.textContent = etat.expiration ? `${String(Math.floor(restant / 60)).padStart(2, '0')}:${String(restant % 60).padStart(2, '0')}` : '';
            element.hidden = !etat.expiration;
        }
        if (etat.expiration && restant === 0) verifierPanier();
    }
    function fermerApercu() { if (dialogue?.open) dialogue.close(); }
    dialogue?.addEventListener('keydown', evenement => {
        if (evenement.key !== 'Tab') return;
        const cibles = [...dialogue.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled), select:not(:disabled), [tabindex="0"]')].filter(cible => cible.getClientRects().length);
        if (!cibles.length) { evenement.preventDefault(); return; }
        const debut = cibles[0], fin = cibles[cibles.length - 1];
        if (evenement.shiftKey && (document.activeElement === debut || !cibles.includes(document.activeElement))) { evenement.preventDefault(); fin.focus(); }
        else if (!evenement.shiftKey && (document.activeElement === fin || !cibles.includes(document.activeElement))) { evenement.preventDefault(); debut.focus(); }
    });
    dialogue?.addEventListener('close', () => {
        requeteApercu?.abort();
        dialogue.classList.remove('est-ouvert');
        if (retourFocus?.isConnected) retourFocus.focus();
    });
    dialogue?.addEventListener('click', evenement => {
        if (evenement.target !== dialogue) return;
        const rect = dialogue.getBoundingClientRect();
        if (evenement.clientX < rect.left || evenement.clientX > rect.right || evenement.clientY < rect.top || evenement.clientY > rect.bottom) fermerApercu();
    });
    async function lireApercu(id) {
        const reponse = await fetch(`apercu_livre.php?id=${encodeURIComponent(id)}`, {cache: 'no-store', credentials: 'same-origin'});
        const livre = await reponse.json();
        if (!reponse.ok) throw new Error(livre.message || 'Cet aperçu n’est pas disponible.');
        return livre;
    }
    function element(nom, texte, classe = '') {
        const noeud = document.createElement(nom);
        if (texte !== null) noeud.textContent = texte;
        if (classe) noeud.className = classe;
        return noeud;
    }
    async function ouvrirApercu(id, declencheur, donnees = null) {
        if (!dialogue || !contenu) return;
        retourFocus = declencheur;
        requeteApercu?.abort();
        requeteApercu = new AbortController();
        const controle = requeteApercu;
        const chargement = element('h2', 'Chargement de votre livre…'); chargement.id = 'apercu-titre';
        contenu.replaceChildren(chargement);
        contenu.setAttribute('aria-busy', 'true');
        if (!dialogue.open) dialogue.showModal();
        requestAnimationFrame(() => dialogue.classList.add('est-ouvert'));
        try {
            const livre = donnees || await lireApercu(id);
            if (controle.signal.aborted || !dialogue.open) return;
            const image = element('img', null, 'apercu-livre__couverture'); image.src = livre.couverture; image.alt = `Couverture de ${livre.titre}`;
            const titre = element('h2', livre.titre); titre.id = 'apercu-titre';
            const auteur = element('p', livre.auteur || '', 'apercu-livre__auteur');
            const note = element('p', livre.avis ? `${new Intl.NumberFormat('fr-FR', {maximumFractionDigits: 1}).format(livre.note)} ★ (${livre.avis} avis)` : 'Pas encore d’avis', 'apercu-livre__note');
            const prix = element('strong', '', 'apercu-livre__prix');
            const grille = element('div', null, 'conditions-livre'); grille.setAttribute('role', 'group'); grille.setAttribute('aria-label', 'Choisir la condition');
            const ajouter = element('button', 'Ajouter au panier', 'bouton bouton-action w-100'); ajouter.type = 'button'; ajouter.disabled = true;
            let selection = null;
            const disponibles = livre.etats.filter(etat => etat.disponible).sort((a, b) => a.prix - b.prix);
            for (const etatLivre of livre.etats) {
                const bouton = element('button', null, 'condition-livre'); bouton.type = 'button'; bouton.disabled = !etatLivre.disponible;
                bouton.append(element('strong', etatLivre.libelle), element('span', etatLivre.disponible ? argent(etatLivre.prix) : 'Déjà vendu'));
                bouton.setAttribute('aria-pressed', 'false');
                const choisir = () => {
                    selection = etatLivre;
                    grille.querySelectorAll('button').forEach(x => x.setAttribute('aria-pressed', String(x === bouton)));
                    prix.textContent = argent(etatLivre.prix);
                    ajouter.disabled = false;
                };
                bouton.addEventListener('click', choisir); grille.append(bouton);
                if (disponibles[0]?.id_exemplaire === etatLivre.id_exemplaire) choisir();
            }
            ajouter.addEventListener('click', async () => {
                if (!selection) return;
                ajouter.disabled = true;
                try { const f = new FormData(); f.set('action', 'ajouter'); f.set('id_exemplaire', selection.id_exemplaire); await envoyerPanier(f, image); }
                catch (erreur) { informer(erreur.message); } finally { ajouter.disabled = false; }
            });
            const lien = element('a', 'Voir la fiche complète', 'apercu-livre__lien'); lien.href = livre.lien;
            contenu.replaceChildren(image, auteur, titre, note, prix, element('h3', 'Sélectionnez la condition', 'h6'), grille, ajouter, lien);
        } catch (erreur) { if (!controle.signal.aborted) contenu.replaceChildren(element('h2', 'Aperçu indisponible'), element('p', erreur.message)); }
        finally { contenu.setAttribute('aria-busy', 'false'); }
    }
    document.addEventListener('click', async evenement => {
        const fermeture = evenement.target.closest('[data-fermer-apercu]');
        if (fermeture) { fermerApercu(); return; }
        const apercu = evenement.target.closest('[data-apercu]');
        if (apercu) { ouvrirApercu(apercu.dataset.apercu, apercu); return; }
        const rapide = evenement.target.closest('[data-ajout-rapide]');
        if (rapide) {
            rapide.disabled = true;
            try {
                const livre = await lireApercu(rapide.dataset.ajoutRapide), disponibles = livre.etats.filter(x => x.disponible);
                if (disponibles.length !== 1) await ouvrirApercu(livre.id, rapide, livre);
                else { const f = new FormData(); f.set('action', 'ajouter'); f.set('id_exemplaire', disponibles[0].id_exemplaire); await envoyerPanier(f, rapide.closest('.carte-livre')?.querySelector('img')); }
            } catch (erreur) { informer(erreur.message); } finally { rapide.disabled = false; }
        }
        const fleche = evenement.target.closest('[data-carrousel-cible]');
        if (fleche) { document.getElementById(fleche.dataset.carrouselCible)?.scrollBy({left: Number(fleche.dataset.direction || 1) * 320, behavior: reduction.matches ? 'instant' : 'smooth'}); }
    });
    document.addEventListener('submit', async evenement => {
        const formulaire = evenement.target.closest('[data-panier-formulaire]');
        if (!formulaire) return;
        evenement.preventDefault();
        const donnees = new FormData(formulaire);
        if (evenement.submitter?.name) donnees.set(evenement.submitter.name, evenement.submitter.value);
        const boutons = formulaire.querySelectorAll('button'); boutons.forEach(b => b.disabled = true);
        try {
            await envoyerPanier(donnees, formulaire.closest('.fiche-livre, .ligne-panier')?.querySelector('img'));
            if (donnees.get('action') === 'acheter') window.location.assign('panier.php');
        } catch (erreur) { informer(erreur.message); if (document.getElementById('page-panier')) await actualiserPagePanier(); }
        finally { boutons.forEach(b => b.disabled = false); }
    });
    document.addEventListener('change', evenement => {
        if (evenement.target.matches('[data-panier-etat]')) evenement.target.form.requestSubmit();
        if (evenement.target.matches('[data-condition-fiche]')) {
            const champ = evenement.target, bloc = champ.closest('[data-achat-livre]');
            if (!bloc) return;
            bloc.querySelector('[name="id_exemplaire"]').value = champ.value;
            bloc.querySelector('[data-prix-fiche]').textContent = argent(Number(champ.dataset.prix));
            const prixNeuf = Number(bloc.dataset.prixNeuf || 0), prix = Number(champ.dataset.prix);
            const prixBarre = bloc.querySelector('[data-prix-neuf-fiche]'); if (prixBarre) prixBarre.hidden = !(prixNeuf > prix);
            const economie = bloc.querySelector('[data-economie-fiche]');
            if (economie) { economie.hidden = !(prixNeuf > prix); economie.textContent = prixNeuf > prix ? `−${Math.round((1 - prix / prixNeuf) * 100)} %` : ''; }
            const stock = bloc.querySelector('[data-stock-bas]'); if (stock) stock.hidden = champ.dataset.stockBas !== '1';
        }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && etat.expiration) verifierPanier(); });
    afficherTemps();
    setInterval(afficherTemps, 1000);
})();
