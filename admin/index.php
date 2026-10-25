<?php
require_once __DIR__ . '/_init.php';
exigerDroit('equipe');

function compter(PDO $pdo, string $sql): float
{
    try {
        return (float) $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$stats = [
    'titres'     => compter($pdo, "SELECT COUNT(*) FROM livre WHERE is_valide = 1"),
    'stock'      => compter($pdo, "SELECT COALESCE(SUM(quantite), 0) FROM exemplaire WHERE is_disponible = 1 AND quantite > 0"),
    'membres'    => compter($pdo, "SELECT COUNT(*) FROM user"),
    'en_ligne'   => compter($pdo, "SELECT COUNT(*) FROM user WHERE last_activity > NOW() - INTERVAL 5 MINUTE"),
    'cmd_mois'   => compter($pdo, "SELECT COUNT(*) FROM commande WHERE date_commande >= DATE_FORMAT(NOW(), '%Y-%m-01')"),
    'ca_mois'    => compter($pdo, "SELECT COALESCE(SUM(total), 0) FROM commande WHERE date_commande >= DATE_FORMAT(NOW(), '%Y-%m-01')"),
    'dons'       => compter($pdo, "SELECT COUNT(*) FROM don WHERE statut IN ('disponible', 'reserve') AND date_limite >= CURDATE()"),
    'evenements' => compter($pdo, "SELECT COUNT(*) FROM evenement WHERE statut = 'publie' AND date_fin >= NOW()"),
    'rupture'    => compter($pdo, "SELECT COUNT(*) FROM livre l WHERE l.is_valide = 1 AND NOT EXISTS
                                   (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.quantite > 0)"),
];
$compteurs = compteursEquipe($pdo);

$dernieresCommandes = [];
if (aLeDroit('admin')) {
    try {
        $dernieresCommandes = $pdo->query("
            SELECT c.id_commande, c.date_commande, c.total, u.pseudo,
                   (SELECT SUM(quantite) FROM commande_ligne cl WHERE cl.id_commande = c.id_commande) AS nb_livres
            FROM commande c INNER JOIN user u ON u.id = c.id_user
            ORDER BY c.date_commande DESC LIMIT 6")->fetchAll();
    } catch (PDOException $e) {
    }
}

$titrePage = 'Tableau de bord';
$pageActive = 'index';
include __DIR__ . '/_haut.php';

$cartes = [
    [$compteurs['livres'], 'Fiches à valider', 'shield-exclamation', 'moderation_livres.php', 'moderation', $compteurs['livres'] > 0],
    [$compteurs['avis'], 'Avis signalés', 'flag', 'moderation_avis.php', 'moderation', $compteurs['avis'] > 0],
    [$compteurs['candidatures'], 'Candidatures modérateur', 'person-plus', 'utilisateurs.php', 'admin', $compteurs['candidatures'] > 0],
    [$stats['rupture'], 'Titres en rupture', 'exclamation-triangle', 'inventaire.php?stock=rupture', 'catalogue', $stats['rupture'] > 0],
    [$stats['titres'], 'Titres au catalogue', 'book', 'inventaire.php', 'catalogue', false],
    [$stats['stock'], 'Exemplaires en vente', 'box-seam', 'inventaire.php', 'catalogue', false],
    [$stats['membres'], 'Membres inscrits', 'people', 'utilisateurs.php', 'admin', false],
    [$stats['en_ligne'], 'En ligne maintenant', 'broadcast', 'en_ligne.php', 'admin', false],
    [$stats['cmd_mois'], 'Commandes ce mois-ci', 'bag-check', null, 'admin', false],
    [number_format($stats['ca_mois'], 2, ',', ' ') . ' €', "Chiffre d'affaires du mois", 'currency-euro', null, 'admin', false],
    [$stats['dons'], 'Dons en cours', 'gift', 'dons.php', 'equipe', false],
    [$stats['evenements'], 'Événements à venir', 'calendar-event', 'evenements.php', 'evenements', false],
];
?>

<p class="text-muted">Bonjour <?= e($_SESSION['prenom'] ?: $_SESSION['pseudo']) ?>, voici l'état de la boutique.</p>

<div class="row g-3 mb-4">
    <?php foreach ($cartes as [$valeur, $libelle, $icone, $lien, $droit, $alerte]): ?>
        <?php if (!aLeDroit($droit)) continue; ?>
        <div class="col-sm-6 col-xl-3">
            <<?= $lien ? 'a href="' . $lien . '"' : 'div' ?> class="stat">
                <span class="stat-icone <?= $alerte ? 'alerte' : '' ?>"><i class="bi bi-<?= $icone ?>"></i></span>
                <span>
                    <span class="stat-valeur d-block"><?= is_float($valeur) ? (int) $valeur : $valeur ?></span>
                    <span class="stat-libelle"><?= $libelle ?></span>
                </span>
            </<?= $lien ? 'a' : 'div' ?>>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <?php if (aLeDroit('admin')): ?>
        <div class="col-xl-7">
            <div class="carte h-100">
                <div class="carte-entete">Dernières commandes</div>
                <?php if (!$dernieresCommandes): ?>
                    <p class="text-muted small p-3 mb-0">Aucune commande pour l'instant.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle small">
                            <thead class="table-light"><tr><th>N°</th><th>Client</th><th>Date</th><th class="text-center">Livres</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($dernieresCommandes as $c): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $c['id_commande'] ?></td>
                                        <td><?= e($c['pseudo']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($c['date_commande'])) ?></td>
                                        <td class="text-center"><?= (int) $c['nb_livres'] ?></td>
                                        <td class="text-end fw-bold"><?= number_format($c['total'], 2, ',', ' ') ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?= aLeDroit('admin') ? 'col-xl-5' : 'col-lg-8' ?>">
        <div class="carte h-100">
            <div class="carte-entete">Raccourcis</div>
            <div class="list-group list-group-flush">
                <?php if (aLeDroit('catalogue')): ?>
                    <a href="livre.php" class="list-group-item list-group-item-action"><i class="bi bi-plus-circle me-2 text-success"></i>Ajouter un livre au catalogue</a>
                <?php endif; ?>
                <a href="moderation_livres.php" class="list-group-item list-group-item-action"><i class="bi bi-shield-check me-2 text-success"></i>Valider les fiches proposées par les auteurs</a>
                <a href="moderation_avis.php" class="list-group-item list-group-item-action"><i class="bi bi-chat-square-text me-2 text-success"></i>Traiter les avis signalés</a>
                <?php if (aLeDroit('evenements')): ?>
                    <a href="evenement.php" class="list-group-item list-group-item-action"><i class="bi bi-calendar-plus me-2 text-success"></i>Créer un événement</a>
                <?php endif; ?>
                <?php if (aLeDroit('admin')): ?>
                    <a href="export_membres.php" class="list-group-item list-group-item-action"><i class="bi bi-file-earmark-pdf me-2 text-success"></i>Exporter la liste des membres (PDF)</a>
                    <a href="newsletter.php" class="list-group-item list-group-item-action"><i class="bi bi-megaphone me-2 text-success"></i>Envoyer la newsletter</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
