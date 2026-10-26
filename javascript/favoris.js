document.addEventListener('click', function (e) {
    const bouton = e.target.closest('button.bouton-coeur');
    if (!bouton) return;
    e.preventDefault();
    const jeton = document.querySelector('meta[name="csrf"]');
    const corps = new URLSearchParams({ action: 'favori_basculer', id_livre: bouton.dataset.livre, csrf: jeton ? jeton.content : '' });
    bouton.disabled = true;
    fetch('compte_action.php', { method: 'POST', body: corps, credentials: 'same-origin' })
        .then(reponse => {
            if (reponse.status === 401) { window.location.href = 'login.php'; return null; }
            return reponse.ok ? reponse.json() : null;
        })
        .then(data => {
            if (!data) return;
            document.querySelectorAll('button.bouton-coeur[data-livre="' + bouton.dataset.livre + '"]').forEach(b => {
                b.classList.toggle('actif', data.favori);
                b.setAttribute('aria-pressed', data.favori ? 'true' : 'false');
                b.title = data.favori ? "Retirer de mes favoris" : "Ajouter à mes favoris";
                b.querySelector('i').className = data.favori ? 'bi bi-heart-fill' : 'bi bi-heart';
                b.classList.remove('anime');
                void b.offsetWidth;
                b.classList.add('anime');
            });
            let compteur = document.getElementById('compteur-favoris');
            const lien = document.querySelector('a[href="wishlist.php"].icone-entete');
            if (!compteur && lien && data.nb > 0) {
                compteur = document.createElement('span');
                compteur.className = 'pastille';
                compteur.id = 'compteur-favoris';
                lien.appendChild(compteur);
            }
            if (compteur) {
                compteur.textContent = data.nb;
                compteur.style.display = data.nb > 0 ? '' : 'none';
            }
        })
        .finally(() => { bouton.disabled = false; });
});
