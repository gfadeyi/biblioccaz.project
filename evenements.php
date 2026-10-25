<?php
require_once 'config.php';
require_once 'evenements_lib.php';

$periode = ($_GET['periode'] ?? '') === 'passes' ? 'passes' : 'a_venir';
$type = isset(EVT_TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$lieu = trim($_GET['lieu'] ?? '');
$auteur = trim($_GET['auteur'] ?? '');
$mois = preg_match('/^\d{4}-\d{2}$/', $_GET['mois'] ?? '') ? $_GET['mois'] : '';

$where = ["e.statut = 'publie'", $periode === 'passes' ? "e.date_fin < NOW()" : "e.date_fin >= NOW()"];
$params = [];
if ($type !== '') {
    $where[] = "e.type = ?";
    $params[] = $type;
}
if ($lieu !== '') {
    $where[] = "e.lieu = ?";
    $params[] = $lieu;
}
if ($auteur !== '') {
    $where[] = "(e.auteurs LIKE ? OR e.titre LIKE ?)";
    array_push($params, "%$auteur%", "%$auteur%");
}
if ($mois !== '') {
    $where[] = "e.date_debut >= ? AND e.date_debut < ?";
    array_push($params, "$mois-01 00:00:00", date('Y-m-01 00:00:00', strtotime("$mois-01 +1 month")));
}

$stmt = $pdo->prepare("
    SELECT e.*,
           (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit') AS nb_inscrits
    FROM evenement e
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.date_debut " . ($periode === 'passes' ? 'DESC' : 'ASC') . "
    LIMIT 100");
$stmt->execute($params);
$evenements = $stmt->fetchAll();

$lieux = $pdo->query("SELECT DISTINCT lieu FROM evenement WHERE statut = 'publie' ORDER BY lieu")->fetchAll(PDO::FETCH_COLUMN);
$moisDispos = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(date_debut, '%Y-%m') FROM evenement
    WHERE statut = 'publie' AND date_fin >= NOW() ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);

$parMois = [];
foreach ($evenements as $e) {
    $t = strtotime($e['date_debut']);
    $parMois[ucfirst(EVT_MOIS[(int) date('n', $t) - 1]) . ' ' . date('Y', $t)][] = $e;
}
$filtresActifs = $type !== '' || $lieu !== '' || $auteur !== '' || $mois !== '';

include 'header.php';
?>

<div class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><i class="bi bi-calendar-event"></i> Événements</h1>
            <p class="text-muted mb-0">Dédicaces, rencontres et ateliers autour du livre d'occasion.</p>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="mes_evenements.php" class="btn btn-outline-success rounded-pill fw-bold"><i class="bi bi-ticket-perforated"></i> Mes inscriptions</a>
        <?php endif; ?>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link <?= $periode === 'a_venir' ? 'active bg-success' : 'text-success' ?>" href="evenements.php">À venir</a></li>
        <li class="nav-item"><a class="nav-link <?= $periode === 'passes' ? 'active bg-success' : 'text-success' ?>" href="evenements.php?periode=passes">Passés</a></li>
    </ul>

    <form method="GET" class="row g-2 mb-4">
        <?php if ($periode === 'passes'): ?><input type="hidden" name="periode" value="passes"><?php endif; ?>
        <div class="col-md-3">
            <input type="text" name="auteur" class="form-control" placeholder="Auteur ou titre" value="<?= htmlspecialchars($auteur) ?>" aria-label="Auteur ou titre">
        </div>
        <div class="col-6 col-md-2">
            <select name="type" class="form-select" aria-label="Type d'événement">
                <option value="">Tous les types</option>
                <?php foreach (EVT_TYPES as $cle => [$libelle]): ?>
                    <option value="<?= $cle ?>" <?= $type === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="lieu" class="form-select" aria-label="Lieu">
                <option value="">Tous les lieux</option>
                <?php foreach ($lieux as $l): ?>
                    <option value="<?= htmlspecialchars($l) ?>" <?= $lieu === $l ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($periode === 'a_venir'): ?>
            <div class="col-6 col-md-2">
                <select name="mois" class="form-select" aria-label="Mois">
                    <option value="">Toutes les dates</option>
                    <?php foreach ($moisDispos as $m): ?>
                        <option value="<?= $m ?>" <?= $mois === $m ? 'selected' : '' ?>><?= ucfirst(EVT_MOIS[(int) substr($m, 5, 2) - 1]) . ' ' . substr($m, 0, 4) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-success flex-grow-1">Filtrer</button>
            <?php if ($filtresActifs): ?>
                <a href="evenements.php<?= $periode === 'passes' ? '?periode=passes' : '' ?>" class="btn btn-link px-1" title="Effacer les filtres"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$evenements): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <i class="bi bi-calendar-x text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary mb-0"><?= $filtresActifs ? 'Aucun événement ne correspond à ces critères.' : ($periode === 'passes' ? 'Aucun événement passé.' : 'Aucun événement prévu pour le moment, revenez bientôt !') ?></h5>
        </div>
    <?php endif; ?>

    <?php foreach ($parMois as $titreMois => $liste): ?>
        <h2 class="h5 fw-bold mt-4 mb-3 text-secondary"><?= $titreMois ?></h2>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($liste as $e): ?>
                <?php $t = strtotime($e['date_debut']); $restantes = placesRestantes($e); ?>
                <a href="evenement.php?id=<?= $e['id_evenement'] ?>" class="card border-0 shadow-sm text-decoration-none text-dark carte-evenement" style="border-radius: 15px;">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="date-evenement text-center flex-shrink-0">
                            <div class="small text-uppercase"><?= mb_substr(EVT_JOURS[(int) date('w', $t)], 0, 3) ?></div>
                            <div class="fs-2 fw-bold lh-1"><?= date('j', $t) ?></div>
                            <div class="small"><?= mb_substr(EVT_MOIS[(int) date('n', $t) - 1], 0, 4) ?></div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap gap-2 mb-1"><?= badgeTypeEvenement($e['type']) ?> <?= badgeEvenement($e) ?></div>
                            <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($e['titre']) ?></h3>
                            <div class="small text-muted">
                                <i class="bi bi-clock"></i> <?= date('G\hi', $t) ?> – <?= date('G\hi', strtotime($e['date_fin'])) ?>
                                <span class="mx-1">·</span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['lieu']) ?>
                                <?php if ($e['auteurs']): ?><span class="mx-1">·</span><i class="bi bi-person"></i> <?= htmlspecialchars($e['auteurs']) ?><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($periode === 'a_venir'): ?>
                            <div class="text-end small flex-shrink-0 d-none d-sm-block">
                                <?php if ($restantes > 0): ?>
                                    <strong class="text-success"><?= $restantes ?></strong> place<?= $restantes > 1 ? 's' : '' ?><br><span class="text-muted">sur <?= $e['places_max'] ?></span>
                                <?php else: ?>
                                    <span class="text-warning-emphasis fw-bold">Complet</span><br><span class="text-muted">liste d'attente</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .date-evenement { width: 64px; padding: .5rem 0; border-radius: 12px; background: #d9ead3; color: #274e13; }
    .carte-evenement { transition: transform .15s; }
    .carte-evenement:hover { transform: translateY(-2px); }
    .min-w-0 { min-width: 0; }
</style>

<?php include 'footer.php'; ?>
