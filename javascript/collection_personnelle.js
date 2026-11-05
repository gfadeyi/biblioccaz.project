document.querySelectorAll('[data-selection-collection]').forEach(function (formulaire) {
    const cases = [...formulaire.querySelectorAll('input[name="volumes[]"]:not(:disabled)')];
    const outils = formulaire.querySelector('[data-outils-collection]');
    if (!outils || !cases.length) return;
    const compteur = outils.querySelector('[data-compteur-collection]');
    function actualiserCompteur() {
        const nombre = cases.filter(caseVolume => caseVolume.checked).length;
        compteur.textContent = nombre ? nombre + (nombre === 1 ? ' volume choisi' : ' volumes choisis') + ' pour votre étagère. Pensez à enregistrer.' : 'À vous de choisir les prochains livres de votre étagère !';
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
