# Consolidation de BibliOccaz

## Livraison 1 : accueil et fiabilité du catalogue

- Bandeau : trois livres parmi les 200 derniers titres éligibles, avec couverture et offre magasin disponible, un par série. Le tirage reste stable pendant la session. « Nouveau chez nous » conserve sa sélection.
- Icône SVG de l'onglet sur le site et dans la mise en page du back-office.
- Orange : séparation des trois adaptations en roman de Yui Tokiumi et des mangas d'Ichigo Takano, par ISBN. Migration rejouable `sql/maj_orange_editions.sql`, sans modification des offres, stocks, comptes ni commandes.
- Le contrôle technique des couvertures vérifie la présence et la lisibilité des fichiers. Il ne prouve pas que chaque illustration correspond à son édition : cette comparaison reste un contrôle bibliographique distinct.

La BnF distingue notamment le manga `9782369740315` des romans `9782369742272`, `9782369742470` et `9782369742708`. Les couvertures des trois romans portent le nom de Yui Tokiumi. La [présentation de l'éditeur](https://www.akata.fr/publications/orange-roman-t1) confirme l'adaptation en roman ; son ISBN affiché comporte une coquille et ne doit pas remplacer les ISBN vérifiés.

## Contrôles nécessitant le propriétaire

### Connexion Google

Les identifiants restent à créer et à renseigner uniquement dans `secrets.php`. Aucun secret ne doit être transmis dans un rapport ou une conversation. L'URI de retour de production est exactement `https://biblioccaz.fr/connexion_google.php`.

Après configuration, vérifier : bouton visible sur connexion et inscription client, création d'un lecteur, reconnexion du même lecteur, liaison à une adresse existante après confirmation du mot de passe, annulation chez Google, refus d'un compte suspendu et d'un compte de l'équipe. Le back-office conserve sa connexion séparée. Sans identifiants, le bouton reste masqué.

### QR codes avec un téléphone

Utiliser des opérations fictives identifiées comme telles, avec les rôles appropriés. Un test sur le site publié modifie effectivement ses données de démonstration ; consigner les opérations réalisées.

1. Emprunt : réserver avec un compte lecteur, afficher son QR depuis « Mes emprunts », puis ouvrir le comptoir avec un gestionnaire sur le téléphone. Scanner un QR affiché sur un autre écran, vérifier la bonne réservation et confirmer le retrait. Contrôler le ticket et la date de retour, puis enregistrer le retour.
2. Don : afficher le QR du donneur ; le bénéficiaire le scanne avec son propre téléphone, vérifie le livre puis confirme le retrait. Le donneur ne valide pas son propre don.
3. Vérifier qu'un second essai ne crée ni double retrait ni deuxième crédit de points. Essayer également un code inconnu et un compte sans droit.
4. Vérifier le refus d'accès à la caméra puis le repli sur le code court. La caméra du navigateur demande un contexte sécurisé, notamment HTTPS sur le site publié.
5. Consigner téléphone, navigateur, date, résultat et références des opérations. Ne pas qualifier le scan physique de réussi avant ces essais.

## Livraison 2 : tarifs des événements

- Prix en euros nullable : vide signifie « Tarif à préciser », zéro signifie « Gratuit », positif signifie « Payant ».
- Saisie côté équipe et propositions d'auteurs ; validation serveur et conservation après erreur.
- Affichage cohérent dans les listes, fiches et billets ; filtre combinable avec type, lieu, date et recherche.
- Reprendre uniquement les tarifs existants dont le texte est sans ambiguïté ; conserver les conditions particulières dans la description.
- Migration additive et rejouable, compatibilité avec l'ancien schéma pendant le déploiement, recette des prix invalides et des droits.
- Les tarifs restent indicatifs dans ce site de démonstration ; ce chantier n'ajoute pas d'encaissement bancaire.

## Livraison 3 : administration et qualité

- Historique des décisions sensibles : acteur, date, objet, décision et motif ; commencer par la validation des fiches livres, dont l'auteur n'est pas enregistré actuellement.
- Écran de santé réservé à l'administrateur : dernières relances, erreurs de mail et état des sauvegardes, sans affichage de secrets ni de contenu privé des journaux.
- Accessibilité : parcours clavier, focus, noms accessibles, erreurs de formulaire et contrastes ; audit complet dans un chantier distinct, avec preuves et limites explicites.

## Conservation des données

Définir et faire valider les durées, exceptions et données à anonymiser avant d'activer une purge. Prévoir une simulation sans écriture, un récapitulatif des lignes concernées, des lots limités et une traçabilité des opérations. Aucune purge automatique ni durée arbitraire n'est activée par cette première livraison.
