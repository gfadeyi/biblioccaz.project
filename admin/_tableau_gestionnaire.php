<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
require_once __DIR__ . '/../messagerie_equipe_lib.php';
require_once __DIR__ . '/../evenements_lib.php';

$moi = (int) $_SESSION['user_id'];
$catalogue = aLeDroit('catalogue');
$evenements = aLeDroit('evenements');
$moderation = aLeDroit('moderation');
if ($catalogue) {
    require_once __DIR__ . '/../emprunts_lib.php';
    verifierEmprunts($pdo);
}
$compteurs = compteursEquipe($pdo);
$nonLusEquipe = nombreNonLusEquipe($pdo, $moi);

$cartes = [];
$commandesRecentes = [];
$stockBas = [];
$nbStockBas = 0;
$rupturesAlertes = [];
$emprunts = [];
$derniereRelance = null;
$prochainsEvenements = [];

if ($catalogue) {
    $commandesValides = "FROM commande WHERE statut <> 'annulee'";
    $ventes = [
        'jour' => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND DATE(date_commande) = CURDATE()"),
        'jour_nb' => compter($pdo, "SELECT COUNT(*) $commandesValides AND DATE(date_commande) = CURDATE()"),
        'mois' => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
        'mois_nb' => compter($pdo, "SELECT COUNT(*) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
    ];
    try {
        $commandesRecentes = $pdo->query("
            SELECT c.id_commande, c.date_commande, c.total, u.pseudo,
                   (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_commande = c.id_commande) AS nb_livres
            FROM commande c LEFT JOIN user u ON u.id = c.id_user
            WHERE c.statut <> 'annulee' AND c.date_commande >= NOW() - INTERVAL 2 DAY
            ORDER BY c.date_commande DESC LIMIT 8")->fetchAll();
        $stockLibre = sqlStockLibrePanier('e');
        $sqlStockBas = "SELECT l.id_livre, l.titre, SUM($stockLibre) AS stock
                        FROM livre l INNER JOIN exemplaire e ON e.id_livre = l.id_livre AND " . conditionOffreMagasin() . "
                        WHERE l.is_valide = 1 GROUP BY l.id_livre, l.titre HAVING stock < " . SEUIL_STOCK_BAS_PUBLIC;
        $nbStockBas = (int) $pdo->query("SELECT COUNT(*) FROM ($sqlStockBas) AS bas")->fetchColumn();
        $stockBas = $pdo->query("$sqlStockBas ORDER BY stock, l.titre LIMIT 6")->fetchAll();
        $rupturesAlertes = $pdo->query("
            SELECT l.id_livre, l.titre, COUNT(a.id_alerte) AS nb_alertes
            FROM alerte_stock a INNER JOIN livre l ON l.id_livre = a.id_livre
            WHERE a.date_envoi IS NULL AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND " . conditionOffreMagasin() . ")
            GROUP BY l.id_livre, l.titre ORDER BY nb_alertes DESC LIMIT 6")->fetchAll();
    } catch (PDOException $e) {
    }
    $emprunts = [
        ['a_preparer', 'À préparer', compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE statut = 'reserve'"), 'emprunts.php?onglet=a_preparer'],
        ['retraits', 'Retirés aujourd’hui', compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE DATE(date_retrait) = CURDATE()"), 'emprunts.php?onglet=en_cours'],
        ['retours_prevus', 'Retours attendus aujourd’hui', compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue = CURDATE()"), 'emprunts.php?onglet=en_cours'],
        ['retours', 'Rendus aujourd’hui', compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE DATE(date_retour) = CURDATE()"), 'emprunts.php'],
        ['retards', 'En retard', compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()"), 'emprunts.php?onglet=en_cours'],
        ['attente', 'En liste d’attente', compter($pdo, "SELECT COUNT(*) FROM emprunt_attente"), 'emprunts.php'],
    ];
    try {
        $derniereRelance = $pdo->query("SELECT date_debut, date_fin, statut, rappels, expirations, erreurs FROM relance_execution ORDER BY date_debut DESC LIMIT 1")->fetch() ?: null;
    } catch (PDOException $e) {
    }
    $relancesDuJour = (int) compter($pdo, "SELECT COUNT(*) FROM emprunt WHERE DATE(date_derniere_relance) = CURDATE()");
    $cartes[] = ['currency-euro', 'Ventes du jour', formaterPrix($ventes['jour']), (int) $ventes['jour_nb'] . ' commande' . ($ventes['jour_nb'] > 1 ? 's' : ''), 'commandes.php', false];
    $cartes[] = ['graph-up-arrow', 'Ventes du mois', formaterPrix($ventes['mois']), (int) $ventes['mois_nb'] . ' commande' . ($ventes['mois_nb'] > 1 ? 's' : ''), 'commandes.php', false];
    $cartes[] = ['box-seam', 'Stock bas', $nbStockBas, 'moins de ' . SEUIL_STOCK_BAS_PUBLIC . ' exemplaires', 'inventaire.php', $nbStockBas > 0];
    $cartes[] = ['bell', 'Ruptures attendues', count($rupturesAlertes), 'avec des clients en attente', 'inventaire.php?stock=rupture', count($rupturesAlertes) > 0];
    $cartes[] = ['alarm', 'Emprunts en retard', (int) $emprunts[4][2], 'relances automatiques', 'emprunts.php?onglet=en_cours', $emprunts[4][2] > 0];
}
$cartes[] = ['chat-left-text', 'Messages de l’équipe', $nonLusEquipe, $nonLusEquipe > 0 ? 'non lu' . ($nonLusEquipe > 1 ? 's' : '') : 'Tout est lu', 'messagerie_equipe.php', $nonLusEquipe > 0];

if ($evenements) {
    try {
        $prochainsEvenements = $pdo->query("
            SELECT e.id_evenement, e.titre, e.date_debut, e.lieu, e.places_max,
                   (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit') AS nb_inscrits,
                   (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'attente') AS nb_attente
            FROM evenement e WHERE e.statut = 'publie' AND e.date_fin >= NOW()
            ORDER BY e.date_debut LIMIT 6")->fetchAll();
    } catch (PDOException $e) {
    }
}

$filesModeration = [];
if ($moderation) {
    $filesModeration = [
        ['shield-check', 'Fiches livres à valider', $compteurs['livres'], 'moderation_livres.php'],
        ['shop', 'Annonces à valider', $compteurs['annonces'] ?? 0, 'annonces.php'],
        ['gift', 'Dons à valider', $compteurs['dons'] ?? 0, 'dons.php?statut=en_validation'],
        ['patch-check', 'Avis à valider', $compteurs['avis_a_valider'] ?? 0, 'avis_a_valider.php'],
        ['flag', 'Avis signalés', $compteurs['avis'], 'moderation_avis.php'],
        ['chat-dots', 'Messages signalés', $compteurs['messages'] ?? 0, 'messages.php'],
        ['people', 'Ventes entre lecteurs à surveiller', $compteurs['ventes'] ?? 0, 'ventes.php'],
    ];
}
if ($evenements) {
    $filesModeration[] = ['calendar-plus', 'Propositions d’événements', $compteurs['evenements_proposes'] ?? 0, 'evenements.php?onglet=proposes'];
}

$titrePage = 'Tableau de bord';
$sousTitrePage = 'Bonjour ' . (($_SESSION['prenom'] ?? '') ?: ($_SESSION['pseudo'] ?? '')) . ', voici la boutique, le stock, les emprunts et les événements au ' . date('d/m/Y') . '.';
$pageActive = 'index';
$actionsPage = [];
if ($catalogue) {
    $actionsPage[] = ['livre.php', 'plus-lg', 'Ajouter un livre', 'btn-vert'];
    $actionsPage[] = ['emprunts.php#code', 'qr-code-scan', 'Comptoir retrait / retour', 'btn-outline-secondary'];
}
if ($evenements) {
    $actionsPage[] = ['evenement.php', 'calendar-plus', 'Créer un événement', 'btn-outline-secondary'];
}
include __DIR__ . '/_haut.php';
?>

<div class="grille-indicateurs mb-4">
    <?php foreach ($cartes as [$icone, $libelle, $valeur, $detail, $lien, $alerte]): ?>
        <div>
            <a href="<?= e($lien) ?>" class="stat">
                <span class="stat-haut"><span class="stat-icone <?= $alerte ? 'alerte' : '' ?>"><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i></span></span>
                <span class="stat-valeur"><?= e((string) $valeur) ?></span>
                <span class="stat-libelle"><?= e($libelle) ?></span>
                <span class="stat-detail"><?= e($detail) ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <?php if ($catalogue): ?>
        <div class="col-xl-6">
            <section class="carte h-100" aria-labelledby="titreCommandes">
                <div class="carte-entete"><h2 id="titreCommandes">Commandes à préparer</h2><a href="commandes.php" class="lien-carte">Toutes les commandes</a></div>
                <p class="carte-corps small text-muted mb-0 pb-0">Commandes validées des dernières 48 heures. Le site de démonstration n’a pas d’étape d’expédition : aucun livre n’est envoyé.</p>
                <ul class="liste-courte mt-2">
                    <?php foreach ($commandesRecentes as $commande): ?>
                        <li>
                            <a href="commande.php?id=<?= (int) $commande['id_commande'] ?>">Commande n° <?= (int) $commande['id_commande'] ?></a>
                            <small>@<?= e($commande['pseudo'] ?? 'compte supprimé') ?> · <?= (int) $commande['nb_livres'] ?> livre<?= $commande['nb_livres'] > 1 ? 's' : '' ?> · <?= formaterPrix((float) $commande['total']) ?> · <?= depuis($commande['date_commande']) ?></small>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$commandesRecentes): ?><li class="vide">Aucune commande depuis deux jours.</li><?php endif; ?>
                </ul>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="carte h-100" aria-labelledby="titreStock">
                <div class="carte-entete"><h2 id="titreStock">Stock</h2><a href="inventaire.php" class="lien-carte">Inventaire</a></div>
                <h3 class="h6 px-3 pt-3 mb-1">Ruptures avec des clients en attente</h3>
                <ul class="liste-courte">
                    <?php foreach ($rupturesAlertes as $livre): ?>
                        <li><a href="exemplaires.php?id=<?= (int) $livre['id_livre'] ?>"><?= e($livre['titre']) ?></a><small><?= (int) $livre['nb_alertes'] ?> alerte<?= $livre['nb_alertes'] > 1 ? 's' : '' ?></small></li>
                    <?php endforeach; ?>
                    <?php if (!$rupturesAlertes): ?><li class="vide">Aucun client n’attend un livre en rupture.</li><?php endif; ?>
                </ul>
                <h3 class="h6 px-3 pt-3 mb-1">Stock bas</h3>
                <ul class="liste-courte">
                    <?php foreach ($stockBas as $livre): ?>
                        <li><a href="exemplaires.php?id=<?= (int) $livre['id_livre'] ?>"><?= e($livre['titre']) ?></a><small><?= (int) $livre['stock'] ?> exemplaire<?= $livre['stock'] > 1 ? 's' : '' ?></small></li>
                    <?php endforeach; ?>
                    <?php if (!$stockBas): ?><li class="vide">Aucun titre en stock bas.</li><?php endif; ?>
                </ul>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="carte h-100" aria-labelledby="titreEmprunts">
                <div class="carte-entete"><h2 id="titreEmprunts">Emprunts du jour</h2><a href="emprunts.php#code" class="lien-carte">Comptoir</a></div>
                <div class="chiffres-ligne">
                    <?php foreach ($emprunts as [, $libelle, $nombre, $lien]): ?>
                        <a href="<?= e($lien) ?>"><strong><?= (int) $nombre ?></strong><span><?= e($libelle) ?></span></a>
                    <?php endforeach; ?>
                </div>
                <div class="carte-corps border-top small">
                    <strong>Suivi des relances :</strong>
                    <?php if ($derniereRelance): ?>
                        dernier passage <?= depuis($derniereRelance['date_debut']) ?> (<?= e($derniereRelance['statut']) ?>, <?= (int) $derniereRelance['rappels'] ?> rappel<?= $derniereRelance['rappels'] > 1 ? 's' : '' ?><?= $derniereRelance['erreurs'] > 0 ? ', ' . (int) $derniereRelance['erreurs'] . ' erreur' . ($derniereRelance['erreurs'] > 1 ? 's' : '') : '' ?>),
                    <?php else: ?>
                        aucun passage enregistré,
                    <?php endif; ?>
                    <?= $relancesDuJour ?> lecteur<?= $relancesDuJour > 1 ? 's' : '' ?> relancé<?= $relancesDuJour > 1 ? 's' : '' ?> aujourd’hui.
                    <a href="relances_emprunts.php">Voir le suivi</a>
                </div>
            </section>
        </div>
    <?php endif; ?>
    <?php if ($evenements): ?>
        <div class="col-xl-6">
            <section class="carte h-100" aria-labelledby="titreEvenements">
                <div class="carte-entete"><h2 id="titreEvenements">Événements à venir</h2><a href="evenements.php" class="lien-carte">Tous les événements</a></div>
                <ul class="liste-courte">
                    <?php foreach ($prochainsEvenements as $evt): ?>
                        <?php $remplissage = $evt['places_max'] > 0 ? min(100, round($evt['nb_inscrits'] / $evt['places_max'] * 100)) : 100; ?>
                        <li>
                            <span>
                                <a href="evenement_participants.php?id=<?= (int) $evt['id_evenement'] ?>"><?= e($evt['titre']) ?></a>
                                <small class="d-block"><?= date('d/m/Y H\hi', strtotime($evt['date_debut'])) ?> · <?= e($evt['lieu']) ?></small>
                                <span class="jauge-places-admin" aria-hidden="true"><span style="width: <?= $remplissage ?>%"></span></span>
                            </span>
                            <small><?= (int) $evt['nb_inscrits'] ?>/<?= (int) $evt['places_max'] ?> inscrits · <?= placesRestantes($evt) ?> place<?= placesRestantes($evt) > 1 ? 's' : '' ?><?= $evt['nb_attente'] > 0 ? ' · ' . (int) $evt['nb_attente'] . ' en attente' : '' ?></small>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$prochainsEvenements): ?><li class="vide">Aucun événement programmé.</li><?php endif; ?>
                </ul>
            </section>
        </div>
    <?php endif; ?>
    <?php if ($filesModeration): ?>
        <div class="col-xl-6">
            <section class="carte h-100" aria-labelledby="titreModeration">
                <?php $totalModeration = array_sum(array_column($filesModeration, 2)); ?>
                <div class="carte-entete">
                    <h2 id="titreModeration">Mes files de modération</h2>
                    <span class="etiquette <?= $totalModeration > 0 ? 'etiquette-alerte' : '' ?>"><?= $totalModeration > 0 ? $totalModeration . ' en attente' : 'Tout est à jour' ?></span>
                </div>
                <ul class="liste-a-traiter">
                    <?php foreach ($filesModeration as [$icone, $libelle, $nombre, $lien]): ?>
                        <li>
                            <a href="<?= e($lien) ?>" class="ligne-a-traiter">
                                <span class="compte <?= $nombre > 0 ? '' : 'zero' ?>"><?= $nombre > 0 ? (int) $nombre : '<i class="bi bi-check-lg" aria-label="Aucun"></i>' ?></span>
                                <span><strong><?= e($libelle) ?></strong></span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
