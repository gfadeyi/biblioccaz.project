document.querySelectorAll('[data-aide-prix-stock]').forEach(aide => {
    const formulaire = aide.closest('form');
    const etat = formulaire.querySelector('[name="etat"]');
    const prix = formulaire.querySelector('[name="prix"]');
    const description = aide.querySelector('[data-description-prix]');
    const bouton = aide.querySelector('[data-utiliser-prix]');
    const message = aide.querySelector('[data-message-prix]');
    const suggestions = JSON.parse(aide.dataset.aidePrixStock);
    const euros = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'});
    aide.querySelector('[data-prix-sans-script]').hidden = true;
    const actualiser = () => {
        const suggestion = suggestions[etat.value];
        description.textContent = suggestion.prix === null ? suggestion.origine : `Prix proposé : ${euros.format(suggestion.prix)}. ${suggestion.origine}`;
        bouton.hidden = suggestion.prix === null;
        bouton.textContent = suggestion.prix === null ? '' : `Utiliser ${euros.format(suggestion.prix)}`;
        message.textContent = '';
    };
    bouton.addEventListener('click', () => {
        const suggestion = suggestions[etat.value];
        if (suggestion.prix === null) return;
        prix.value = Number(suggestion.prix).toFixed(2);
        prix.dispatchEvent(new Event('input', {bubbles: true}));
        message.textContent = 'Prix renseigné. Vous pouvez le modifier avant d’ajouter les exemplaires.';
    });
    etat.addEventListener('change', actualiser);
    actualiser();
});
