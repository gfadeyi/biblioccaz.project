# Checklist de préproduction

État : corrections locales à relire et à valider. Cette liste ne certifie ni la sécurité exhaustive ni la conformité juridique.

## Avant une recette sur un hébergement isolé

- [ ] Valider le périmètre exact des fichiers, le commit et la publication ; conserver les changements de la mission UX/UI.
- [ ] Utiliser une configuration et des données fictives séparées ; bloquer les emails externes et l’indexation.
- [ ] Vérifier PHP/MariaDB, extensions, dépendances verrouillées et espace disque.
- [ ] Vérifier le dossier privé hors racine publique, ses droits et les protections Apache réelles.
- [ ] Tester HTTPS, redirections et cookies Secure sur l’hébergement cible ; si un proxy termine TLS, définir seulement ses adresses fiables.
- [ ] Contrôler les erreurs 403/404/500/503 sans détails techniques ; compléter la gestion des erreurs propres au serveur web.
- [ ] Tester sauvegarde et restauration sur une copie isolée, jamais en écrasant une base active.

## Avant une démonstration publique

- [ ] Faire valider les mentions légales, les CGU et la confidentialité réellement publiées, y compris les textes personnalisés.
- [ ] Garder les paiements, stocks et avis fictifs clairement identifiés ; vérifier les origines des contenus.
- [ ] Vérifier les rôles et l’accès horizontal avec plusieurs comptes fictifs ; limiter les abus et surveiller les journaux.
- [ ] Vérifier les parcours clés sur téléphone, tablette et ordinateur, le clavier et les emails.
- [ ] Vérifier les URL canoniques, les images de partage, robots.txt et le sitemap ; ne pas indexer les copies de recette.
- [ ] Mesurer de nouveau les performances sous réseau et cache contrôlés ; examiner les décalages de mise en page.

## Avant une ouverture réelle

- [ ] Décider du périmètre commercial et faire valider les CGV et règles de vente, dons, prêts et transactions entre membres applicables.
- [ ] Définir et appliquer les durées de conservation, l’anonymisation et la purge des données et sauvegardes.
- [ ] Organiser et tester les demandes d’accès, export, rectification et effacement.
- [x] Valider et installer le lot de confirmation newsletter/désabonnement sans compte ; abonnements historiques sans preuve : à reconfirmer, exclus des campagnes, sans relance automatique.
- [ ] Valider et installer le lot local de jetons de validation email sous empreinte avec expiration ; vérifier les anciens liens après migration et revalider récupération et invitation sur l’hébergement cible.
- [ ] Valider le parcours des mineurs et les accès aux données de la messagerie d’équipe.
- [ ] Réaliser les essais avec lecteur d’écran et une alternative accessible au captcha.
- [ ] Contrôler les versions corrigées des services, les accès de l’équipe, les sauvegardes et la procédure d’incident.
- [ ] Intégrer un prestataire de paiement seulement si le site passe à des transactions réelles, avec une recette dédiée.

Aucun outil d’audience externe n’est nécessaire à la démonstration actuelle. Tout futur ajout exige un choix explicite, une analyse des données collectées et des conditions de consentement ou d’exemption applicables.
