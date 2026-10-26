<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../commandes_lib.php';
exigerDroit('catalogue');

const COMMANDES_PAR_PAGE = 30;
const PERIODES_COMMANDES = ['7' => '7 derniers jours', '30' => '30 derniers jours', '90' => '3 derniers mois', '365' => '12 derniers mois'];
const TRIS_COMMANDES = [
    'date' => 'c.date_commande DESC, c.id_commande DESC',
    'total' => 'c.total DESC, c.id_commande DESC',
];

$recherche = trim($_GET['q'] ?? '');
$statutFiltre = array_key_exists($_GET['statut'] ?? '', STATUTS_COMMANDE) ? $_GET['statut'] : '';
$periode = array_key_exists($_GET['periode'] ?? '', PERIODES_COMMANDES) ? $_GET['periode'] : '';
$tri = array_key_exists($_GET['tri'] ?? '', TRIS_COMMANDES) ? $_GET['tri'] : 'date';
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = [];
$params = [];
$numeroCherche = ltrim($recherche, "#nN°o \t");
if ($recherche !== '' && ctype_digit($numeroCherche)) {
    $where[] = "c.id_commande = ?";
    $params[] = (int) $numeroCherche;
} elseif ($recherche !== '') {
    $where[] = "(u.pseudo LIKE ? OR u.email LIKE ? OR CONCAT(c.prenom, ' ', c.nom) LIKE ?)";
    array_push($params, "%$recherche%", "%$recherche%", "%$recherche%");
}
if ($statutFiltre !== '') {
    $where[] = "c.statut = ?";
    $params[] = $statutFiltre;
}
if ($periode !== '') {
    $where[] = "c.date_commande >= CURDATE() - INTERVAL ? DAY";
    $params[] = (int) $periode;
}
$conditions = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM commande c INNER JOIN `user` u ON u.id = c.id_user $conditions");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$nbPages = max(1, (int) ceil($total / COMMANDES_PAR_PAGE));
$page = min($page, $nbPages);

$stmt = $pdo->prepare("
    SELECT c.*, u.pseudo, u.email,
           (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_commande = c.id_commande) AS nb_livres
    FROM commande c
    INNER JOIN `user` u ON u.id = c.id_user
    $conditions
    ORDER BY " . TRIS_COMMANDES[$tri] . "
    LIMIT " . COMMANDES_PAR_PAGE . " OFFSET " . (($page - 1) * COMMANDES_PAR_PAGE));
$stmt->execute($params);
$commandes = $stmt->fetchAll();

$indicateurs = $pdo->query("
    SELECT COALESCE(SUM(CASE WHEN statut <> 'annulee' THEN total END), 0) AS chiffre,
           COUNT(CASE WHEN statut <> 'annulee' THEN 1 END) AS nombre,
           COUNT(CASE WHEN statut = 'annulee' THEN 1 END) AS annulees
    FROM commande
    WHERE date_commande >= CURDATE() - INTERVAL 30 DAY")->fetch();
$panierMoyen = $indicateurs['nombre'] > 0 ? $indicateurs['chiffre'] / $indicateurs['nombre'] : 0;

function lienCommandes(array $changements): string
{
    $parametres = array_merge(['q' => $_GET['q'] ?? '', 'statut' => $_GET['statut'] ?? '', 'periode' => $_GET['periode'] ?? '', 'tri' => $_GET['tri'] ?? ''], $changements);
    $parametres = array_filter($parametres, fn($valeur) => $valeur !== '' && $valeur !== null);
    return 'commandes.php' . ($parametres ? '?' . http_build_query($parametres) : '');
}

$titrePage = 'Commandes';
$sousTitrePage = 'Toutes les commandes du site : détail, suivi des points et annulation.';
$pageActive = 'commandes';
include __DIR__ . '/_haut.php';
$filtresActifs = $recherche !== '' || $statutFiltre !== '' || $periode !== '';
?>

<div class="grille-indicateurs mb-4">
    <div class="stat"><div class="stat-haut"><span>Chiffre d'affaires (30 j)</span><span class="stat-icone"><i class="bi bi-cash-stack" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= formaterPrix((float) $indicateurs['chiffre']) ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Commandes (30 j)</span><span class="stat-icone"><i class="bi bi-bag-check" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= (int) $indicateurs['nombre'] ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Panier moyen</span><span class="stat-icone"><i class="bi bi-basket" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= formaterPrix($panierMoyen) ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Annulées (30 j)</span><span class="stat-icone"><i class="bi bi-x-circle" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= (int) $indicateurs['annulees'] ?></div></div>
</div>

<form method="GET" class="barre-filtres-admin mb-3">
    <div class="champ-recherche">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="N° de commande, client ou email…" value="<?= e($recherche) ?>" aria-label="Rechercher une commande">
    </div>
    <select name="statut" class="form-select form-select-sm" aria-label="Statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <?php foreach (STATUTS_COMMANDE as $cle => $libelle): ?>
            <option value="<?= $cle ?>" <?= $statutFiltre === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
        <?php endforeach; ?>
    </select>
    <select name="periode" class="form-select form-select-sm" aria-label="Période" onchange="this.form.submit()">
        <option value="">Depuis le début</option>
        <?php foreach (PERIODES_COMMANDES as $cle => $libelle): ?>
            <option value="<?= $cle ?>" <?= $periode === (string) $cle ? 'selected' : '' ?>><?= $libelle ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="tri" value="<?= e($tri) ?>">
    <button class="btn btn-vert btn-sm">Filtrer</button>
    <?php if ($filtresActifs): ?><a href="commandes.php" class="btn btn-link btn-sm">Réinitialiser</a><?php endif; ?>
</form>

<?php if (!$commandes): ?>
    <div class="carte etat-vide">
        <div class="icone-vide"><i class="bi bi-bag" aria-hidden="true"></i></div>
        <strong><?= $filtresActifs ? 'Aucune commande ne correspond' : 'Aucune commande pour le moment' ?></strong>
        <?= $filtresActifs ? 'Essayez un autre numéro, un autre client ou une autre période.' : 'Les commandes des clients apparaîtront ici.' ?>
    </div>
<?php else: ?>
    <p class="small text-muted mb-2"><?= $total ?> commande<?= $total > 1 ? 's' : '' ?></p>
    <div class="carte table-responsive">
        <table class="table table-hover align-middle mb-0 table-cartes">
            <thead>
                <tr>
                    <th scope="col"><a href="<?= e(lienCommandes(['tri' => '', 'page' => ''])) ?>" class="th-tri <?= $tri === 'date' ? 'actif' : '' ?>">Commande<?= $tri === 'date' ? ' <i class="bi bi-arrow-down-short" aria-hidden="true"></i>' : '' ?></a></th>
                    <th scope="col">Client</th>
                    <th scope="col" class="text-center">Livres</th>
                    <th scope="col" class="text-end"><a href="<?= e(lienCommandes(['tri' => 'total', 'page' => ''])) ?>" class="th-tri <?= $tri === 'total' ? 'actif' : '' ?>">Total<?= $tri === 'total' ? ' <i class="bi bi-arrow-down-short" aria-hidden="true"></i>' : '' ?></a></th>
                    <th scope="col" class="text-end">Points</th>
                    <th scope="col">Statut</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Action</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <?php $annulee = $commande['statut'] === 'annulee'; ?>
                    <tr class="<?= $annulee ? 'ligne-annulee' : '' ?>">
                        <td data-libelle="Commande">
                            <a href="commande.php?id=<?= (int) $commande['id_commande'] ?>" class="fw-bold">N° <?= (int) $commande['id_commande'] ?></a>
                            <br><small class="text-muted"><?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></small>
                        </td>
                        <td data-libelle="Client"><?= e($commande['prenom'] . ' ' . $commande['nom']) ?><br><small class="text-muted">@<?= e($commande['pseudo']) ?></small></td>
                        <td data-libelle="Livres" class="text-center"><?= (int) $commande['nb_livres'] ?></td>
                        <td data-libelle="Total" class="text-end">
                            <strong class="<?= $annulee ? 'text-decoration-line-through text-muted' : '' ?>"><?= formaterPrix((float) $commande['total']) ?></strong>
                            <?php if ((float) $commande['reduction_points'] > 0): ?><br><small class="text-muted">bon −<?= formaterPrix((float) $commande['reduction_points']) ?></small><?php endif; ?>
                        </td>
                        <td data-libelle="Points" class="text-end small">
                            <?php if ((int) $commande['points_utilises'] > 0): ?><span class="text-muted">−<?= (int) $commande['points_utilises'] ?></span> · <?php endif; ?>
                            <span class="<?= $annulee ? 'text-muted text-decoration-line-through' : 'text-success fw-bold' ?>">+<?= (int) $commande['points_gagnes'] ?></span>
                        </td>
                        <td data-libelle="Statut">
                            <?php if ($annulee): ?>
                                <span class="etiquette etiquette-alerte" title="Le <?= date('d/m/Y', strtotime($commande['date_annulation'])) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i> Annulée</span>
                            <?php else: ?>
                                <span class="etiquette"><i class="bi bi-check2" aria-hidden="true"></i> Validée</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><a href="commande.php?id=<?= (int) $commande['id_commande'] ?>" class="btn btn-outline-success btn-sm">Détail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($nbPages > 1): ?>
        <?php $classePagination = 'mt-3'; $lienPage = fn(int $numero) => lienCommandes(['page' => $numero > 1 ? $numero : '']); include __DIR__ . '/../partie_pagination.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
