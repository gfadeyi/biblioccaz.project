<?php
require_once 'config.php';
require_once 'infos_legales_lib.php';

$titreDocument = 'Politique de confidentialité';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-legale">
    <h1 class="titre-page-compte mb-1">Politique de confidentialité</h1>
    <p class="text-muted">Dernière mise à jour : <?= infoLegale('date_mise_a_jour', 'date') ?></p>

    <section class="carte-compte mb-4" aria-labelledby="titre-responsable">
        <h2 id="titre-responsable">Responsable du traitement</h2>
        <p class="mb-0">Les données sont traitées par <?= infoLegale('editeur_nom', "nom de l'éditeur") ?>, joignable à <?= emailContactLegal() ?>. BIBLIOccaz ne vend ni ne loue vos données.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-collecte">
        <h2 id="titre-collecte">Données collectées et finalités</h2>
        <table class="table tableau-legal mb-0">
            <caption class="visually-hidden">Données collectées, usages et bases légales</caption>
            <thead>
                <tr><th scope="col">Données</th><th scope="col">Pourquoi</th><th scope="col">Base légale</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td data-libelle="Données">Compte : pseudo, adresse email, nom, prénom, mot de passe chiffré, ville, code postal, photo de profil</td>
                    <td data-libelle="Pourquoi">Créer et sécuriser votre compte, vous identifier auprès des autres membres par votre pseudo</td>
                    <td data-libelle="Base légale">Exécution du service</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Commandes, adresses de livraison, points, bons et badges</td>
                    <td data-libelle="Pourquoi">Traiter les commandes, calculer les réductions et le programme de fidélité</td>
                    <td data-libelle="Base légale">Exécution du service</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Emprunts, dons, annonces et leurs photos, demandes d'achat, inscriptions aux événements et présences</td>
                    <td data-libelle="Pourquoi">Organiser les échanges entre membres et avec l'équipe, envoyer rappels et billets</td>
                    <td data-libelle="Base légale">Exécution du service</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Avis, messages, signalements, candidatures et fiches auteur</td>
                    <td data-libelle="Pourquoi">Publier vos contributions, assurer la modération et la sécurité des échanges</td>
                    <td data-libelle="Base légale">Exécution du service et intérêt légitime</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Favoris, alertes de retour en stock, livres consultés et catégories préférées</td>
                    <td data-libelle="Pourquoi">Vous prévenir et vous proposer des recommandations</td>
                    <td data-libelle="Base légale">Exécution du service et intérêt légitime</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Adresse email d'abonnement à la newsletter</td>
                    <td data-libelle="Pourquoi">Envoyer la lettre d'information mensuelle</td>
                    <td data-libelle="Base légale">Consentement</td>
                </tr>
                <tr>
                    <td data-libelle="Données">Adresse IP, pages consultées, actions réalisées, type d'appareil et dates de connexion</td>
                    <td data-libelle="Pourquoi">Sécuriser le site, détecter les abus, afficher et fermer vos connexions actives</td>
                    <td data-libelle="Base légale">Intérêt légitime</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-destinataires">
        <h2 id="titre-destinataires">Qui voit vos données</h2>
        <ul class="mb-0">
            <li>Les autres membres voient votre pseudo, votre photo de profil et ce que vous publiez (avis, annonces, dons, messages dans une transaction). Votre adresse email n'est jamais affichée.</li>
            <li>L'équipe BIBLIOccaz y accède selon son rôle (administration, gestion, modération).</li>
            <li>L'hébergeur du site les stocke pour notre compte : <?= infoLegale('hebergeur_nom', "nom de l'hébergeur") ?>.</li>
            <li>Les polices, feuilles de style, scripts et couvertures du catalogue sont hébergés sur le site lui-même : leur chargement ne nécessite pas de service tiers.</li>
        </ul>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-durees">
        <h2 id="titre-durees">Durées de conservation</h2>
        <ul class="mb-0">
            <li>Compte et contenus associés : tant que le compte existe. Un compte inactif depuis 3 ans est supprimé ou anonymisé, après un mail de prévenance.</li>
            <li>Commandes, emprunts, dons et ventes : 3 ans après leur clôture, puis anonymisation.</li>
            <li>Messages entre membres : 1 an après la clôture de la transaction.</li>
            <li>Newsletter : jusqu'au désabonnement, possible à tout moment depuis votre profil ou le lien présent dans chaque lettre.</li>
            <li>Journaux de sécurité et adresses IP : 12 mois.</li>
            <li>Cookie de session : il expire à la fermeture du navigateur ou après 10 minutes d'inactivité.</li>
        </ul>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-cookies">
        <h2 id="titre-cookies">Cookies</h2>
        <p class="mb-0">BIBLIOccaz n'utilise qu'un cookie de session, indispensable pour rester connecté, garder votre panier et protéger les formulaires. Il ne sert ni à la publicité ni à la mesure d'audience : aucun consentement n'est donc demandé.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-droits">
        <h2 id="titre-droits">Vos droits</h2>
        <p>Vous pouvez accéder à vos données, les rectifier, les effacer, en limiter l'usage, vous opposer à leur traitement, les récupérer dans un format lisible et donner des directives pour après votre décès. Vous pouvez aussi retirer votre consentement à la newsletter à tout moment.</p>
        <p>Votre profil permet déjà de modifier la plupart de vos informations. Pour toute autre demande, dont la suppression du compte, écrivez à <?= emailContactLegal() ?> depuis l'adresse de votre compte. Nous répondons sous un mois.</p>
        <p class="mb-0">Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir la <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-securite">
        <h2 id="titre-securite">Sécurité</h2>
        <p class="mb-0">Les échanges sont chiffrés (HTTPS), les mots de passe sont stockés sous forme chiffrée et irréversible, et l'accès à l'administration est limité aux membres habilités de l'équipe.</p>
    </section>

    <section class="carte-compte" aria-labelledby="titre-mineurs">
        <h2 id="titre-mineurs">Mineurs</h2>
        <p class="mb-0">Avant 15 ans, la création d'un compte nécessite l'accord d'un parent ou d'un tuteur légal.</p>
    </section>
</div>

<?php include 'footer.php'; ?>
