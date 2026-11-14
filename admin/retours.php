<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../retours_lib.php';
exigerDroit('catalogue');

const RETOURS_PAR_PAGE = 30;
const FILTRES_RETOURS = ['a_traiter' => 'En cours', 'demande' => 'Demandés', 'accepte' => 'Acceptés', 'recu' => 'Reçus', 'rembourse' => 'Remboursés', 'refuse' => 'Refusés', 'annule' => 'Annulés', 'tous' => 'Tous'];

$disponible = retoursDisponibles($pdo);
$filtre = array_key_exists($_GET['statut'] ?? '', FILTRES_RETOURS) ? $_GET['statut'] : 'a_traiter';
$recherche = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$type = in_array($_GET['type'] ?? '', ['retour', 'remboursement'], true) ? $_GET['type'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$retours = [];
$total = 0;
$compteursRetours = array_fill_keys(array_keys(RETOUR_STATUTS), 0);
$nbPages = 1;

if ($disponible) {
    $where = [];
    $params = [];
    if ($filtre === 'a_traiter') {
        $where[] = "r.statut IN ('demande', 'accepte', 'recu')";
    } elseif ($filtre !== 'tous') {
        $where[] = "r.statut = ?";
        $params[] = $filtre;
    }
    if ($type !== '') {
        $where[] = "r.type = ?";
        $params[] = $type;
    }
    if ($recherche !== '') {
        $numero = ltrim($recherche, "#nN°o \t");
        if (ctype_digit($numero)) {
            $where[] = "(r.id_commande = ? OR r.id_retour = ?)";
            array_push($params, (int) $numero, (int) $numero);
        } else {
            $motif = '%' . addcslashes($recherche, '%_\\') . '%';
            $where[] = "(r.reference LIKE ? OR u.pseudo LIKE ? OR u.email LIKE ?)";
            array_push($params, $motif, $motif, $motif);
        }
    }
    $conditions = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM retour_commande r INNER JOIN `user` u ON u.id = r.id_user $conditions");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    $nbPages = max(1, (int) ceil($total / RETOURS_PAR_PAGE));
    $page = min($page, $nbPages);
    $stmt = $pdo->prepare("SELECT r.*, u.pseudo, (SELECT COALESCE(SUM(rl.quantite), 0) FROM retour_ligne rl WHERE rl.id_retour = r.id_retour) AS nb_livres
        FROM retour_commande r INNER JOIN `user` u ON u.id = r.id_user $conditions
        ORDER BY FIELD(r.statut, 'demande', 'recu', 'accepte') = 0, r.date_demande ASC, r.id_retour ASC
        LIMIT " . RETOURS_PAR_PAGE . " OFFSET " . (($page - 1) * RETOURS_PAR_PAGE));
    $stmt->execute($params);
    $retours = $stmt->fetchAll();
    foreach ($pdo->query("SELECT statut, COUNT(*) FROM retour_commande GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR) as $statut => $nombre) {
        $compteursRetours[$statut] = (int) $nombre;
    }
}

function lienRetours(array $changements): string
{
    $parametres = array_filter(array_merge(['statut' => $_GET['statut'] ?? '', 'q' => $_GET['q'] ?? '', 'type' => $_GET['type'] ?? ''], $changements), fn($valeur) => $valeur !== '' && $valeur !== null);
    return 'retours.php' . ($parametres ? '?' . http_build_query($parametres) : '');
}

$titrePage = 'Retours et remboursements';
$sousTitrePage = 'Demandes des clients après une livraison ou une annulation : demandé, accepté, reçu, remboursé.';
$pageActive = 'retours';
$filAriane = [[null, 'Ventes']];
include __DIR__ . '/_haut.php';
?>

<?php if (!$disponible): ?>
    <div class="alert alert-warning">Importez <code>sql/maj_retours_commande.sql</code> pour activer les retours.</div>
<?php else: ?>
    <div class="grille-indicateurs mb-4">
        <div class="stat"><div class="stat-haut"><span>Demandés</span><span class="stat-icone"><i class="bi bi-inbox" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursRetours['demande'] ?></div></div>
        <div class="stat"><div class="stat-haut"><span>Acceptés</span><span class="stat-icone"><i class="bi bi-truck" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursRetours['accepte'] ?></div></div>
        <div class="stat"><div class="stat-haut"><span>Reçus</span><span class="stat-icone"><i class="bi bi-box-seam" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursRetours['recu'] ?></div></div>
        <div class="stat"><div class="stat-haut"><span>Remboursés</span><span class="stat-icone"><i class="bi bi-cash-coin" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursRetours['rembourse'] ?></div></div>
    </div>

    <form method="GET" class="barre-filtres-admin mb-3">
        <div class="champ-recherche">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Référence, n° de commande, client…" value="<?= e($recherche) ?>" aria-label="Rechercher une demande">
        </div>
        <select name="statut" class="form-select form-select-sm" aria-label="Statut" onchange="this.form.submit()">
            <?php foreach (FILTRES_RETOURS as $cle => $libelle): ?>
                <option value="<?= $cle ?>" <?= $filtre === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-select form-select-sm" aria-label="Type" onchange="this.form.submit()">
            <option value="">Retours et remboursements</option>
            <option value="retour" <?= $type === 'retour' ? 'selected' : '' ?>>Retours après livraison</option>
            <option value="remboursement" <?= $type === 'remboursement' ? 'selected' : '' ?>>Remboursements après annulation</option>
        </select>
        <button class="btn btn-vert btn-sm">Filtrer</button>
        <?php if ($recherche !== '' || $type !== '' || $filtre !== 'a_traiter'): ?><a href="retours.php" class="btn btn-link btn-sm">Réinitialiser</a><?php endif; ?>
    </form>

    <?php if (!$retours): ?>
        <div class="carte etat-vide">
            <div class="icone-vide"><i class="bi bi-arrow-return-left" aria-hidden="true"></i></div>
            <strong><?= $filtre === 'a_traiter' && $recherche === '' && $type === '' ? 'Aucune demande en cours' : 'Aucune demande ne correspond' ?></strong>
        </div>
    <?php else: ?>
        <p class="small text-muted mb-2"><?= $total ?> demande<?= $total > 1 ? 's' : '' ?></p>
        <div class="carte table-responsive">
            <table class="table table-hover align-middle mb-0 table-cartes">
                <thead>
                    <tr>
                        <th scope="col">Demande</th>
                        <th scope="col">Client</th>
                        <th scope="col">Type et motif</th>
                        <th scope="col" class="text-end">Montant</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($retours as $retour): ?>
                        <tr>
                            <td data-libelle="Demande"><a href="retour.php?id=<?= (int) $retour['id_retour'] ?>" class="fw-bold"><?= e((string) $retour['reference']) ?></a><br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($retour['date_demande'])) ?> · commande n° <?= (int) $retour['id_commande'] ?></small></td>
                            <td data-libelle="Client">@<?= e($retour['pseudo']) ?></td>
                            <td data-libelle="Type"><?= $retour['type'] === 'retour' ? 'Retour (' . (int) $retour['nb_livres'] . ' livre' . ((int) $retour['nb_livres'] > 1 ? 's' : '') . ')' : 'Remboursement' ?><br><small class="text-muted"><?= e(libelleMotifRetour($retour['type'], $retour['motif'])) ?></small></td>
                            <td data-libelle="Montant" class="text-end"><strong><?= formaterPrix((float) $retour['montant']) ?></strong></td>
                            <td data-libelle="Statut"><span class="etiquette<?= in_array($retour['statut'], ['refuse', 'annule'], true) ? ' etiquette-neutre' : ($retour['statut'] === 'demande' ? ' etiquette-alerte' : '') ?>"><?= e(libelleStatutRetour($retour['statut'])) ?></span></td>
                            <td class="text-end"><a href="retour.php?id=<?= (int) $retour['id_retour'] ?>" class="btn btn-outline-success btn-sm">Traiter</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($nbPages > 1): ?>
            <?php $classePagination = 'mt-3'; $lienPage = fn(int $numero) => lienRetours(['page' => $numero > 1 ? $numero : '']); include __DIR__ . '/../partie_pagination.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
