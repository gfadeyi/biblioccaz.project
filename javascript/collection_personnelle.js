document.querySelectorAll('[data-selection-collection]').forEach(function (formulaire) {
    const cases = [...formulaire.querySelectorAll('input[name="volumes[]"]:not(:disabled)')];
    const outils = formulaire.querySelector('[data-outils-collection]');
    if (!outils || !cases.length) return;
    const compteur = outils.querySelector('[data-compteur-collection]');
    const etagere = formulaire.querySelector('[data-etagere-collection]');
    let derniersChoix = new Set(cases.filter(caseVolume => caseVolume.checked).map(caseVolume => caseVolume.value));
    function actualiserEtagere() {
        if (!etagere) return;
        const choisis = cases.filter(caseVolume => caseVolume.checked);
        const niveaux = [...etagere.querySelectorAll('[data-etagere-niveau]')];
        const fragments = niveaux.map(() => document.createDocumentFragment());
        choisis.slice(0, 15).forEach(function (caseVolume, index) {
            const carte = caseVolume.closest('.collection-volume');
            const tranche = document.createElement('span');
            tranche.className = 'etagere-livre';
            tranche.title = carte.querySelector('.collection-volume__titre').textContent.trim();
            if (!derniersChoix.has(caseVolume.value)) tranche.classList.add('vient-arriver');
            const couverture = document.createElement('img');
            couverture.src = carte.querySelector('.collection-volume__couverture img').getAttribute('src');
            couverture.alt = '';
            couverture.decoding = 'async';
            couverture.width = 160;
            couverture.height = 220;
            tranche.append(couverture);
            fragments[Math.floor(index / 5)].append(tranche);
        });
        niveaux.forEach((niveau, index) => niveau.replaceChildren(fragments[index]));
        derniersChoix = new Set(choisis.map(caseVolume => caseVolume.value));
        etagere.querySelector('[data-etagere-message]').textContent = choisis.length === 0 ? 'Votre étagère attend ses premières trouvailles.' : choisis.length > 15 ? '15 volumes exposés et ' + (choisis.length - 15) + ' autres dans votre collection.' : choisis.length + (choisis.length === 1 ? ' volume sur votre étagère.' : ' volumes sur votre étagère.');
    }
    function actualiserCompteur() {
        const nombre = cases.filter(caseVolume => caseVolume.checked).length;
        compteur.textContent = nombre ? nombre + (nombre === 1 ? ' volume choisi' : ' volumes choisis') + ' pour votre étagère. Pensez à enregistrer.' : 'À vous de choisir les prochains livres de votre étagère !';
        actualiserEtagere();
    }
    function cocherTout(cocher) {
        cases.forEach(caseVolume => { caseVolume.checked = cocher; });
        actualiserCompteur();
    }
    outils.querySelector('[data-tout-cocher]').addEventListener('click', function () { cocherTout(true); });
    outils.querySelector('[data-tout-decocher]').addEventListener('click', function () { cocherTout(false); });
    cases.forEach(caseVolume => caseVolume.addEventListener('change', actualiserCompteur));
    actualiserCompteur();
    outils.hidden = false;
});
