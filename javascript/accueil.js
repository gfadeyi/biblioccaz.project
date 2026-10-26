document.addEventListener('DOMContentLoaded', () => {
    const mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const carrouselAvis = document.querySelector('[data-carrousel-avis]');
    const pointsAvis = document.querySelectorAll('[data-points-avis] button');
    if (carrouselAvis && pointsAvis.length > 1) {
        const avis = carrouselAvis.children;
        let indexActuel = 0;
        let enPause = false;

        const afficherAvis = (index) => {
            indexActuel = (index + avis.length) % avis.length;
            carrouselAvis.scrollTo({ left: indexActuel * carrouselAvis.clientWidth, behavior: mouvementReduit ? 'auto' : 'smooth' });
        };

        pointsAvis.forEach((point, index) => point.addEventListener('click', () => afficherAvis(index)));

        carrouselAvis.addEventListener('scroll', () => {
            const index = Math.round(carrouselAvis.scrollLeft / carrouselAvis.clientWidth);
            pointsAvis.forEach((point, i) => point.classList.toggle('actif', i === index));
            indexActuel = index;
        }, { passive: true });

        const bloc = carrouselAvis.closest('.bloc-communaute');
        bloc.addEventListener('mouseenter', () => { enPause = true; });
        bloc.addEventListener('mouseleave', () => { enPause = false; });
        bloc.addEventListener('focusin', () => { enPause = true; });
        bloc.addEventListener('focusout', () => { enPause = false; });
        bloc.addEventListener('touchstart', () => { enPause = true; }, { passive: true });

        if (!mouvementReduit) {
            setInterval(() => {
                if (!enPause && !document.hidden) {
                    afficherAvis(indexActuel + 1);
                }
            }, 6000);
        }
    }

    document.querySelectorAll('.carrousel').forEach((carrousel) => {
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

        const flecheGauche = creerFleche('gauche', 'Livres précédents');
        const flecheDroite = creerFleche('droite', 'Livres suivants');
        const mettreAJour = () => {
            flecheGauche.hidden = carrousel.scrollLeft < 8;
            flecheDroite.hidden = carrousel.scrollLeft + carrousel.clientWidth >= carrousel.scrollWidth - 8;
        };
        carrousel.addEventListener('scroll', mettreAJour, { passive: true });
        window.addEventListener('resize', mettreAJour);
        mettreAJour();
    });
});
