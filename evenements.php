<?php
require_once 'config.php';
require_once 'evenements_lib.php';

$periode = ($_GET['periode'] ?? '') === 'passes' ? 'passes' : 'a_venir';
$type = isset(EVT_TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$lieu = trim($_GET['lieu'] ?? '');
$auteur = trim($_GET['auteur'] ?? '');
$mois = preg_match('/^\d{4}-\d{2}$/', $_GET['mois'] ?? '') ? $_GET['mois'] : '';
$tarif = isset(EVT_FILTRES_TARIF[$_GET['tarif'] ?? '']) ? $_GET['tarif'] : '';

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
if ($tarif !== '') {
    $where[] = conditionTarifEvenement($pdo, $tarif);
}
if ($mois !== '') {
    $where[] = "e.date_debut >= ? AND e.date_debut < ?";
    array_push($params, "$mois-01 00:00:00", date('Y-m-01 00:00:00', strtotime("$mois-01 +1 month")));
}

$stmt = $pdo->prepare("
    SELECT e.*,
           (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit') AS nb_inscrits,
           IF(e.date_validation IS NULL, NULL, COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.prenom, u.nom)), ''), u.pseudo)) AS propose_par
    FROM evenement e
    LEFT JOIN user u ON u.id = e.id_createur
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.date_debut " . ($periode === 'passes' ? 'DESC' : 'ASC') . "
    LIMIT 100");
$stmt->execute($params);
$evenements = $stmt->fetchAll();

$evenementsPasses = [];
$nbEvenementsPasses = 0;
if ($periode === 'a_venir') {
    $wherePasses = $where;
    $paramsPasses = $params;
    $wherePasses[1] = "e.date_fin < NOW()";
    if ($mois !== '') {
        array_pop($wherePasses);
        array_splice($paramsPasses, -2);
    }
    $stmt = $pdo->prepare("
        SELECT e.*, COUNT(*) OVER () AS nb_total
        FROM evenement e
        WHERE " . implode(' AND ', $wherePasses) . "
        ORDER BY e.date_debut DESC
        LIMIT 6");
    $stmt->execute($paramsPasses);
    $evenementsPasses = $stmt->fetchAll();
    $nbEvenementsPasses = (int) ($evenementsPasses[0]['nb_total'] ?? 0);
}

$lieux = $pdo->query("SELECT DISTINCT lieu FROM evenement WHERE statut = 'publie' ORDER BY lieu")->fetchAll(PDO::FETCH_COLUMN);
$moisDispos = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(date_debut, '%Y-%m') FROM evenement
    WHERE statut = 'publie' AND date_fin >= NOW() ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);

$parMois = [];
foreach ($evenements as $e) {
    $t = strtotime($e['date_debut']);
    $parMois[ucfirst(EVT_MOIS[(int) date('n', $t) - 1]) . ' ' . date('Y', $t)][] = $e;
}
$filtresActifs = $type !== '' || $lieu !== '' || $auteur !== '' || $mois !== '' || $tarif !== '';

include 'header.php';
?>

<div class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><i class="bi bi-calendar-event"></i> Événements</h1>
            <p class="text-muted mb-0">Dédicaces, rencontres et ateliers autour du livre d'occasion. Événements gratuits ou payants, sur inscription.</p>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="mes_evenements" class="btn btn-outline-success rounded-pill fw-bold"><i class="bi bi-ticket-perforated"></i> Mes inscriptions</a>
        <?php endif; ?>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link <?= $periode === 'a_venir' ? 'active' : '' ?>" href="evenements">À venir</a></li>
        <li class="nav-item"><a class="nav-link <?= $periode === 'passes' ? 'active' : '' ?>" href="evenements?periode=passes">Passés</a></li>
    </ul>

    <form method="GET" class="row g-2 mb-4">
        <?php if ($periode === 'passes'): ?><input type="hidden" name="periode" value="passes"><?php endif; ?>
        <div class="col-md-2">
            <input type="text" name="auteur" class="form-control" placeholder="Auteur ou titre" value="<?= htmlspecialchars($auteur) ?>" aria-label="Auteur ou titre">
        </div>
        <div class="col-6 col-md-2">
            <select name="tarif" class="form-select" aria-label="Tarif" data-filtre-tarif>
                <option value="">Tous les tarifs</option>
                <?php foreach (EVT_FILTRES_TARIF as $cle => $libelle): ?>
                    <option value="<?= $cle ?>" <?= $tarif === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="type" class="form-select" aria-label="Type d'événement">
                <option value="">Tous les types</option>
                <?php foreach (EVT_TYPES as $cle => [$libelle]): ?>
                    <option value="<?= $cle ?>" <?= $type === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
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
                <a href="evenements<?= $periode === 'passes' ? '?periode=passes' : '' ?>" class="btn btn-link px-1" title="Effacer les filtres" aria-label="Effacer les filtres"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
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
                <a href="evenement?id=<?= $e['id_evenement'] ?>" class="card border-0 shadow-sm text-decoration-none text-dark carte-evenement" style="border-radius: 15px;">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="date-evenement text-center flex-shrink-0">
                            <div class="small text-uppercase"><?= mb_substr(EVT_JOURS[(int) date('w', $t)], 0, 3) ?></div>
                            <div class="fs-2 fw-bold lh-1"><?= date('j', $t) ?></div>
                            <div class="small"><?= EVT_MOIS_COURTS[(int) date('n', $t) - 1] ?></div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap gap-2 mb-1"><?= badgeTypeEvenement($e['type']) ?> <?= badgeEvenement($e) ?> <?= badgePrixEvenement($e) ?></div>
                            <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($e['titre']) ?></h3>
                            <div class="small text-muted">
                                <i class="bi bi-clock"></i> <?= date('G\hi', $t) ?> – <?= date('G\hi', strtotime($e['date_fin'])) ?>
                                <span class="mx-1">·</span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['lieu']) ?>
                                <?php if ($e['auteurs']): ?><span class="mx-1">·</span><i class="bi bi-person"></i> <?= htmlspecialchars($e['auteurs']) ?><?php endif; ?>
                                <?php if ($e['propose_par']): ?><span class="mx-1">·</span><i class="bi bi-pen"></i> Proposé par <?= htmlspecialchars($e['propose_par']) ?><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($periode === 'a_venir'): ?>
                            <div class="text-end small flex-shrink-0 d-none d-sm-block">
                                <?php if ($restantes > 0): ?>
                                    <strong class="text-success"><?= $restantes ?></strong> place<?= $restantes > 1 ? 's' : '' ?><br><span class="text-muted">sur <?= $e['places_max'] ?></span>
                                <?php else: ?>
                                    <span class="fw-bold text-body-secondary">Complet</span><br><span class="text-muted">liste d'attente</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

<?php if ($evenementsPasses): ?>
    <details class="evenements-passes mt-5" data-evenements-passes>
        <summary>
            <span><i class="bi bi-clock-history" aria-hidden="true"></i> Événements passés</span>
            <span class="evenements-passes__nombre"><?= $nbEvenementsPasses ?></span>
        </summary>
        <ul class="list-unstyled mb-0">
            <?php foreach ($evenementsPasses as $passe): ?>
                <?php $debutPasse = strtotime($passe['date_debut']); ?>
                <li>
                    <a href="evenement?id=<?= (int) $passe['id_evenement'] ?>">
                        <span class="evenements-passes__date"><?= date('j', $debutPasse) ?> <?= EVT_MOIS_COURTS[(int) date('n', $debutPasse) - 1] ?> <?= date('Y', $debutPasse) ?></span>
                        <span class="evenements-passes__titre"><?= htmlspecialchars($passe['titre']) ?></span>
                        <span class="evenements-passes__lieu"><?= htmlspecialchars($passe['lieu']) ?> · <?= htmlspecialchars(libellePrixEvenement($passe)) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($nbEvenementsPasses > count($evenementsPasses)): ?>
            <a href="evenements?periode=passes" class="lien-voir-tout mt-2">Voir les <?= $nbEvenementsPasses ?> événements passés <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <?php endif; ?>
    </details>
<?php endif; ?>
</div>

<style>
    .date-evenement { width: 64px; padding: .5rem 0; border-radius: 12px; background: #d9ead3; color: #274e13; }
    .carte-evenement { transition: transform .15s; }
    .carte-evenement:hover { transform: translateY(-2px); }
    .min-w-0 { min-width: 0; }
    .evenements-passes { border-top: 1px solid var(--trait); padding-top: 1rem; }
    .evenements-passes summary { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-height: 44px; cursor: pointer; font-weight: 700; color: var(--encre-douce); list-style: none; }
    .evenements-passes summary::-webkit-details-marker { display: none; }
    .evenements-passes summary::after { content: "\F282"; font-family: "bootstrap-icons"; transition: transform var(--transition); }
    .evenements-passes[open] summary::after { transform: rotate(180deg); }
    .evenements-passes__nombre { margin-left: auto; padding: .1rem .6rem; border-radius: var(--rayon-pilule); background: var(--papier); border: 1px solid var(--trait); font-size: .8rem; }
    .evenements-passes ul { display: grid; gap: .25rem; margin-top: .5rem; }
    .evenements-passes li a { display: grid; grid-template-columns: 7rem minmax(0, 1fr); gap: .1rem 1rem; padding: .6rem .25rem; border-bottom: 1px dashed var(--trait); color: var(--encre-douce); text-decoration: none; }
    .evenements-passes li a:hover { color: var(--texte-vert); }
    .evenements-passes__date { grid-row: span 2; font-size: .85rem; }
    .evenements-passes__titre { font-weight: 600; overflow-wrap: anywhere; }
    .evenements-passes__lieu { font-size: .8rem; }
    @media (max-width: 575px) { .evenements-passes li a { grid-template-columns: minmax(0, 1fr); } .evenements-passes__date { grid-row: auto; } }
</style>

<?php include 'footer.php'; ?>
