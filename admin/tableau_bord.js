(function () {
    'use strict';
    const bloc = document.querySelector('[data-tableau-bord]');
    if (!bloc || !window.fetch || !window.AbortController) { return; }
    const etat = bloc.querySelector('[data-tableau-etat]');
    const bilan = bloc.querySelector('[data-tableau-bilan]');
    const source = bloc.dataset.source;
    const base = Math.max(30, Number(bloc.dataset.intervalle) || 60) * 1000;
    const plafond = 5 * 60 * 1000;
    let minuterie = null;
    let controle = null;
    let numero = 0;
    let echecs = 0;
    let arrete = false;
    let derniere = Date.now();

    function dire(texte, lien) {
        if (!etat) { return; }
        etat.textContent = texte;
        if (lien) {
            const a = document.createElement('a');
            a.href = lien.href;
            a.textContent = lien.texte;
            etat.append(' ', a);
        }
    }

    function suspendre() {
        if (minuterie !== null) { clearTimeout(minuterie); minuterie = null; }
        if (controle) { controle.abort(); controle = null; }
    }

    function programmer(delai) {
        if (minuterie !== null) { clearTimeout(minuterie); }
        minuterie = null;
        if (arrete || document.visibilityState !== 'visible') { return; }
        minuterie = setTimeout(actualiser, delai);
    }

    function appliquer(donnees) {
        bloc.querySelectorAll('[data-priorite]').forEach(function (carte) {
            const p = donnees.priorites ? donnees.priorites[carte.dataset.priorite] : undefined;
            if (!p) { return; }
            const valeur = carte.querySelector('[data-priorite-valeur]');
            const detail = carte.querySelector('[data-priorite-detail]');
            const texte = p.valeur === null ? '—' : String(p.valeur);
            if (valeur && valeur.textContent !== texte) { valeur.textContent = texte; }
            const texteDetail = p.valeur === null ? 'Donnée indisponible' : p.detail;
            if (detail && detail.textContent !== texteDetail) { detail.textContent = texteDetail; }
            carte.classList.toggle('indisponible', p.valeur === null);
            carte.classList.toggle('urgente', p.valeur !== null && p.valeur > 0 && carte.dataset.urgence === '1');
        });
        if (bilan && donnees.bilan) {
            if (bilan.textContent !== donnees.bilan.libelle) { bilan.textContent = donnees.bilan.libelle; }
            bilan.classList.toggle('etiquette-alerte', Boolean(donnees.bilan.alerte));
        }
        dire('Actualisé à ' + donnees.heure);
    }

    function arreter(texte, lien) {
        arrete = true;
        suspendre();
        bloc.classList.add('priorites--figees');
        dire(texte, lien);
    }

    function actualiser() {
        minuterie = null;
        if (arrete || controle || document.visibilityState !== 'visible') { return; }
        const courant = ++numero;
        controle = new AbortController();
        fetch(source, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store', signal: controle.signal })
            .then(function (reponse) {
                return reponse.json().catch(function () { return null; }).then(function (corps) { return { statut: reponse.status, corps: corps }; });
            })
            .then(function (resultat) {
                if (courant !== numero) { return; }
                controle = null;
                derniere = Date.now();
                if (resultat.statut === 401) {
                    const message = resultat.corps && resultat.corps.message ? resultat.corps.message : 'Votre session est terminée.';
                    arreter(message, { href: 'login', texte: 'Se reconnecter' });
                    return;
                }
                if (resultat.statut === 403) {
                    arreter('Accès refusé : ces données ne sont plus disponibles pour votre rôle.');
                    return;
                }
                if (resultat.statut !== 200 || !resultat.corps || resultat.corps.ok !== true) {
                    throw new Error('reponse');
                }
                echecs = 0;
                appliquer(resultat.corps);
                programmer(base);
            })
            .catch(function (erreur) {
                if (courant !== numero || (erreur && erreur.name === 'AbortError')) { return; }
                controle = null;
                derniere = Date.now();
                echecs += 1;
                const delai = Math.min(plafond, base * Math.pow(2, echecs));
                dire('Actualisation impossible, nouvel essai dans ' + Math.round(delai / 60000) + ' min.');
                programmer(delai);
            });
    }

    document.addEventListener('visibilitychange', function () {
        if (arrete) { return; }
        if (document.visibilityState !== 'visible') {
            numero += 1;
            suspendre();
            return;
        }
        if (Date.now() - derniere >= base) { actualiser(); } else { programmer(base - (Date.now() - derniere)); }
    });
    window.addEventListener('pagehide', suspendre);
    programmer(base);
})();
