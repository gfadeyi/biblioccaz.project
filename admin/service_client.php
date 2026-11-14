<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../service_client_lib.php';
exigerDroit('equipe');

const DEMANDES_PAR_PAGE = 30;
const FILTRES_DEMANDES = ['a_traiter' => 'À traiter', 'ouvertes' => 'Ouvertes', 'nouvelle' => 'Nouvelles', 'en_cours' => 'En cours', 'attente_client' => 'Réponse envoyée', 'resolue' => 'Résolues', 'toutes' => 'Toutes'];

$disponible = serviceClientDisponible($pdo);
$idMoi = (int) $_SESSION['user_id'];
$filtre = array_key_exists($_GET['statut'] ?? '', FILTRES_DEMANDES) ? $_GET['statut'] : 'a_traiter';
$categorie = array_key_exists($_GET['categorie'] ?? '', SERVICE_CATEGORIES) ? $_GET['categorie'] : '';
$attribution = in_array($_GET['attribution'] ?? '', ['moi', 'personne'], true) ? $_GET['attribution'] : '';
$recherche = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$roleMoi = (string) $_SESSION['role'];
$perimetre = $roleMoi !== 'admin' && ($_GET['perimetre'] ?? '') !== 'equipe' ? 'mes' : 'equipe';
$categoriesMiennes = categoriesServicePourRole($pdo, $roleMoi);
$page = max(1, (int) ($_GET['page'] ?? 1));
$demandes = [];
$total = 0;
$nbPages = 1;
$compteursDemandes = ['a_traiter' => 0, 'nouvelle' => 0, 'moi' => 0, 'resolue_semaine' => 0];

if ($disponible) {
    $where = [];
    $params = [];
    [$conditionPerimetre, $paramsPerimetre] = conditionPerimetreService($pdo, $roleMoi, $idMoi);
    if ($perimetre === 'mes') {
        $where[] = $conditionPerimetre;
        array_push($params, ...$paramsPerimetre);
    }
    if ($filtre === 'a_traiter') {
        $where[] = "(d.statut = 'nouvelle' OR (d.statut <> 'resolue' AND d.non_lu_equipe = 1))";
    } elseif ($filtre === 'ouvertes') {
        $where[] = "d.statut <> 'resolue'";
    } elseif ($filtre !== 'toutes') {
        $where[] = "d.statut = ?";
        $params[] = $filtre;
    }
    if ($categorie !== '') {
        $where[] = "d.categorie = ?";
        $params[] = $categorie;
    }
    if ($attribution === 'moi') {
        $where[] = "d.id_assigne = ?";
        $params[] = $idMoi;
    } elseif ($attribution === 'personne') {
        $where[] = "d.id_assigne IS NULL";
    }
    if ($recherche !== '') {
        $numero = ltrim($recherche, "#nN°o \t");
        if (ctype_digit($numero)) {
            $where[] = "(d.id_commande = ? OR d.id_demande = ?)";
            array_push($params, (int) $numero, (int) $numero);
        } else {
            $motif = '%' . addcslashes($recherche, '%_\\') . '%';
            $where[] = "(d.reference LIKE ? OR d.sujet LIKE ? OR u.pseudo LIKE ? OR u.email LIKE ?)";
            array_push($params, $motif, $motif, $motif, $motif);
        }
    }
    $conditions = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client d INNER JOIN `user` u ON u.id = d.id_user $conditions");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    $nbPages = max(1, (int) ceil($total / DEMANDES_PAR_PAGE));
    $page = min($page, $nbPages);
    $stmt = $pdo->prepare("SELECT d.*, u.pseudo, a.pseudo AS pseudo_assigne,
            (SELECT COUNT(*) FROM demande_client_message m WHERE m.id_demande = d.id_demande AND m.cote IN ('client', 'equipe')) AS nb_messages
        FROM demande_client d INNER JOIN `user` u ON u.id = d.id_user LEFT JOIN `user` a ON a.id = d.id_assigne $conditions
        ORDER BY d.statut = 'resolue', d.non_lu_equipe DESC, d.statut = 'nouvelle' DESC, d.date_maj ASC, d.id_demande ASC
        LIMIT " . DEMANDES_PAR_PAGE . " OFFSET " . (($page - 1) * DEMANDES_PAR_PAGE));
    $stmt->execute($params);
    $demandes = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(statut = 'nouvelle' OR (statut <> 'resolue' AND non_lu_equipe = 1)), 0),
            COALESCE(SUM(statut = 'nouvelle'), 0),
            COALESCE(SUM(statut <> 'resolue' AND id_assigne = ?), 0),
            COALESCE(SUM(statut = 'resolue' AND date_cloture > NOW() - INTERVAL 7 DAY), 0)
        FROM demande_client d WHERE " . ($perimetre === 'mes' ? $conditionPerimetre : '1 = 1'));
    $stmt->execute($perimetre === 'mes' ? [$idMoi, ...$paramsPerimetre] : [$idMoi]);
    $compteursDemandes = array_combine(array_keys($compteursDemandes), array_map('intval', $stmt->fetch(PDO::FETCH_NUM)));
}

function lienDemandes(array $changements): string
{
    $parametres = array_filter(array_merge(['statut' => $_GET['statut'] ?? '', 'categorie' => $_GET['categorie'] ?? '', 'attribution' => $_GET['attribution'] ?? '', 'perimetre' => $_GET['perimetre'] ?? '', 'q' => $_GET['q'] ?? ''], $changements), fn($valeur) => $valeur !== '' && $valeur !== null);
    return 'service_client.php' . ($parametres ? '?' . http_build_query($parametres) : '');
}

$titrePage = 'Service client';
$sousTitrePage = 'Questions des clients : prenez une demande en charge, répondez ou confiez-la à un collègue.';
$libellesCategoriesMiennes = array_map(fn($cle) => SERVICE_CATEGORIES[$cle], array_values(array_intersect(array_keys(SERVICE_CATEGORIES), $categoriesMiennes)));
$pageActive = 'service_client';
$filAriane = [[null, 'Ventes']];
include __DIR__ . '/_haut.php';
?>

<?php if (!$disponible): ?>
    <div class="alert alert-warning">Importez <code>sql/maj_service_client.sql</code> pour activer le service client.</div>
<?php else: ?>
    <?php if ($roleMoi !== 'admin'): ?>
        <div class="perimetre-service mb-3" data-perimetre-service>
            <p class="small mb-2"><strong>Votre pôle traite :</strong> <?= e($libellesCategoriesMiennes ? implode(', ', $libellesCategoriesMiennes) : 'aucune catégorie') ?>, ainsi que les demandes qui vous sont confiées. Vous pouvez lire toutes les autres et les confier à un collègue.</p>
            <div class="btn-group btn-group-sm" role="group" aria-label="Périmètre des demandes">
                <a class="btn <?= $perimetre === 'mes' ? 'btn-vert' : 'btn-outline-secondary' ?>" href="<?= e(lienDemandes(['perimetre' => '', 'page' => ''])) ?>"<?= $perimetre === 'mes' ? ' aria-current="page"' : '' ?>>Mon pôle</a>
                <a class="btn <?= $perimetre === 'equipe' ? 'btn-vert' : 'btn-outline-secondary' ?>" href="<?= e(lienDemandes(['perimetre' => 'equipe', 'page' => ''])) ?>"<?= $perimetre === 'equipe' ? ' aria-current="page"' : '' ?>>Toute l’équipe</a>
            </div>
        </div>
    <?php endif; ?>
    <div class="grille-indicateurs mb-4">
        <a class="stat" href="<?= e(lienDemandes(['statut' => '', 'attribution' => '', 'page' => ''])) ?>"><div class="stat-haut"><span>À traiter</span><span class="stat-icone"><i class="bi bi-inbox" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursDemandes['a_traiter'] ?></div></a>
        <a class="stat" href="<?= e(lienDemandes(['statut' => 'nouvelle', 'attribution' => '', 'page' => ''])) ?>"><div class="stat-haut"><span>Nouvelles</span><span class="stat-icone"><i class="bi bi-envelope" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursDemandes['nouvelle'] ?></div></a>
        <a class="stat" href="<?= e(lienDemandes(['statut' => 'ouvertes', 'attribution' => 'moi', 'page' => ''])) ?>"><div class="stat-haut"><span>Mes demandes</span><span class="stat-icone"><i class="bi bi-person-check" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursDemandes['moi'] ?></div></a>
        <div class="stat"><div class="stat-haut"><span>Résolues (7 j)</span><span class="stat-icone"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><div class="stat-valeur"><?= $compteursDemandes['resolue_semaine'] ?></div></div>
    </div>

    <form method="GET" class="barre-filtres-admin mb-3">
        <?php if ($perimetre === 'equipe' && $roleMoi !== 'admin'): ?><input type="hidden" name="perimetre" value="equipe"><?php endif; ?>
        <div class="champ-recherche">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Référence, sujet, client, n° de commande…" value="<?= e($recherche) ?>" aria-label="Rechercher une demande">
        </div>
        <select name="statut" class="form-select form-select-sm" aria-label="Statut" onchange="this.form.submit()">
            <?php foreach (FILTRES_DEMANDES as $cle => $libelle): ?>
                <option value="<?= $cle ?>" <?= $filtre === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
            <?php endforeach; ?>
        </select>
        <select name="categorie" class="form-select form-select-sm" aria-label="Catégorie" onchange="this.form.submit()">
            <option value="">Toutes les catégories</option>
            <?php foreach (SERVICE_CATEGORIES as $cle => $libelle): ?>
                <option value="<?= $cle ?>" <?= $categorie === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="attribution" class="form-select form-select-sm" aria-label="Attribution" onchange="this.form.submit()">
            <option value="">Tous les responsables</option>
            <option value="moi" <?= $attribution === 'moi' ? 'selected' : '' ?>>Confiées à moi</option>
            <option value="personne" <?= $attribution === 'personne' ? 'selected' : '' ?>>Sans responsable</option>
        </select>
        <button class="btn btn-vert btn-sm">Filtrer</button>
        <?php if ($recherche !== '' || $categorie !== '' || $attribution !== '' || $filtre !== 'a_traiter'): ?><a href="service_client.php" class="btn btn-link btn-sm">Réinitialiser</a><?php endif; ?>
    </form>

    <?php if (!$demandes): ?>
        <div class="carte etat-vide">
            <div class="icone-vide"><i class="bi bi-headset" aria-hidden="true"></i></div>
            <strong><?= $filtre === 'a_traiter' && $recherche === '' && $categorie === '' && $attribution === '' ? 'Aucune demande à traiter' : 'Aucune demande ne correspond' ?></strong>
        </div>
    <?php else: ?>
        <p class="small text-muted mb-2"><?= $total ?> demande<?= $total > 1 ? 's' : '' ?></p>
        <div class="carte table-responsive">
            <table class="table table-hover align-middle mb-0 table-cartes">
                <thead>
                    <tr>
                        <th scope="col">Demande</th>
                        <th scope="col">Client</th>
                        <th scope="col">Catégorie</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandes as $demande): ?>
                        <tr>
                            <td data-libelle="Demande">
                                <a href="demande_client.php?id=<?= (int) $demande['id_demande'] ?>" class="fw-bold"><?= e($demande['sujet']) ?></a>
                                <?php if ((int) $demande['non_lu_equipe'] && $demande['statut'] !== 'resolue'): ?><span class="etiquette etiquette-alerte ms-1">Nouveau message</span><?php endif; ?>
                                <br><small class="text-muted"><?= e((string) $demande['reference']) ?> · <?= (int) $demande['nb_messages'] ?> message<?= (int) $demande['nb_messages'] > 1 ? 's' : '' ?> · <?= date('d/m/Y H:i', strtotime($demande['date_maj'])) ?><?= $demande['id_commande'] ? ' · commande n° ' . (int) $demande['id_commande'] : '' ?></small>
                            </td>
                            <td data-libelle="Client">@<?= e($demande['pseudo']) ?></td>
                            <td data-libelle="Catégorie"><?= e(SERVICE_CATEGORIES[$demande['categorie']] ?? 'Autre') ?><?php if ($roleMoi !== 'admin' && !in_array($demande['categorie'], $categoriesMiennes, true) && (int) $demande['id_assigne'] !== $idMoi): ?><br><small class="text-muted">autre pôle · lecture</small><?php endif; ?></td>
                            <td data-libelle="Responsable"><?= $demande['pseudo_assigne'] ? '@' . e($demande['pseudo_assigne']) : '<span class="text-muted">Personne</span>' ?></td>
                            <td data-libelle="Statut"><span class="etiquette<?= $demande['statut'] === 'resolue' ? ' etiquette-neutre' : ($demande['statut'] === 'nouvelle' ? ' etiquette-alerte' : '') ?>"><?= e(libelleStatutDemande($demande['statut'])) ?></span></td>
                            <td class="text-end"><a href="demande_client.php?id=<?= (int) $demande['id_demande'] ?>" class="btn btn-outline-success btn-sm">Ouvrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($nbPages > 1): ?>
            <?php $classePagination = 'mt-3'; $lienPage = fn(int $numero) => lienDemandes(['page' => $numero > 1 ? $numero : '']); include __DIR__ . '/../partie_pagination.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
