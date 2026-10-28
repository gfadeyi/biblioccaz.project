<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../points_lib.php';
exigerDroit('equipe');
$niveauxAdmin = niveauxRegles($pdo);

const MEMBRES_PAR_PAGE = 30;
const TRIS_MEMBRES_POINTS = [
    'cumul' => ['Total gagné', 'cumul DESC, u.pseudo'],
    'solde' => ['Solde', 'u.solde_points DESC, u.pseudo'],
    'attente' => ['En attente', 'attente DESC, u.pseudo'],
    'activite' => ['Dernière activité', 'dernier IS NULL, dernier DESC'],
    'pseudo' => ['Membre', 'u.pseudo'],
];

$recherche = trim($_GET['q'] ?? '');
$niveauFiltre = isset($_GET['niveau']) && $_GET['niveau'] !== '' && isset($niveauxAdmin[(int) $_GET['niveau']]) ? (int) $_GET['niveau'] : null;
$tri = array_key_exists($_GET['tri'] ?? '', TRIS_MEMBRES_POINTS) ? $_GET['tri'] : 'cumul';
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ["u.role = 'client'"];
$having = [];
$params = [];
if ($recherche !== '') {
    $where[] = "(u.pseudo LIKE ? OR u.email LIKE ?)";
    array_push($params, "%$recherche%", "%$recherche%");
}
$paramsHaving = [];
if ($niveauFiltre !== null) {
    $having[] = "cumul >= ?";
    $paramsHaving[] = $niveauxAdmin[$niveauFiltre]['seuil'];
    if (isset($niveauxAdmin[$niveauFiltre + 1])) {
        $having[] = "cumul < ?";
        $paramsHaving[] = $niveauxAdmin[$niveauFiltre + 1]['seuil'];
    }
}

$sqlMembres = "
    SELECT u.id, u.pseudo, u.email, u.solde_points, u.date_inscription,
           GREATEST(0, COALESCE(SUM(CASE WHEN pm.statut = 'valide' AND pm.type NOT IN ('depense', 'remboursement') THEN pm.points END), 0)) AS cumul,
           COALESCE(SUM(CASE WHEN pm.statut = 'en_attente' THEN pm.points END), 0) AS attente,
           MAX(pm.date_mouvement) AS dernier
    FROM user u
    LEFT JOIN point_mouvement pm ON pm.id_user = u.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY u.id, u.pseudo, u.email, u.solde_points, u.date_inscription
    " . ($having ? "HAVING " . implode(' AND ', $having) : "");

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ($sqlMembres) AS membres");
    $stmt->execute(array_merge($params, $paramsHaving));
    $total = (int) $stmt->fetchColumn();
    $nbPages = max(1, (int) ceil($total / MEMBRES_PAR_PAGE));
    $page = min($page, $nbPages);

    $stmt = $pdo->prepare($sqlMembres . " ORDER BY " . TRIS_MEMBRES_POINTS[$tri][1] . " LIMIT " . MEMBRES_PAR_PAGE . " OFFSET " . (($page - 1) * MEMBRES_PAR_PAGE));
    $stmt->execute(array_merge($params, $paramsHaving));
    $membres = $stmt->fetchAll();

    $badgesParMembre = [];
    if ($membres) {
        $ids = array_column($membres, 'id');
        $stmt = $pdo->prepare("SELECT id_user, code, MAX(palier) AS palier FROM badge_obtenu WHERE id_user IN (" . implode(',', array_fill(0, count($ids), '?')) . ") GROUP BY id_user, code");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $ligne) {
            $badgesParMembre[$ligne['id_user']][$ligne['code']] = (int) $ligne['palier'];
        }
    }

    $repartition = array_fill(0, count($niveauxAdmin), 0);
    $cumuls = $pdo->query("
        SELECT GREATEST(0, COALESCE(SUM(CASE WHEN pm.statut = 'valide' AND pm.type NOT IN ('depense', 'remboursement') THEN pm.points END), 0)) AS cumul
        FROM user u LEFT JOIN point_mouvement pm ON pm.id_user = u.id
        WHERE u.role = 'client' GROUP BY u.id")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cumuls as $cumul) {
        $repartition[niveauPour((int) $cumul, $niveauxAdmin)['index']]++;
    }
    $totaux = $pdo->query("
        SELECT COALESCE(SUM(CASE WHEN statut = 'valide' AND points > 0 AND type NOT IN ('remboursement') THEN points END), 0) AS distribues,
               COALESCE(-SUM(CASE WHEN statut = 'valide' AND type = 'depense' THEN points END), 0) AS depenses,
               COALESCE(SUM(CASE WHEN statut = 'en_attente' THEN points END), 0) AS attente
        FROM point_mouvement")->fetch();
    $badgesDistribues = (int) $pdo->query("SELECT COUNT(*) FROM badge_obtenu")->fetchColumn();
} catch (PDOException $e) {
    $membres = [];
    $total = 0;
    $nbPages = 1;
    $badgesParMembre = [];
    $repartition = array_fill(0, count($niveauxAdmin), 0);
    $totaux = ['distribues' => 0, 'depenses' => 0, 'attente' => 0];
    $badgesDistribues = 0;
    $erreurMigration = true;
}

function lienMembresPoints(array $changements): string
{
    $parametres = array_merge(['q' => $_GET['q'] ?? '', 'niveau' => $_GET['niveau'] ?? '', 'tri' => $_GET['tri'] ?? ''], $changements);
    $parametres = array_filter($parametres, fn($valeur) => $valeur !== '' && $valeur !== null);
    return 'membres_points.php' . ($parametres ? '?' . http_build_query($parametres) : '');
}

function iconeBadgeAdmin(string $nom): string
{
    return '<svg class="icone-dessin" aria-hidden="true" focusable="false"><use href="../img/icones.svg?v=' . @filemtime(__DIR__ . '/../img/icones.svg') . '#d-' . e($nom) . '"></use></svg>';
}

$titrePage = 'Niveaux & badges';
$sousTitrePage = 'La progression des lecteurs : niveaux calculés sur le total de points gagnés, badges débloqués et points en attente.';
$pageActive = 'membres_points';
$filAriane = [[null, 'Communauté']];
include __DIR__ . '/_haut.php';
$maxRepartition = max(1, max($repartition));
?>

<?php if (!empty($erreurMigration)): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>La migration <code>sql/maj_points_badges.sql</code> n'est pas encore importée.</span></div>
<?php endif; ?>

<div class="grille-indicateurs mb-4">
    <div class="stat"><div class="stat-haut"><span>Points distribués</span><span class="stat-icone"><i class="bi bi-coin" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= number_format((int) $totaux['distribues'], 0, ',', ' ') ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Points utilisés en bons</span><span class="stat-icone"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= number_format((int) $totaux['depenses'], 0, ',', ' ') ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Points en attente</span><span class="stat-icone"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= number_format((int) $totaux['attente'], 0, ',', ' ') ?></div></div>
    <div class="stat"><div class="stat-haut"><span>Badges débloqués</span><span class="stat-icone"><i class="bi bi-award" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $badgesDistribues ?></div></div>
</div>

<section class="carte mb-4">
    <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Lecteurs par niveau</h2></div>
    <div class="carte-corps">
        <ul class="repartition-niveaux">
            <?php foreach ($niveauxAdmin as $index => $niveauLigne): ?>
                <li>
                    <a href="<?= e(lienMembresPoints(['niveau' => $niveauFiltre === $index ? '' : $index, 'page' => ''])) ?>" class="<?= $niveauFiltre === $index ? 'actif' : '' ?>">
                        <span class="repartition-niveaux__nom"><?= e($niveauLigne['nom']) ?> <small>dès <?= number_format($niveauLigne['seuil'], 0, ',', ' ') ?> pts</small></span>
                        <span class="repartition-niveaux__barre"><span style="width: <?= round($repartition[$index] / $maxRepartition * 100, 1) ?>%"></span></span>
                        <strong><?= $repartition[$index] ?></strong>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<form method="GET" class="barre-filtres-admin mb-3">
    <div class="champ-recherche">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Pseudo ou email…" value="<?= e($recherche) ?>" aria-label="Rechercher un membre">
    </div>
    <select name="niveau" class="form-select form-select-sm" aria-label="Niveau" onchange="this.form.submit()">
        <option value="">Tous les niveaux</option>
        <?php foreach ($niveauxAdmin as $index => $niveauLigne): ?>
            <option value="<?= $index ?>" <?= $niveauFiltre === $index ? 'selected' : '' ?>><?= e($niveauLigne['nom']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="tri" value="<?= e($tri) ?>">
    <button class="btn btn-vert btn-sm">Filtrer</button>
    <?php if ($recherche !== '' || $niveauFiltre !== null): ?><a href="membres_points.php" class="btn btn-link btn-sm">Réinitialiser</a><?php endif; ?>
</form>

<?php if (!$membres): ?>
    <div class="carte etat-vide"><i class="bi bi-people" aria-hidden="true"></i><p>Aucun lecteur ne correspond à ces critères.</p></div>
<?php else: ?>
    <div class="carte">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cartes table-cartes-large">
                <thead>
                    <tr>
                        <?php foreach (['pseudo' => 'Membre', 'niveau' => 'Niveau', 'cumul' => 'Total gagné', 'solde' => 'Solde', 'attente' => 'En attente', 'badges' => 'Badges', 'activite' => 'Dernière activité'] as $cle => $libelle): ?>
                            <th scope="col" class="<?= in_array($cle, ['cumul', 'solde', 'attente'], true) ? 'text-end' : '' ?>">
                                <?php if (isset(TRIS_MEMBRES_POINTS[$cle])): ?>
                                    <a href="<?= e(lienMembresPoints(['tri' => $cle, 'page' => ''])) ?>" class="th-tri <?= $tri === $cle ? 'actif' : '' ?>"><?= $libelle ?><?= $tri === $cle ? ' <i class="bi bi-arrow-down-short" aria-hidden="true"></i>' : '' ?></a>
                                <?php else: ?>
                                    <?= $libelle ?>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($membres as $membre): ?>
                        <?php $niveauMembre = niveauPour((int) $membre['cumul'], $niveauxAdmin); ?>
                        <tr>
                            <td data-libelle="Membre"><strong>@<?= e($membre['pseudo']) ?></strong><br><small class="text-muted"><?= e($membre['email']) ?></small></td>
                            <td data-libelle="Niveau">
                                <span class="etiquette"><?= e($niveauMembre['nom']) ?></span>
                                <?php if ($niveauMembre['prochain']): ?>
                                    <span class="mini-jauge" title="Encore <?= $niveauMembre['reste'] ?> pts pour <?= e($niveauMembre['prochain']['nom']) ?>"><span style="width: <?= round($niveauMembre['progression'] * 100) ?>%"></span></span>
                                <?php endif; ?>
                            </td>
                            <td data-libelle="Total gagné" class="text-end fw-bold"><?= number_format((int) $membre['cumul'], 0, ',', ' ') ?></td>
                            <td data-libelle="Solde" class="text-end"><?= number_format((int) $membre['solde_points'], 0, ',', ' ') ?></td>
                            <td data-libelle="En attente" class="text-end"><?= (int) $membre['attente'] > 0 ? '<span class="etiquette etiquette-neutre">' . (int) $membre['attente'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                            <td data-libelle="Badges">
                                <?php $obtenus = $badgesParMembre[$membre['id']] ?? []; ?>
                                <?php if (!$obtenus): ?><span class="text-muted">—</span><?php endif; ?>
                                <span class="badges-mini">
                                    <?php foreach ($obtenus as $code => $palier): ?>
                                        <?php $definition = BADGES[$code] ?? null; if (!$definition) continue; ?>
                                        <span class="badge-mini palier-<?= isset($definition['rare']) ? 'rare' : $palier ?>" title="<?= e($definition['nom'] . ' · ' . (isset($definition['rare']) ? 'rare' : PALIERS_BADGES[$palier])) ?>"><?= iconeBadgeAdmin($definition['icone']) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            </td>
                            <td data-libelle="Dernière activité" class="text-muted small"><?= $membre['dernier'] ? date('d/m/Y', strtotime($membre['dernier'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($nbPages > 1): ?>
        <?php $classePagination = 'mt-3'; $lienPage = fn(int $numero) => lienMembresPoints(['page' => $numero > 1 ? $numero : '']); include __DIR__ . '/../partie_pagination.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
