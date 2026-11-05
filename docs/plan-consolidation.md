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

Le propriétaire confirme le 4 novembre 2026 que le scan des dons fonctionne sur son téléphone. Cette confirmation ne vaut pas validation physique des emprunts, qui reste à réaliser ; le modèle du téléphone et le navigateur n'ont pas été consignés.

Utiliser des opérations fictives identifiées comme telles, avec les rôles appropriés. Un test sur le site publié modifie effectivement ses données de démonstration ; consigner les opérations réalisées.

1. Emprunt : réserver avec un compte lecteur, afficher son QR depuis « Mes emprunts », puis ouvrir le comptoir avec un gestionnaire sur le téléphone. Scanner un QR affiché sur un autre écran, vérifier la bonne réservation et confirmer le retrait. Contrôler le ticket et la date de retour, puis enregistrer le retour.
2. Don : afficher le QR du donneur ; le bénéficiaire le scanne avec son propre téléphone, vérifie le livre puis confirme le retrait. Le donneur ne valide pas son propre don.
3. Vérifier qu'un second essai ne crée ni double retrait ni deuxième crédit de points. Essayer également un code inconnu et un compte sans droit.
4. Vérifier le refus d'accès à la caméra puis le repli sur le code court. La caméra du navigateur demande un contexte sécurisé, notamment HTTPS sur le site publié.
5. Consigner téléphone, navigateur, date, résultat et références des opérations. Ne pas qualifier le scan physique de réussi avant ces essais.

## Livraison 2 : événements gratuits et historique des décisions

Le propriétaire maintient tous les événements gratuits et annule le chantier de prix structuré et de filtre gratuit/payant.

- Gratuité annoncée sur le calendrier, les fiches et les formulaires de création/proposition ; suppression ciblée des neuf mentions de tarifs de démonstration. Aucun changement d'inscription, de date ou de capacité.
- Historique en lecture seule des nouvelles décisions sur les fiches livres, archives du catalogue, annonces, dons, avis, propositions d'événements et candidatures à l'équipe.
- Auteur, pseudo au moment de la décision, date, identifiant et libellé de l'objet, action, états avant/après et motif lorsqu'il est fourni. Les anciens auteurs inconnus ne sont pas inventés.
- Recherche, filtres et pagination ; seuls les domaines permis par les droits actuels sont lisibles. Les candidatures à l'équipe restent réservées à l'administrateur.
- Décision et trace enregistrées ensemble, avec retour arrière de l'opération si le journal échoue. Pendant une mise à jour sans la table, les anciennes actions restent utilisables et la page indique que l'historique n'est pas encore activé.
- Les historiques métier existants sont conservés. Cette première version ne centralise pas les décisions sur les messages, litiges de ventes, candidatures auteur, ni tous les changements de comptes ou de paramètres.

## Livraison 3 : administration et qualité

- Étendre progressivement l'historique aux autres décisions sensibles, avec une recette par domaine.
- Écran de santé livré le 5 novembre 2026, réservé à l'administrateur : dernières relances, erreurs de mail et entrées du journal PHP repérées, exports SQL et dossiers de copie du code détectés. Lecture seule, limites explicites ; aucune modification de la tâche planifiée, envoi de test, restauration ou programmation de sauvegarde.
- Accessibilité : parcours clavier, focus, noms accessibles, erreurs de formulaire et contrastes ; audit complet dans un chantier distinct, avec preuves et limites explicites.

## Livraison 4 : catalogue et choix de lecture

- Auteur de la série manga Fullmetal Alchemist corrigé par `sql/maj_fullmetal_auteur.sql`, sans déplacer les romans déjà séparés ni modifier les stocks.
- Vérifications techniques des ISBN, rattachements et fichiers de couverture ; les éditions d'un même tome sont conservées. Les contrôles automatiques ne garantissent pas la correspondance visuelle de chaque couverture ni la complétude des séries.
- Parcours public « Trouver ma prochaine lecture » : univers, ambiance et budget par livre ; jusqu'à six livres disponibles en magasin, un par série, puis accès au catalogue avec les mêmes filtres. Fonctionne sans compte et sans JavaScript, en complément des suggestions personnalisées.

## Livraison 5 : compléter sa collection

- Suivi personnel des séries et volumes possédés pour les lecteurs et auteurs, accessible depuis une série ou le menu du compte.
- Rééditions d'un même volume regroupées dans leur série ; formats simples, doubles et œuvres non numérotées distincts. Aucun rapprochement automatique entre différentes séries Perfect/Deluxe.
- Volumes manquants parmi les seules références publiques du catalogue, offres magasin disponibles et prix actuels. La liste ne prétend pas attester une collection bibliographiquement complète.
- Deux tables de suivi privé, formulaires POST avec CSRF et transactions ; aucune modification des livres, stocks ou commandes. Les alertes automatiques de collection restent pour une deuxième version.

## Conservation des données

Définir et faire valider les durées, exceptions et données à anonymiser avant d'activer une purge. Prévoir une simulation sans écriture, un récapitulatif des lignes concernées, des lots limités et une traçabilité des opérations. Aucune purge automatique ni durée arbitraire n'est activée par cette première livraison.
