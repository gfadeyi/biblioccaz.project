document.addEventListener('DOMContentLoaded', function () {
    const fenetre = document.getElementById('fenetreConfirmation');
    const modale = fenetre && window.bootstrap?.Modal ? window.bootstrap.Modal.getOrCreateInstance(fenetre) : null;
    let formulaireEnAttente = null;

    document.querySelectorAll('form[data-confirmer]').forEach(function (formulaire) {
        if (modale) {
            formulaire.querySelectorAll('[data-confirmation-js]').forEach(function (champ) { champ.disabled = false; });
            formulaire.querySelectorAll('[data-confirmation-sans-js]').forEach(function (repli) {
                repli.hidden = true;
                repli.querySelectorAll('input').forEach(function (champ) { champ.required = false; });
            });
        }
        formulaire.addEventListener('submit', function (evenement) {
            if (formulaire.dataset.confirme === '1' || !modale) return;
            evenement.preventDefault();
            formulaireEnAttente = formulaire;
            document.getElementById('titreConfirmation').textContent = formulaire.dataset.titreConfirmer || "Confirmer l'action";
            document.getElementById('texteConfirmation').textContent = formulaire.dataset.confirmer;
            document.getElementById('boutonConfirmer').textContent = formulaire.dataset.boutonConfirmer || 'Confirmer';
            modale.show();
        });
    });

    const boutonConfirmer = document.getElementById('boutonConfirmer');
    if (boutonConfirmer) {
        boutonConfirmer.addEventListener('click', function () {
            if (!formulaireEnAttente) return;
            formulaireEnAttente.dataset.confirme = '1';
            formulaireEnAttente.querySelectorAll('[data-confirmation-sans-js] input').forEach(function (champ) { champ.checked = true; });
            boutonConfirmer.disabled = true;
            if (formulaireEnAttente.requestSubmit) {
                formulaireEnAttente.requestSubmit();
            } else {
                formulaireEnAttente.submit();
            }
        });
        fenetre.addEventListener('hidden.bs.modal', function () {
            boutonConfirmer.disabled = false;
            if (formulaireEnAttente && formulaireEnAttente.dataset.confirme !== '1') formulaireEnAttente = null;
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"], .actions-ligne [title], .bouton-action[title]').forEach(function (element) {
        if (!element.getAttribute('aria-label')) element.setAttribute('aria-label', element.getAttribute('title'));
        if (window.bootstrap?.Tooltip) new window.bootstrap.Tooltip(element, { trigger: 'hover focus' });
    });

    document.querySelectorAll('[data-disparition]').forEach(function (message) {
        setTimeout(function () {
            message.classList.add('disparait');
            setTimeout(function () { message.remove(); }, 300);
        }, 6000);
    });

    document.querySelectorAll('[data-graphique]').forEach(function (cadre) {
        const bulle = cadre.querySelector('.bulle-graphique');
        if (!bulle) return;
        cadre.querySelectorAll('.barre-zone').forEach(function (zone) {
            function montrer() {
                const rectangleCadre = cadre.getBoundingClientRect();
                const rectangleBarre = zone.querySelector('.barre').getBoundingClientRect();
                const valeur = document.createElement('strong');
                valeur.textContent = zone.dataset.valeur;
                bulle.textContent = zone.dataset.libelle + ' · ';
                bulle.appendChild(valeur);
                bulle.style.left = (rectangleBarre.left + rectangleBarre.width / 2 - rectangleCadre.left) + 'px';
                bulle.style.top = Math.max(rectangleBarre.top - rectangleCadre.top, 18) + 'px';
                bulle.classList.add('visible');
            }
            zone.addEventListener('mouseenter', montrer);
            zone.addEventListener('focus', montrer);
            zone.addEventListener('mouseleave', function () { bulle.classList.remove('visible'); });
            zone.addEventListener('blur', function () { bulle.classList.remove('visible'); });
        });
    });

    document.querySelectorAll('.table-responsive').forEach(function (zone) {
        if (zone.scrollWidth <= zone.clientWidth + 1 && zone.scrollHeight <= zone.clientHeight + 1) return;
        zone.tabIndex = 0;
        zone.setAttribute('role', 'region');
        if (!zone.hasAttribute('aria-label')) zone.setAttribute('aria-label', 'Tableau à faire défiler');
    });

    const palette = document.getElementById('rechercheRapide');
    if (palette && typeof palette.showModal === 'function') {
        const champ = document.getElementById('rechercheRapideChamp');
        const liste = document.getElementById('rechercheRapideListe');
        const vide = document.getElementById('rechercheRapideVide');
        const simplifier = function (texte) { return texte.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); };
        const pages = [];
        document.querySelectorAll('.admin-menu-lien[data-menu]').forEach(function (lien) {
            let section = '';
            let precedent = lien.previousElementSibling;
            while (precedent && !precedent.classList.contains('admin-menu-section')) precedent = precedent.previousElementSibling;
            if (precedent) section = precedent.textContent.trim();
            const libelle = lien.querySelector('span') ? lien.querySelector('span').textContent.trim() : lien.textContent.trim();
            const icone = lien.querySelector('i') ? lien.querySelector('i').className : 'bi bi-arrow-right';
            pages.push({ libelle: libelle, section: section, adresse: lien.getAttribute('href'), icone: icone, cle: simplifier(libelle + ' ' + section) });
        });
        let actif = 0;
        let visibles = [];

        function marquer() {
            liste.querySelectorAll('[role="option"]').forEach(function (option, index) {
                option.setAttribute('aria-selected', index === actif ? 'true' : 'false');
                if (index === actif) { champ.setAttribute('aria-activedescendant', option.id); option.scrollIntoView({ block: 'nearest' }); }
            });
            if (!visibles.length) champ.removeAttribute('aria-activedescendant');
        }

        function afficher() {
            const mots = simplifier(champ.value).split(/\s+/).filter(Boolean);
            visibles = pages.filter(function (page) { return mots.every(function (mot) { return page.cle.indexOf(mot) !== -1; }); });
            liste.textContent = '';
            visibles.forEach(function (page, index) {
                const option = document.createElement('li');
                option.id = 'rechercheRapideOption' + index;
                option.setAttribute('role', 'option');
                option.className = 'recherche-rapide__option';
                const icone = document.createElement('i');
                icone.className = page.icone;
                icone.setAttribute('aria-hidden', 'true');
                const libelle = document.createElement('span');
                libelle.textContent = page.libelle;
                const section = document.createElement('small');
                section.textContent = page.section;
                option.append(icone, libelle, section);
                option.addEventListener('click', function () { window.location.href = page.adresse; });
                option.addEventListener('mousemove', function () { if (actif !== index) { actif = index; marquer(); } });
                liste.appendChild(option);
            });
            vide.hidden = visibles.length > 0;
            actif = 0;
            marquer();
        }

        function ouvrir() {
            if (palette.open) return;
            champ.value = '';
            afficher();
            palette.showModal();
            champ.focus();
        }

        document.querySelectorAll('[data-recherche-rapide]').forEach(function (bouton) { bouton.addEventListener('click', ouvrir); });
        champ.addEventListener('input', afficher);
        champ.addEventListener('keydown', function (evenement) {
            if (evenement.key === 'ArrowDown' || evenement.key === 'ArrowUp') {
                evenement.preventDefault();
                if (visibles.length) { actif = (actif + (evenement.key === 'ArrowDown' ? 1 : visibles.length - 1)) % visibles.length; marquer(); }
            } else if (evenement.key === 'Enter') {
                evenement.preventDefault();
                if (visibles[actif]) window.location.href = visibles[actif].adresse;
            } else if (evenement.key === 'Escape') {
                evenement.preventDefault();
                palette.close();
            }
        });
        palette.addEventListener('click', function (evenement) { if (evenement.target === palette) palette.close(); });
        document.addEventListener('keydown', function (evenement) {
            const cible = evenement.target;
            const saisie = cible instanceof HTMLElement && (cible.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(cible.tagName));
            const raccourciLettre = (evenement.ctrlKey || evenement.metaKey) && !evenement.altKey && evenement.key.toLowerCase() === 'k';
            const raccourciBarre = evenement.key === '/' && !evenement.ctrlKey && !evenement.metaKey && !evenement.altKey && !saisie;
            if ((raccourciLettre || raccourciBarre) && !document.querySelector('.modal.show')) {
                evenement.preventDefault();
                ouvrir();
            }
        });
    }
});
