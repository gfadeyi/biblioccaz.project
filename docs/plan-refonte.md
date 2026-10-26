# Plan de refonte de BibliOccaz

Objectif : un site de livres d'occasion communautaire, vivant et donnant envie, qui couvre tous les livres
(manga, BD, romans, jeunesse, scolaire, études, savoirs pro). Inspirations : Recyclivre (occasion, offres par état)
et la Fnac (univers bien rangés, mise en avant des bons plans et des coups de cœur).

Contraintes : PHP pour l'essentiel (rendu des pages côté serveur), un peu de JavaScript seulement là où il apporte
vraiment quelque chose (menu, favoris, petites animations). Le « vivant » vient surtout du HTML/CSS : mise en page,
images, couleurs, transitions, carrousels en CSS.

On avance phase par phase. Chaque phase se termine par un test complet et un commit.

## Phase 0 — Préparation
- Travailler en local dans le dossier du projet.
- Tout commiter, puis créer une branche `refonte` pour pouvoir revenir en arrière.
- Rassembler les captures d'inspiration (Recyclivre, Fnac) dans `docs/inspiration/`.

## Phase 1 — Direction artistique (design system)
- Palette : le vert BibliOccaz comme couleur principale + une couleur d'accent chaude pour les bons plans et les boutons d'action, + fonds clairs.
- Typographies (Google Fonts) : une police à caractère pour les titres, une police très lisible pour le texte.
- Composants réutilisables : carte livre, badge (état, réduction, « coup de cœur »), boutons, puces de filtres, bandeaux, tuiles d'univers.
- `style.css` réorganisé autour de variables CSS (`--vert`, `--accent`, rayons, ombres, espacements) pour que tout le site reste cohérent.
- Valider le style sur une maquette de la page d'accueil avant de toucher au code des autres pages.

## Phase 2 — Catalogue organisé en univers
Passer d'une liste plate de catégories à deux niveaux : **univers → catégories**.

| Univers | Catégories |
|---|---|
| Bons plans | Moins de 5 €, Moins de 10 €, Dernière chance, Coups de cœur, Nouveautés |
| BD, Manga & Jeunesse | BD, Comics, Mangas, Albums (0-6 ans), Romans jeunesse (7-12 ans), Ados, Young adult |
| Littérature | Romans, Policier & thriller, SF & fantasy, Romance, Poésie & théâtre, Classiques |
| Art & Culture | Histoire, Arts & photographie, Musique, Cinéma, Voyages |
| Scolaire & Études | Primaire, Collège, Lycée (manuels & annales), Soutien scolaire, Prépas & concours, Université |
| Savoirs & Pro | Droit, Économie & entreprise, Informatique, Sciences & techniques, Santé, Langues & dictionnaires |
| Vie pratique | Cuisine, Développement personnel, Sport, Maison & jardin |

- Base : `categorie.id_parent` (univers = catégorie sans parent), plus une icône et une couleur par univers. Migration `sql/maj_univers.sql` qui reclasse les catégories existantes.
- « Coups de cœur » = livres les plus ajoutés en favoris et les mieux notés (calculés, pas une catégorie à remplir à la main).
- Scolaire : champs `niveau` (classe) et `matiere` sur les livres concernés ; l'ISBN est essentiel (une édition précise par manuel).
- Back-office : la page Catégories gère les deux niveaux (univers, catégories, ordre, icône).
- Catalogue : filtrer par univers inclut toutes ses catégories.

## Phase 3 — Menu des catégories (méga-menu latéral)
Le bouton « Toutes les catégories » ouvre un panneau à gauche, dans l'esprit du menu latéral du back-office et du menu de la Fnac :
- colonne 1 : la liste des univers avec leur icône ;
- au survol ou au clic, colonne 2 : les catégories de l'univers + 2 ou 3 raccourcis mis en avant (meilleures ventes de l'univers, petits prix) ;
- sur mobile : accordéon simple.
- HTML généré en PHP depuis la base, offcanvas Bootstrap, quelques lignes de JS au plus.

## Phase 4 — Page d'accueil vivante
1. Grand bandeau d'accueil : message court, barre de recherche, boutons « Acheter » / « Donner ou revendre mes livres ».
2. Tuiles des univers (illustrées, couleur de l'univers).
3. Carrousels en CSS (scroll-snap) : Coups de cœur, Petits prix, Nouveautés, Mangas, et selon la période « Rentrée scolaire ».
4. Bloc communauté : derniers dons disponibles, prochains événements, derniers avis des lecteurs.
5. Bandeau de réassurance : livres vérifiés, livraison offerte dès 20 €, points fidélité, seconde vie des livres.

## Phase 5 — Pages clés
- Catalogue : filtres latéraux plus clairs, puces des filtres actifs, tri, nombre de résultats, pagination soignée.
- Fiche livre : grande couverture, choix de l'état sous forme de cartes (comme Recyclivre : prix et stock par état), résumé, caractéristiques, avis, « vous aimerez aussi » (même catégorie / même auteur).
- Espace « Rentrée & études » : recherche par niveau, matière ou ISBN ; mise en avant des manuels disponibles et des dons de manuels.
- Pages univers : en-tête à la couleur de l'univers, catégories en puces, sélection mise en avant.

## Phase 6 — Données de démonstration
- Ajouter des livres d'exemple dans chaque univers (avec couvertures, états et prix variés) pour que le site paraisse vivant en démo.
- Quelques avis, dons et événements d'exemple.

## Phase 7 — Finitions
- Responsive (téléphone, tablette), accessibilité (contrastes, textes alternatifs, navigation au clavier).
- Images optimisées, chargement différé (`loading="lazy"`).
- Harmoniser le back-office avec la nouvelle charte.
- Mettre à jour le README et les captures pour le portfolio.

## Phase 8 — Fonctionnalités restantes du cahier des charges
- Badges et niveaux (F7).
- Revente entre particuliers (F3).
- Emprunt de livres (F5).
