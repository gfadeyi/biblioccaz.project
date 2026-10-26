document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.choix-livres').forEach(function (bloc) {
        const filtre = bloc.querySelector('.choix-livres__filtre');
        const compteur = bloc.querySelector('.choix-livres__compteur');
        const livres = Array.from(bloc.querySelectorAll('.choix-livres__livre'));
        const cases = livres.map(function (livre) { return livre.querySelector('input'); });
        const maximum = parseInt(bloc.dataset.maximum, 10);
        if (!livres.length) return;

        function mettreAJour() {
            const nombreCoches = cases.filter(function (c) { return c.checked; }).length;
            compteur.textContent = nombreCoches + ' / ' + maximum;
            cases.forEach(function (c) {
                c.disabled = !c.checked && nombreCoches >= maximum;
                c.closest('.choix-livres__livre').classList.toggle('coche', c.checked);
            });
        }

        if (filtre) {
            filtre.hidden = false;
            filtre.addEventListener('input', function () {
                const recherche = filtre.value.trim().toLowerCase();
                livres.forEach(function (livre) {
                    livre.hidden = recherche !== '' && !livre.dataset.recherche.includes(recherche);
                });
            });
        }
        cases.forEach(function (c) { c.addEventListener('change', mettreAJour); });
        mettreAJour();
    });
});
