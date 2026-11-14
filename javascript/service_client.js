(function () {
    const zone = document.getElementById('zone-commande-service');
    const categorie = document.getElementById('service-categorie');
    if (!zone || !categorie) {
        return;
    }
    const categoriesCommande = (zone.dataset.categoriesCommande || '').split(' ');
    const choixCommande = zone.querySelector('select');
    const actualiser = function () {
        const visible = categoriesCommande.indexOf(categorie.value) !== -1;
        zone.hidden = !visible;
        choixCommande.disabled = !visible;
    };
    categorie.addEventListener('change', actualiser);
    actualiser();
})();
