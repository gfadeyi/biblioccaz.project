<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../categories_lib.php';
require_once __DIR__ . '/../livres_lib.php';
exigerDroit('equipe');

function compter(PDO $pdo, string $sql): float
{
    try {
        return (float) $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function tendance(float $actuel, float $precedent): array
{
    if ($precedent <= 0) {
        return $actuel > 0 ? ['hausse', 'arrow-up-right', 'Nouveau'] : ['stable', 'dash', 'Stable'];
    }
    $variation = (int) round(($actuel - $precedent) / $precedent * 100);
    if ($variation === 0) {
        return ['stable', 'dash', 'Stable'];
    }
    return $variation > 0 ? ['hausse', 'arrow-up-right', '+' . $variation . ' %'] : ['baisse', 'arrow-down-right', $variation . ' %'];
}

$commandesValides = "FROM commande WHERE statut <> 'annulee'";
$finances = aLeDroit('admin');

$indicateurs = [
    'ca_jour'         => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND DATE(date_commande) = CURDATE()"),
    'ca_hier'         => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND DATE(date_commande) = CURDATE() - INTERVAL 1 DAY"),
    'ca_mois'         => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
    'ca_mois_avant'   => compter($pdo, "SELECT COALESCE(SUM(total), 0) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND date_commande < NOW() - INTERVAL 1 MONTH"),
    'cmd_mois'        => compter($pdo, "SELECT COUNT(*) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
    'cmd_mois_avant'  => compter($pdo, "SELECT COUNT(*) $commandesValides AND date_commande >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND date_commande < NOW() - INTERVAL 1 MONTH"),
    'membres_30j'     => compter($pdo, "SELECT COUNT(*) FROM user WHERE date_inscription >= NOW() - INTERVAL 30 DAY"),
    'membres_30j_avant' => compter($pdo, "SELECT COUNT(*) FROM user WHERE date_inscription >= NOW() - INTERVAL 60 DAY AND date_inscription < NOW() - INTERVAL 30 DAY"),
    'stock'           => compter($pdo, "SELECT COALESCE(SUM(e.quantite), 0) FROM exemplaire e WHERE " . conditionOffreMagasin()),
    'titres'          => compter($pdo, "SELECT COUNT(*) FROM livre WHERE is_valide = 1"),
    'rupture'         => compter($pdo, "SELECT COUNT(*) FROM livre l WHERE l.is_valide = 1 AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND " . conditionOffreMagasin() . ")"),
    'alertes_clients' => compter($pdo, "SELECT COUNT(*) FROM alerte_stock WHERE date_envoi IS NULL"),
    'dons'            => compter($pdo, "SELECT COUNT(*) FROM don WHERE statut IN ('disponible', 'reserve') AND date_limite >= CURDATE()"),
    'dons_reserves'   => compter($pdo, "SELECT COUNT(*) FROM don WHERE statut = 'reserve' AND date_limite >= CURDATE()"),
    'evenements'      => compter($pdo, "SELECT COUNT(*) FROM evenement WHERE statut = 'publie' AND date_fin >= NOW()"),
    'evt_proposes'    => compter($pdo, "SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'"),
];
if (aLeDroit('catalogue')) {
    require_once __DIR__ . '/../emprunts_lib.php';
    verifierEmprunts($pdo);
}
$compteurs = compteursEquipe($pdo);
$aModerer = $compteurs['livres'] + $compteurs['avis'] + $compteurs['annonces'] + ($compteurs['messages'] ?? 0) + ($compteurs['ventes'] ?? 0);

$prochainEvenement = null;
try {
    $prochainEvenement = $pdo->query("SELECT titre, date_debut FROM evenement WHERE statut = 'publie' AND date_debut >= NOW() ORDER BY date_debut LIMIT 1")->fetch() ?: null;
} catch (PDOException $e) {
}

$ventesParJour = [];
$ventesParUnivers = [];
if ($finances) {
    $jours = [];
    for ($decalage = 29; $decalage >= 0; $decalage--) {
        $jours[date('Y-m-d', strtotime("-$decalage day"))] = 0.0;
    }
    try {
        $lignes = $pdo->query("SELECT DATE(date_commande) AS jour, SUM(total) AS montant $commandesValides AND date_commande >= CURDATE() - INTERVAL 29 DAY GROUP BY DATE(date_commande)")->fetchAll();
        foreach ($lignes as $ligne) {
            $jours[$ligne['jour']] = (float) $ligne['montant'];
        }
        $ventesParUnivers = $pdo->query("
            SELECT COALESCE(u.nom, c.nom, 'Sans catégorie') AS univers, COALESCE(u.couleur, c.couleur) AS couleur, COALESCE(u.icone, c.icone) AS icone,
                   SUM(cl.prix_unitaire * cl.quantite) AS montant, SUM(cl.quantite) AS livres
            FROM commande_ligne cl
            INNER JOIN commande co ON co.id_commande = cl.id_commande AND co.statut <> 'annulee'
            LEFT JOIN livre l ON l.id_livre = cl.id_livre
            LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
            LEFT JOIN categorie u ON u.id_categorie = c.id_parent
            WHERE co.date_commande >= CURDATE() - INTERVAL 29 DAY
            GROUP BY univers, couleur, icone
            ORDER BY montant DESC")->fetchAll();
    } catch (PDOException $e) {
    }
    $ventesParJour = $jours;
}

$livresEnAttenteAlerte = [];
try {
    $livresEnAttenteAlerte = $pdo->query("
        SELECT l.id_livre, l.titre, COUNT(a.id_alerte) AS nb_alertes
        FROM alerte_stock a INNER JOIN livre l ON l.id_livre = a.id_livre
        WHERE a.date_envoi IS NULL AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND " . conditionOffreMagasin() . ")
        GROUP BY l.id_livre, l.titre ORDER BY nb_alertes DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {
}

$activite = [];
try {
    $activite = $pdo->query("
        (SELECT 'commande' AS type, c.date_commande AS date_evt, u.pseudo AS qui, CONCAT('Commande n° ', c.id_commande) AS quoi, c.total AS montant
         FROM commande c INNER JOIN user u ON u.id = c.id_user ORDER BY c.date_commande DESC LIMIT 6)
        UNION ALL
        (SELECT 'membre', date_inscription, pseudo, 'Nouveau membre', NULL FROM user WHERE date_inscription IS NOT NULL ORDER BY date_inscription DESC LIMIT 6)
        UNION ALL
        (SELECT 'avis', a.date_creation, u.pseudo, CONCAT('Avis ', a.note, '/5 sur « ', l.titre, ' »'), NULL
         FROM avis a INNER JOIN user u ON u.id = a.id_user INNER JOIN livre l ON l.id_livre = a.id_livre ORDER BY a.date_creation DESC LIMIT 6)
        UNION ALL
        (SELECT 'don', d.date_creation, u.pseudo, CONCAT('Don proposé : « ', d.titre, ' »'), NULL
         FROM don d INNER JOIN user u ON u.id = d.id_donneur ORDER BY d.date_creation DESC LIMIT 6)
        UNION ALL
        (SELECT 'evenement', i.date_inscription, u.pseudo, CONCAT('Inscription : ', e.titre), NULL
         FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user INNER JOIN evenement e ON e.id_evenement = i.id_evenement ORDER BY i.date_inscription DESC LIMIT 6)
        ORDER BY date_evt DESC LIMIT 8")->fetchAll();
} catch (PDOException $e) {
}
$iconesActivite = ['commande' => 'bag-check', 'membre' => 'person-plus', 'avis' => 'chat-square-text', 'don' => 'gift', 'evenement' => 'calendar-check'];

function depuis(string $date): string
{
    $secondes = time() - strtotime($date);
    if ($secondes < 3600) {
        return 'il y a ' . max(1, intdiv($secondes, 60)) . ' min';
    }
    if ($secondes < 86400) {
        return 'il y a ' . intdiv($secondes, 3600) . ' h';
    }
    if ($secondes < 7 * 86400) {
        $jours = intdiv($secondes, 86400);
        return 'il y a ' . $jours . ' jour' . ($jours > 1 ? 's' : '');
    }
    return 'le ' . date('d/m/Y', strtotime($date));
}

$cartes = [];
if ($finances) {
    $cartes[] = ['currency-euro', "Chiffre d'affaires du jour", formaterPrix($indicateurs['ca_jour']), tendance($indicateurs['ca_jour'], $indicateurs['ca_hier']), "par rapport à hier", null, false];
    $cartes[] = ['graph-up-arrow', "Chiffre d'affaires du mois", formaterPrix($indicateurs['ca_mois']), tendance($indicateurs['ca_mois'], $indicateurs['ca_mois_avant']), "vs même période le mois dernier", null, false];
    $cartes[] = ['bag-check', 'Commandes du mois', (int) $indicateurs['cmd_mois'], tendance($indicateurs['cmd_mois'], $indicateurs['cmd_mois_avant']), "vs même période le mois dernier", null, false];
    $cartes[] = ['person-plus', 'Nouveaux membres', (int) $indicateurs['membres_30j'], tendance($indicateurs['membres_30j'], $indicateurs['membres_30j_avant']), "30 derniers jours", 'utilisateurs.php', false];
}
if (aLeDroit('catalogue')) {
    $cartes[] = ['box-seam', 'Livres en stock', (int) $indicateurs['stock'], null, (int) $indicateurs['titres'] . ' titres au catalogue', 'inventaire.php', false];
    $cartes[] = ['exclamation-triangle', 'Titres en rupture', (int) $indicateurs['rupture'], null, (int) $indicateurs['alertes_clients'] . ' client' . ($indicateurs['alertes_clients'] > 1 ? 's' : '') . ' en attente', 'inventaire.php?stock=rupture', $indicateurs['rupture'] > 0];
}
if (aLeDroit('moderation')) {
    $cartes[] = ['shield-check', 'À modérer', $aModerer, null, $compteurs['livres'] . ' fiche' . ($compteurs['livres'] > 1 ? 's' : '') . ' · ' . $compteurs['avis'] . ' avis signalé' . ($compteurs['avis'] > 1 ? 's' : ''), $compteurs['livres'] > 0 ? 'moderation_livres.php' : 'moderation_avis.php', $aModerer > 0];
}
$cartes[] = ['gift', 'Dons en cours', (int) $indicateurs['dons'], null, (int) $indicateurs['dons_reserves'] . ' réservé' . ($indicateurs['dons_reserves'] > 1 ? 's' : '') . ', à récupérer', 'dons.php', false];
if (aLeDroit('evenements')) {
    $cartes[] = ['calendar-event', 'Événements à venir', (int) $indicateurs['evenements'], null,
        $prochainEvenement ? 'Prochain : ' . date('d/m', strtotime($prochainEvenement['date_debut'])) . ' · ' . mb_strimwidth($prochainEvenement['titre'], 0, 26, '…') : 'Aucun programmé', 'evenements.php', false];
}

$aTraiter = [];
if (aLeDroit('moderation')) {
    $aTraiter[] = ['shield-check', 'Fiches de livres à valider', 'Proposées par les auteurs', $compteurs['livres'], 'moderation_livres.php'];
    $aTraiter[] = ['shop', 'Annonces à valider', 'Ventes entre particuliers', $compteurs['annonces'], 'annonces.php'];
    $aTraiter[] = ['flag', 'Avis signalés', 'Signalements des lecteurs', $compteurs['avis'], 'moderation_avis.php'];
    $aTraiter[] = ['chat-dots', 'Messages signalés', 'Conversations entre membres', $compteurs['messages'] ?? 0, 'messages.php'];
    $aTraiter[] = ['people', 'Ventes à surveiller', 'Litiges et ventes en retard entre lecteurs', $compteurs['ventes'] ?? 0, 'ventes.php'];
}
if (aLeDroit('catalogue')) {
    $detailAlertes = $livresEnAttenteAlerte
        ? implode(', ', array_map(fn($l) => '« ' . mb_strimwidth($l['titre'], 0, 22, '…') . ' » (' . $l['nb_alertes'] . ')', $livresEnAttenteAlerte))
        : 'Aucun client en attente';
    $aTraiter[] = ['bookmark-check', "Réservations d'emprunt à préparer", 'À sortir du rayon pour le retrait en magasin', $compteurs['emprunts_a_preparer'] ?? 0, 'emprunts.php?onglet=a_preparer'];
    $aTraiter[] = ['alarm', 'Emprunts en retard', 'Livres à récupérer, relances envoyées automatiquement', $compteurs['emprunts_en_retard'] ?? 0, 'emprunts.php?onglet=en_cours'];
    $aTraiter[] = ['chat-dots', 'Questions sur les emprunts', "Messages de lecteurs en attente de réponse de l'équipe", $compteurs['emprunts_questions'] ?? 0, 'emprunts.php'];
    $aTraiter[] = ['bell', 'Ruptures avec alertes clients', $detailAlertes, count($livresEnAttenteAlerte), 'inventaire.php?stock=rupture'];
}
if (aLeDroit('evenements')) {
    $aTraiter[] = ['calendar-plus', 'Événements proposés par les auteurs', 'En attente de validation', (int) $indicateurs['evt_proposes'], 'evenements.php?onglet=proposes'];
}
if (aLeDroit('admin')) {
    $aTraiter[] = ['person-badge', 'Candidatures modérateur', 'Membres à valider', $compteurs['candidatures'], 'candidatures.php'];
}
$totalATraiter = array_sum(array_column($aTraiter, 3));

$titrePage = 'Tableau de bord';
$sousTitrePage = 'Bonjour ' . (($_SESSION['prenom'] ?? '') ?: ($_SESSION['pseudo'] ?? '')) . ', voici l\'état de la boutique au ' . date('d/m/Y') . '.';
$pageActive = 'index';
$actionsPage = [];
if (aLeDroit('catalogue')) {
    $actionsPage[] = ['livre.php', 'plus-lg', 'Ajouter un livre', 'btn-vert'];
}
if (aLeDroit('evenements')) {
    $actionsPage[] = ['evenement.php', 'calendar-plus', 'Créer un événement', 'btn-outline-secondary'];
}
include __DIR__ . '/_haut.php';

$maxJour = $ventesParJour ? max(max($ventesParJour), 1) : 1;
$totalUnivers = $ventesParUnivers ? max(array_column($ventesParUnivers, 'montant')) : 1;
$largeurGraphique = 600;
$hauteurGraphique = 180;
$pasBarre = $largeurGraphique / 30;
?>

<div class="grille-indicateurs mb-4">
    <?php foreach ($cartes as [$icone, $libelle, $valeur, $tendanceCarte, $detail, $lien, $alerte]): ?>
        <div class="<?= $tendanceCarte ? 'indicateur-cle' : '' ?>">
            <<?= $lien ? 'a href="' . e($lien) . '"' : 'div' ?> class="stat">
                <span class="stat-haut">
                    <span class="stat-icone <?= $alerte ? 'alerte' : '' ?>"><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i></span>
                    <?php if ($tendanceCarte): ?>
                        <span class="tendance tendance-<?= $tendanceCarte[0] ?>"><i class="bi bi-<?= $tendanceCarte[1] ?>" aria-hidden="true"></i><?= $tendanceCarte[2] ?></span>
                    <?php endif; ?>
                </span>
                <span class="stat-valeur"><?= e((string) $valeur) ?></span>
                <span class="stat-libelle"><?= e($libelle) ?></span>
                <span class="stat-detail"><?= e($detail) ?></span>
            </<?= $lien ? 'a' : 'div' ?>>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($finances): ?>
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="carte h-100" aria-labelledby="titreVentesJour">
                <div class="carte-entete">
                    <h2 id="titreVentesJour">Ventes des 30 derniers jours</h2>
                    <span class="text-muted small fw-normal text-nowrap">Total : <strong class="text-body"><?= formaterPrix(array_sum($ventesParJour)) ?></strong></span>
                </div>
                <div class="carte-corps">
                    <div class="cadre-graphique" data-graphique>
                        <div class="bulle-graphique" aria-hidden="true"></div>
                        <svg class="graphique-barres" viewBox="0 0 <?= $largeurGraphique + 40 ?> <?= $hauteurGraphique + 28 ?>" role="img" aria-label="Chiffre d'affaires par jour sur 30 jours">
                            <?php foreach ([0, .5, 1] as $part): ?>
                                <?php $y = 6 + $hauteurGraphique * (1 - $part); ?>
                                <line class="grille" x1="40" x2="<?= $largeurGraphique + 40 ?>" y1="<?= $y ?>" y2="<?= $y ?>"></line>
                                <text class="axe-texte" x="34" y="<?= $y + 4 ?>" text-anchor="end"><?= number_format($maxJour * $part, 0, ',', ' ') ?> €</text>
                            <?php endforeach; ?>
                            <?php $indexJour = 0; foreach ($ventesParJour as $jour => $montant): ?>
                                <?php
                                $hauteurBarre = $montant > 0 ? max(3, $hauteurGraphique * $montant / $maxJour) : 0;
                                $x = 40 + $indexJour * $pasBarre;
                                ?>
                                <g class="barre-zone" tabindex="0" data-libelle="<?= date('d/m', strtotime($jour)) ?>" data-valeur="<?= formaterPrix($montant) ?>">
                                    <rect class="zone-survol" x="<?= $x ?>" y="6" width="<?= $pasBarre ?>" height="<?= $hauteurGraphique ?>"></rect>
                                    <rect class="barre" x="<?= $x + 2 ?>" y="<?= 6 + $hauteurGraphique - $hauteurBarre ?>" width="<?= $pasBarre - 4 ?>" height="<?= $hauteurBarre ?>" rx="3"></rect>
                                </g>
                                <?php if ((29 - $indexJour) % 7 === 0): ?>
                                    <text class="axe-texte" x="<?= $x + $pasBarre / 2 ?>" y="<?= $hauteurGraphique + 24 ?>" text-anchor="middle"><?= date('d/m', strtotime($jour)) ?></text>
                                <?php endif; ?>
                            <?php $indexJour++; endforeach; ?>
                        </svg>
                    </div>
                    <details class="donnees-graphique">
                        <summary>Voir les données</summary>
                        <div class="table-responsive mt-2" style="max-height: 220px;">
                            <table class="table table-sm small">
                                <thead><tr><th>Jour</th><th class="text-end">Chiffre d'affaires</th></tr></thead>
                                <tbody>
                                    <?php foreach (array_reverse($ventesParJour, true) as $jour => $montant): ?>
                                        <?php if ($montant <= 0) continue; ?>
                                        <tr><td><?= date('d/m/Y', strtotime($jour)) ?></td><td class="text-end"><?= formaterPrix($montant) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="carte h-100" aria-labelledby="titreVentesUnivers">
                <div class="carte-entete"><h2 id="titreVentesUnivers">Ventes par univers</h2><span class="text-muted small fw-normal">30 derniers jours</span></div>
                <div class="carte-corps">
                    <?php if (!$ventesParUnivers): ?>
                        <div class="etat-vide"><div class="icone-vide"><i class="bi bi-bar-chart"></i></div><strong>Pas encore de ventes</strong>Les ventes des 30 derniers jours apparaîtront ici.</div>
                    <?php else: ?>
                        <ul class="repartition">
                            <?php foreach ($ventesParUnivers as $ligneUnivers): ?>
                                <li class="repartition-ligne" title="<?= e($ligneUnivers['univers']) ?> : <?= (int) $ligneUnivers['livres'] ?> livre(s) vendu(s)">
                                    <span class="repartition-nom"><span><?= e($ligneUnivers['univers']) ?></span><small><?= (int) $ligneUnivers['livres'] ?> livre<?= $ligneUnivers['livres'] > 1 ? 's' : '' ?></small></span>
                                    <span class="repartition-valeur"><?= formaterPrix((float) $ligneUnivers['montant']) ?></span>
                                    <span class="repartition-piste"><span class="repartition-barre" style="width: <?= round($ligneUnivers['montant'] / $totalUnivers * 100, 1) ?>%"></span></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="small text-muted mt-3 mb-0">Montant des livres vendus, hors remises et livraison.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-6">
        <section class="carte h-100" aria-labelledby="titreATraiter">
            <div class="carte-entete">
                <h2 id="titreATraiter">À traiter</h2>
                <span class="etiquette <?= $totalATraiter > 0 ? 'etiquette-alerte' : '' ?>"><?= $totalATraiter > 0 ? $totalATraiter . ' en attente' : 'Tout est à jour' ?></span>
            </div>
            <ul class="liste-a-traiter">
                <?php foreach ($aTraiter as [$icone, $libelle, $detail, $nombre, $lien]): ?>
                    <li>
                        <a href="<?= e($lien) ?>" class="ligne-a-traiter">
                            <span class="compte <?= $nombre > 0 ? '' : 'zero' ?>"><?= $nombre > 0 ? (int) $nombre : '<i class="bi bi-check-lg" aria-label="Aucun"></i>' ?></span>
                            <span><strong><?= e($libelle) ?></strong><small><?= e($detail) ?></small></span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="carte h-100" aria-labelledby="titreActivite">
            <div class="carte-entete">
                <h2 id="titreActivite">Activité récente</h2>
                <?php if (aLeDroit('admin')): ?><a href="logs.php" class="lien-carte">Journal complet</a><?php endif; ?>
            </div>
            <?php if (!$activite): ?>
                <div class="etat-vide"><div class="icone-vide"><i class="bi bi-activity"></i></div><strong>Rien de neuf pour l'instant</strong>Les commandes, inscriptions et avis apparaîtront ici.</div>
            <?php else: ?>
                <ul class="fil-activite">
                    <?php foreach ($activite as $evenementActivite): ?>
                        <li>
                            <span class="icone-activite"><i class="bi bi-<?= $iconesActivite[$evenementActivite['type']] ?? 'dot' ?>" aria-hidden="true"></i></span>
                            <span>
                                <strong><?= e($evenementActivite['qui']) ?></strong> · <?= e($evenementActivite['quoi']) ?>
                                <?php if ($finances && $evenementActivite['montant'] !== null): ?> · <strong><?= formaterPrix((float) $evenementActivite['montant']) ?></strong><?php endif; ?>
                                <time datetime="<?= e(date('c', strtotime($evenementActivite['date_evt']))) ?>"><?= depuis($evenementActivite['date_evt']) ?></time>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
