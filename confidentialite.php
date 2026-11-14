<?php
require_once 'config.php';
require_once 'infos_legales_lib.php';

$titreDocument = 'Politique de confidentialité';
include 'header.php';
$textePersonnalise = texteLegalPersonnalise('texte_confidentialite');
if ($textePersonnalise !== '') {
    echo '<div class="container my-4 page-legale"><h1 class="titre-page-compte">Politique de confidentialité</h1><section class="carte-compte" style="overflow-wrap:anywhere;">' . nl2br(htmlspecialchars($textePersonnalise, ENT_QUOTES, 'UTF-8')) . '</section></div>';
    include 'footer.php'; exit();
}
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
                    <td data-libelle="Données">Compte : pseudo, adresse email, nom, prénom, empreinte du mot de passe (hachage), ville, code postal, photo de profil</td>
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
                    <td data-libelle="Données">Adresse email d’abonnement, date et texte du choix confirmé, historique des préférences et empreintes des liens de confirmation et de désabonnement</td>
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
            <li>Les polices, feuilles de style et scripts sont hébergés sur le site. Les couvertures sont généralement locales ; certaines images peuvent être chargées depuis Open Library. Si la connexion Google est activée et utilisée, elle communique avec Google pour vérifier votre identité.</li>
        </ul>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-durees">
        <h2 id="titre-durees">Durées de conservation</h2>
        <p>Les durées de conservation doivent encore être validées pour ce site de démonstration avant une ouverture à de vrais utilisateurs. La purge automatique des comptes inactifs et l’anonymisation programmée des transactions ne sont pas encore implémentées.</p>
        <p class="mb-0">Les sessions sont invalidées après 10 minutes d’inactivité, sauf reconnexion automatique choisie avec « Rester connecté » (30 jours au plus). La réservation de panier dure 15 minutes ; le cookie correspondant expire avec elle. Les durées de conservation des comptes, contributions, transactions, messages, candidatures, journaux et sauvegardes doivent être définies et appliquées par le responsable du site.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-cookies">
        <h2 id="titre-cookies">Cookies</h2>
        <p class="mb-0">Les cookies de session PHPSESSID (site) et BIBLIOCCAZ_ADMIN (équipe) assurent l’authentification et la protection des formulaires. Ils sont conservés pour la session du navigateur ; l’authentification est invalidée après 10 minutes d’inactivité. BIBLIOCCAZ_SOUVENIR n’est déposé que si un lecteur coche « Rester connecté » : il contient un identifiant aléatoire (seule son empreinte est conservée en base), renouvelé à chaque reconnexion, valable 30 jours et supprimé à la déconnexion, au changement ou à la réinitialisation du mot de passe et en cas de suspension du compte. BIBLIOCCAZ_PANIER conserve un identifiant aléatoire de réservation, jusqu’à l’expiration des 15 minutes de panier. bandeau_demo_ferme mémorise pendant 30 jours votre fermeture du bandeau de démonstration. Les brouillons de messages peuvent être conservés dans le stockage de l’onglet, jusqu’à l’envoi ou la fermeture de cet onglet. Aucun outil publicitaire ou de mesure d’audience externe n’est activé par le code du site.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-droits">
        <h2 id="titre-droits">Vos droits</h2>
        <p>Vous pouvez accéder à vos données, les rectifier, les effacer, en limiter l'usage, vous opposer à leur traitement, les récupérer dans un format lisible et donner des directives pour après votre décès. Vous pouvez aussi retirer votre consentement à la newsletter à tout moment depuis votre profil ou le lien de désabonnement de chaque lettre, sans compte. Les nouveaux abonnements demandés par adresse email nécessitent une confirmation ; le profil d’un membre déjà authentifié avec une adresse vérifiée permet un choix direct.</p>
        <p>Votre profil permet déjà de modifier la plupart de vos informations. Pour toute autre demande, dont la suppression du compte, écrivez à <?= emailContactLegal() ?> depuis l'adresse de votre compte. Nous répondons sous un mois.</p>
        <p class="mb-0">Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir la <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-securite">
        <h2 id="titre-securite">Sécurité</h2>
        <p class="mb-0">Les échanges sont chiffrés (HTTPS), les mots de passe sont stockés sous forme d’empreintes de hachage non réversibles, et l'accès à l'administration est limité aux membres habilités de l'équipe.</p>
    </section>

    <section class="carte-compte" aria-labelledby="titre-mineurs">
        <h2 id="titre-mineurs">Mineurs</h2>
        <p class="mb-0">Le site de démonstration ne dispose pas encore d’un parcours de vérification du consentement parental. Les conditions d’accès des mineurs doivent être précisées et validées avant une ouverture réelle.</p>
    </section>
</div>

<?php include 'footer.php'; ?>
