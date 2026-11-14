document.addEventListener('DOMContentLoaded', () => {
    const mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const carrouselAvis = document.querySelector('[data-carrousel-avis]');
    const pointsAvis = document.querySelectorAll('[data-points-avis] button');
    if (carrouselAvis) {
        const avis = Array.from(carrouselAvis.querySelectorAll(':scope > .avis-lecteur'));
        const zonePoints = document.querySelector('[data-points-avis]');
        if (!avis.length) {
            const bloc = carrouselAvis.closest('.bloc-communaute');
            if (bloc) bloc.hidden = true;
        } else if (avis.length > 1 && pointsAvis.length === avis.length) {
            let indexActuel = 0;
            carrouselAvis.classList.add('un-par-un');

            const afficherAvis = (index, sens) => {
                indexActuel = (index + avis.length) % avis.length;
                avis.forEach((element, i) => {
                    const visible = i === indexActuel;
                    element.hidden = !visible;
                    element.classList.remove('entre');
                    if (visible && !mouvementReduit) {
                        element.style.setProperty('--decalage-avis', (sens < 0 ? -12 : 12) + 'px');
                        void element.offsetWidth;
                        element.classList.add('entre');
                    }
                });
                pointsAvis.forEach((point, i) => {
                    point.classList.toggle('actif', i === indexActuel);
                    point.setAttribute('aria-pressed', i === indexActuel ? 'true' : 'false');
                });
            };

            pointsAvis.forEach((point, index) => point.addEventListener('click', () => afficherAvis(index, index < indexActuel ? -1 : 1)));

            let departX = null;
            let departY = null;
            carrouselAvis.addEventListener('touchstart', (evenement) => {
                departX = evenement.touches[0].clientX;
                departY = evenement.touches[0].clientY;
            }, { passive: true });
            carrouselAvis.addEventListener('touchend', (evenement) => {
                if (departX === null) return;
                const ecartX = evenement.changedTouches[0].clientX - departX;
                const ecartY = evenement.changedTouches[0].clientY - departY;
                departX = null;
                if (Math.abs(ecartX) > 40 && Math.abs(ecartX) > Math.abs(ecartY)) {
                    afficherAvis(indexActuel + (ecartX < 0 ? 1 : -1), ecartX < 0 ? 1 : -1);
                }
            }, { passive: true });

            afficherAvis(0, 1);
            avis[0].classList.remove('entre');
            if (zonePoints) zonePoints.hidden = false;
        }
    }

    document.querySelectorAll('.carrousel, .grille-univers').forEach((carrousel) => {
        const cadre = document.createElement('div');
        cadre.className = 'carrousel-cadre';
        carrousel.before(cadre);
        cadre.append(carrousel);

        const creerFleche = (sens, libelle) => {
            const fleche = document.createElement('button');
            fleche.type = 'button';
            fleche.className = 'fleche-carrousel fleche-' + sens;
            fleche.setAttribute('aria-label', libelle);
            fleche.innerHTML = '<i class="bi bi-chevron-' + (sens === 'gauche' ? 'left' : 'right') + '"></i>';
            fleche.addEventListener('click', () => {
                const pas = carrousel.clientWidth * 0.8 * (sens === 'gauche' ? -1 : 1);
                carrousel.scrollBy({ left: pas, behavior: mouvementReduit ? 'auto' : 'smooth' });
            });
            cadre.append(fleche);
            return fleche;
        };

        const flecheGauche = creerFleche('gauche', carrousel.dataset.libellePrecedent || 'Livres précédents');
        const flecheDroite = creerFleche('droite', carrousel.dataset.libelleSuivant || 'Livres suivants');
        const mettreAJour = () => {
            flecheGauche.hidden = carrousel.scrollLeft < 8;
            flecheDroite.hidden = carrousel.scrollLeft + carrousel.clientWidth >= carrousel.scrollWidth - 8;
        };
        carrousel.addEventListener('scroll', mettreAJour, { passive: true });
        window.addEventListener('resize', mettreAJour);
        mettreAJour();
    });
});
