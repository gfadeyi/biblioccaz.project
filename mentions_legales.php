<?php
require_once 'config.php';
require_once 'infos_legales_lib.php';

$titreDocument = 'Mentions légales';
include 'header.php';
$textePersonnalise = texteLegalPersonnalise('texte_mentions');
if ($textePersonnalise !== '') {
    echo '<div class="container my-4 page-legale"><h1 class="titre-page-compte">Mentions légales</h1><section class="carte-compte" style="overflow-wrap:anywhere;">' . nl2br(htmlspecialchars($textePersonnalise, ENT_QUOTES, 'UTF-8')) . '</section></div>';
    include 'footer.php'; exit();
}
?>

<div class="container mt-4 mb-5 page-legale">
    <h1 class="titre-page-compte mb-1">Mentions légales</h1>
    <p class="text-muted">Dernière mise à jour : <?= infoLegale('date_mise_a_jour', 'date') ?></p>

    <section class="carte-compte mb-4" aria-labelledby="titre-editeur">
        <h2 id="titre-editeur">Éditeur du site</h2>
        <dl class="liste-legale">
            <dt>Nom</dt>
            <dd><?= infoLegale('editeur_nom', "nom de l'éditeur") ?></dd>
            <dt>Statut</dt>
            <dd><?= infoLegale('editeur_statut', "statut de l'éditeur (particulier, association, entreprise et numéro SIREN…)") ?></dd>
            <dt>Adresse</dt>
            <dd><?= infoLegale('editeur_adresse', 'adresse postale') ?></dd>
            <dt>Contact</dt>
            <dd><?= emailContactLegal() ?></dd>
            <dt>Directeur de la publication</dt>
            <dd><?= infoLegale('directeur_publication', 'nom du directeur de la publication') ?></dd>
        </dl>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-hebergeur">
        <h2 id="titre-hebergeur">Hébergeur</h2>
        <dl class="liste-legale">
            <dt>Nom</dt>
            <dd><?= infoLegale('hebergeur_nom', "raison sociale de l'hébergeur") ?></dd>
            <dt>Adresse</dt>
            <dd><?= infoLegale('hebergeur_adresse', "adresse de l'hébergeur") ?></dd>
            <dt>Téléphone</dt>
            <dd><?= infoLegale('hebergeur_telephone', "téléphone de l'hébergeur") ?></dd>
        </dl>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-nature">
        <h2 id="titre-nature">Nature du site</h2>
        <p>BIBLIOccaz est un projet de librairie d'occasion et de communauté de lecteurs, réalisé dans le cadre d'un projet d'études. Les commandes passées sur le site sont simulées : aucun paiement réel n'est demandé et aucune donnée bancaire n'est collectée.</p>
        <p class="mb-0">Les dons, emprunts, ventes entre lecteurs et événements sont organisés entre les membres et l'équipe BIBLIOccaz selon les règles présentées sur chaque page concernée.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-propriete">
        <h2 id="titre-propriete">Propriété intellectuelle</h2>
        <p>Les textes, le logo et la mise en page de BIBLIOccaz sont protégés. Toute reproduction sans autorisation est interdite.</p>
        <p class="mb-0">Les titres, résumés et couvertures de livres restent la propriété de leurs auteurs et éditeurs. Pour demander le retrait d'un contenu, écrivez à <?= emailContactLegal() ?>.</p>
    </section>

    <section class="carte-compte mb-4" aria-labelledby="titre-contenus">
        <h2 id="titre-contenus">Contenus publiés par les membres</h2>
        <p class="mb-0">Les avis, annonces, photos et messages sont publiés sous la responsabilité de leurs auteurs. Ils sont modérés par l'équipe et peuvent être signalés depuis le site. Un contenu illicite est retiré dès qu'il est porté à notre connaissance.</p>
    </section>

    <section class="carte-compte" aria-labelledby="titre-donnees">
        <h2 id="titre-donnees">Données personnelles</h2>
        <p class="mb-0">Le traitement de vos données est décrit dans la <a href="confidentialite.php">politique de confidentialité</a>.</p>
    </section>
</div>

<?php include 'footer.php'; ?>
