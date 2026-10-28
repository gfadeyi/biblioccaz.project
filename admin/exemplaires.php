<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../compte_lib.php';
exigerDroit('catalogue');

const ETATS_EXEMPLAIRE = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'Usé'];

$idLivre = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT id_livre, titre, auteur, couverture FROM livre WHERE id_livre = ?");
$stmt->execute([$idLivre]);
$livre = $stmt->fetch();
if (!$livre) {
    rediriger('inventaire.php', 'warning', "Ce livre n'existe pas.");
}
$retour = "exemplaires.php?id=$idLivre";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $action = $_POST['action'] ?? '';
    $idEx = (int) ($_POST['id_exemplaire'] ?? 0);

    if ($action === 'ajouter') {
        $etat = $_POST['etat'] ?? '';
        $prix = round((float) str_replace(',', '.', $_POST['prix'] ?? '0'), 2);
        $quantite = max(1, (int) ($_POST['quantite'] ?? 1));
        if (!isset(ETATS_EXEMPLAIRE[$etat]) || $prix <= 0) {
            rediriger($retour, 'danger', "Choisissez un état et un prix supérieur à 0.");
        }
        $stmt = $pdo->prepare("SELECT e.id_exemplaire FROM exemplaire e WHERE e.id_livre = ? AND e.etat = ? AND e.prix = ? AND " . conditionExemplaireMagasin() . " ORDER BY e.id_exemplaire LIMIT 1");
        $stmt->execute([$idLivre, $etat, $prix]);
        $idExistant = (int) $stmt->fetchColumn();
        if ($idExistant) {
            $pdo->prepare("UPDATE exemplaire SET quantite = quantite + ?, is_disponible = 1 WHERE id_exemplaire = ?")->execute([$quantite, $idExistant]);
        } else {
            $pdo->prepare("INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite, id_user) VALUES (?, ?, ?, 'vente', 1, ?, ?)")
                ->execute([$idLivre, $etat, $prix, $quantite, $_SESSION['user_id']]);
        }
        insertLog('STOCK', "Ajout de $quantite ex. ($etat, $prix €) pour le livre #$idLivre");
        $prevenus = notifierRetourEnStock($pdo, $idLivre);
        rediriger($retour, 'success', "$quantite exemplaire" . ($quantite > 1 ? 's ajoutés' : ' ajouté') . " au stock."
            . ($prevenus ? " $prevenus client" . ($prevenus > 1 ? 's qui attendaient ce livre ont été prévenus' : ' qui attendait ce livre a été prévenu') . " par mail." : ''));
    }

    if (str_starts_with($action, 'emprunt_')) {
        require_once __DIR__ . '/../emprunts_lib.php';
        $duree = (int) ($_POST['duree'] ?? DUREE_EMPRUNT_DEFAUT);
        $renouvellements = (int) ($_POST['renouvellements'] ?? RENOUVELLEMENTS_DEFAUT);
        $blocage = !empty($_POST['blocage_retard']);
        try {
            if ($action === 'emprunt_ajouter') {
                $source = (string) ($_POST['source'] ?? '');
                $idOffreVente = ctype_digit($source) ? (int) $source : null;
                $etat = $_POST['etat_emprunt'] ?? '';
                if (!$idOffreVente && !isset(ETATS_EXEMPLAIRE[$etat])) {
                    rediriger("$retour#emprunt", 'danger', "Choisissez l'état du nouvel exemplaire.");
                }
                [$type, $message] = mettreEnEmprunt($pdo, $idLivre, (string) $etat, $idOffreVente, $duree, $renouvellements, $blocage, (int) $_SESSION['user_id']);
            } elseif ($action === 'emprunt_reglages') {
                [$type, $message] = modifierReglagesEmprunt($pdo, $idEx, $duree, $renouvellements, $blocage);
            } elseif ($action === 'emprunt_retirer' || $action === 'emprunt_remettre') {
                [$type, $message] = changerDisponibiliteEmprunt($pdo, $idEx, $action === 'emprunt_remettre');
            } elseif ($action === 'emprunt_supprimer') {
                [$type, $message] = supprimerExemplaireEmprunt($pdo, $idEx);
            } else {
                [$type, $message] = ['danger', "Action inconnue."];
            }
        } catch (PDOException $e) {
            [$type, $message] = ['danger', "L'emprunt n'est pas encore installé : importez sql/maj_emprunts.sql."];
        }
        if ($type === 'success') {
            insertLog('STOCK', "Emprunt ($action) pour le livre #$idLivre : $message");
        }
        rediriger("$retour#emprunt", $type, $message);
    }

    if ($action === 'plus') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite + 1 WHERE id_exemplaire = ? AND id_livre = ? AND id_proprietaire IS NULL AND type_offre = 'vente'")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock +1 pour l'exemplaire #$idEx");
        notifierRetourEnStock($pdo, $idLivre);
    } elseif ($action === 'moins') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ? AND id_livre = ? AND quantite > 0 AND id_proprietaire IS NULL AND type_offre = 'vente'")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock -1 pour l'exemplaire #$idEx");
    } elseif ($action === 'vider') {
        $pdo->prepare("UPDATE exemplaire SET quantite = 0 WHERE id_exemplaire = ? AND id_livre = ? AND id_proprietaire IS NULL AND type_offre = 'vente'")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Offre exemplaire #$idEx retirée de la vente (stock à 0)");
        rediriger($retour, 'success', "Offre retirée de la vente : elle reste dans l'historique avec un stock de 0.");
    } elseif ($action === 'supprimer') {
        $stmt = $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND id_livre = ? AND quantite = 0 AND id_proprietaire IS NULL AND type_offre = 'vente' AND NOT EXISTS (SELECT 1 FROM commande_ligne cl WHERE cl.id_exemplaire = exemplaire.id_exemplaire)");
        $stmt->execute([$idEx, $idLivre]);
        if ($stmt->rowCount() === 0) {
            rediriger($retour, 'warning', "Cette offre a déjà été vendue : elle est conservée pour l'historique des commandes.");
        }
        insertLog('STOCK', "Suppression de l'offre exemplaire #$idEx (jamais vendue)");
        rediriger($retour, 'success', "Offre supprimée.");
    }
    rediriger($retour);
}

$stmt = $pdo->prepare("
    SELECT e.*, COALESCE(SUM(CASE WHEN c.id_commande IS NOT NULL THEN cl.quantite END), 0) AS nb_vendus, COUNT(cl.id_ligne) AS nb_lignes_commande
    FROM exemplaire e
    LEFT JOIN commande_ligne cl ON cl.id_exemplaire = e.id_exemplaire
    LEFT JOIN commande c ON c.id_commande = cl.id_commande AND c.statut <> 'annulee'
    WHERE e.id_livre = ? AND " . conditionExemplaireMagasin() . "
    GROUP BY e.id_exemplaire
    ORDER BY e.prix");
$stmt->execute([$idLivre]);
$toutesOffres = $stmt->fetchAll();
$exemplaires = array_values(array_filter($toutesOffres, fn($offre) => (int) $offre['quantite'] > 0));
$offresEpuisees = array_values(array_filter($toutesOffres, fn($offre) => (int) $offre['quantite'] <= 0));
$total = array_sum(array_column($exemplaires, 'quantite'));
$exemplairesEmprunt = null;
try {
    require_once __DIR__ . '/../emprunts_lib.php';
    $stmt = $pdo->prepare("
        SELECT e.*, (SELECT COUNT(*) FROM emprunt em WHERE em.id_exemplaire = e.id_exemplaire AND em.statut IN ('en_cours', 'rendu')) AS nb_prets,
               ea.id_emprunt AS id_emprunt_actif, ea.date_retour_prevue AS retour_prevu, u.pseudo AS emprunteur,
               (ea.statut = 'en_cours' AND ea.date_retour_prevue < CURDATE()) AS en_retard
        FROM exemplaire e
        LEFT JOIN emprunt ea ON ea.id_exemplaire = e.id_exemplaire AND ea.statut IN ('reserve', 'en_cours')
        LEFT JOIN `user` u ON u.id = ea.id_user
        WHERE e.id_livre = ? AND " . conditionExemplaireEmprunt() . "
        ORDER BY e.is_disponible DESC, e.id_exemplaire");
    $stmt->execute([$idLivre]);
    $exemplairesEmprunt = $stmt->fetchAll();
} catch (PDOException $e) {
}

$titrePage = 'Stock : ' . $livre['titre'];
$pageActive = 'inventaire';
$filAriane = [[null, 'Catalogue'], ['inventaire.php', 'Inventaire & stock']];
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="inventaire.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Inventaire</a>
    <a href="livre.php?id=<?= $idLivre ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier la fiche</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form method="POST" class="carte p-4">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="ajouter">
            <div class="d-flex gap-3 mb-3">
                <img src="<?= imageLivre($livre['couverture']) ?>" alt="" class="rounded shadow-sm" style="width: 70px; height: 100px; object-fit: cover;">
                <div>
                    <div class="fw-bold"><?= e($livre['titre']) ?></div>
                    <div class="small text-muted"><?= e($livre['auteur']) ?></div>
                    <div class="small mt-1"><strong><?= $total ?></strong> ex. en stock</div>
                </div>
            </div>
            <h2 class="h6 fw-bold text-success">Ajouter des exemplaires</h2>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="etat">État</label>
                <select id="etat" name="etat" class="form-select">
                    <?php foreach (ETATS_EXEMPLAIRE as $cle => $libelle): ?>
                        <option value="<?= $cle ?>" <?= $cle === 'bon' ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold" for="prix">Prix (€)</label>
                    <input type="number" id="prix" name="prix" step="0.01" min="0.01" class="form-control" placeholder="12.50" required>
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold" for="quantite">Quantité</label>
                    <input type="number" id="quantite" name="quantite" value="1" min="1" class="form-control">
                </div>
            </div>
            <button class="btn btn-vert w-100 fw-bold"><i class="bi bi-plus-lg"></i> Ajouter au stock</button>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="carte mb-4">
            <div class="carte-entete">Offres en vente</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small text-uppercase"><tr><th>État</th><th>Prix</th><th class="text-center">Vendus</th><th class="text-center">Quantité</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php if (!$exemplaires): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucun exemplaire en stock.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($exemplaires as $ex): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span></td>
                                <td class="fw-bold text-success"><?= formaterPrix($ex['prix']) ?></td>
                                <td class="text-center text-muted"><?= (int) $ex['nb_vendus'] ?></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <?php foreach (['moins' => 'dash', 'plus' => 'plus'] as $act => $icone): ?>
                                            <?php if ($act === 'plus'): ?><span class="fw-bold" style="min-width: 2ch;"><?= $ex['quantite'] ?></span><?php endif; ?>
                                            <form method="POST">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="<?= $act ?>">
                                                <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                                <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0;" aria-label="<?= $act === 'plus' ? 'Ajouter un' : 'Retirer un' ?> exemplaire"><i class="bi bi-<?= $icone ?>"></i></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <form method="POST" data-confirmer="Retirer cette offre de la vente ? Son stock passe à 0 (<?= (int) $ex['quantite'] ?> ex.), elle reste dans l'historique." data-bouton-confirmer="Retirer de la vente">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="action" value="vider">
                                        <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Retirer de la vente"><i class="bi bi-box-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Retirer de la vente</span></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($offresEpuisees): ?>
            <div class="carte">
                <div class="carte-entete">Offres épuisées <span class="text-muted small fw-normal">Conservées pour l'historique des commandes</span></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-uppercase"><tr><th>État</th><th>Prix</th><th class="text-center">Vendus</th><th class="text-center">Remettre en stock</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($offresEpuisees as $ex): ?>
                                <tr class="text-muted">
                                    <td><span class="badge bg-light text-secondary border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span></td>
                                    <td><?= formaterPrix($ex['prix']) ?></td>
                                    <td class="text-center"><?= (int) $ex['nb_vendus'] ?></td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <?= champCsrf() ?>
                                            <input type="hidden" name="action" value="plus">
                                            <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                            <button class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-plus" aria-hidden="true"></i> 1 exemplaire</button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <?php if ((int) $ex['nb_lignes_commande'] === 0): ?>
                                            <form method="POST" data-confirmer="Supprimer définitivement cette offre jamais vendue ?" data-bouton-confirmer="Supprimer">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="Supprimer l'offre"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer l'offre</span></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="small" title="Cette offre figure dans des commandes"><i class="bi bi-lock" aria-hidden="true"></i> Conservée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="carte mt-4" id="emprunt">
            <div class="carte-entete">Emprunt (bibliothèque) <span class="text-muted small fw-normal">Exemplaires prêtés gratuitement, hors stock de vente</span></div>
            <?php if ($exemplairesEmprunt === null): ?>
                <p class="p-4 mb-0 text-muted small">L'emprunt n'est pas encore installé : importez <code>sql/maj_emprunts.sql</code>.</p>
            <?php else: ?>
                <?php if ($exemplairesEmprunt): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small text-uppercase"><tr><th>Exemplaire</th><th>Statut</th><th>Réglages</th><th class="text-center">Prêts</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($exemplairesEmprunt as $ex): ?>
                                    <?php $retire = !(int) $ex['is_disponible']; ?>
                                    <tr class="<?= $retire ? 'text-muted' : '' ?>">
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span>
                                            <div class="small text-muted">n° <?= (int) $ex['id_exemplaire'] ?></div>
                                        </td>
                                        <td class="small">
                                            <?php if ($retire): ?>
                                                <span class="badge bg-light text-secondary border">Retiré de l'emprunt</span>
                                            <?php elseif ($ex['statut_pret'] === 'disponible'): ?>
                                                <span class="badge bg-success-subtle text-success-emphasis">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge <?= $ex['en_retard'] ? 'bg-danger-subtle text-danger-emphasis' : 'bg-light text-dark border' ?>"><?= e(STATUTS_PRET[$ex['statut_pret']] ?? $ex['statut_pret']) ?><?= $ex['en_retard'] ? ', en retard' : '' ?></span>
                                                <?php if ($ex['id_emprunt_actif']): ?>
                                                    <div><a href="emprunts.php?id=<?= (int) $ex['id_emprunt_actif'] ?>">@<?= e($ex['emprunteur']) ?></a><?= $ex['retour_prevu'] ? ' · retour le ' . date('d/m/Y', strtotime($ex['retour_prevu'])) : '' ?></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex flex-wrap align-items-center gap-1 small">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="emprunt_reglages">
                                                <input type="hidden" name="id_exemplaire" value="<?= (int) $ex['id_exemplaire'] ?>">
                                                <label class="visually-hidden" for="duree-<?= (int) $ex['id_exemplaire'] ?>">Durée en jours</label>
                                                <input type="number" id="duree-<?= (int) $ex['id_exemplaire'] ?>" name="duree" value="<?= (int) $ex['duree_emprunt_jours'] ?>" min="7" max="90" class="form-control form-control-sm" style="width: 4.5rem;"> j
                                                <label class="visually-hidden" for="renouv-<?= (int) $ex['id_exemplaire'] ?>">Renouvellements</label>
                                                <input type="number" id="renouv-<?= (int) $ex['id_exemplaire'] ?>" name="renouvellements" value="<?= (int) $ex['renouvellements_max'] ?>" min="0" max="5" class="form-control form-control-sm" style="width: 3.8rem;"> renouv.
                                                <span class="form-check form-check-inline m-0 ms-1">
                                                    <input type="checkbox" class="form-check-input" id="blocage-<?= (int) $ex['id_exemplaire'] ?>" name="blocage_retard" value="1" <?= (int) $ex['blocage_retard'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="blocage-<?= (int) $ex['id_exemplaire'] ?>">blocage si retard</label>
                                                </span>
                                                <button class="btn btn-sm btn-outline-success" title="Enregistrer les réglages"><i class="bi bi-check2" aria-hidden="true"></i><span class="visually-hidden">Enregistrer les réglages</span></button>
                                            </form>
                                        </td>
                                        <td class="text-center text-muted"><?= (int) $ex['nb_prets'] ?></td>
                                        <td class="text-end">
                                            <?php if ($retire): ?>
                                                <div class="d-inline-flex gap-1">
                                                    <form method="POST">
                                                        <?= champCsrf() ?>
                                                        <input type="hidden" name="action" value="emprunt_remettre">
                                                        <input type="hidden" name="id_exemplaire" value="<?= (int) $ex['id_exemplaire'] ?>">
                                                        <button class="btn btn-sm btn-outline-success" title="Remettre en emprunt"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="visually-hidden">Remettre en emprunt</span></button>
                                                    </form>
                                                    <?php if ((int) $ex['nb_prets'] === 0): ?>
                                                        <form method="POST" data-confirmer="Supprimer cet exemplaire d'emprunt jamais prêté ?" data-bouton-confirmer="Supprimer">
                                                            <?= champCsrf() ?>
                                                            <input type="hidden" name="action" value="emprunt_supprimer">
                                                            <input type="hidden" name="id_exemplaire" value="<?= (int) $ex['id_exemplaire'] ?>">
                                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer</span></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($ex['statut_pret'] === 'disponible'): ?>
                                                <form method="POST" data-confirmer="Retirer cet exemplaire de l'emprunt ? Il ne sera plus proposé aux lecteurs, l'historique est conservé." data-bouton-confirmer="Retirer">
                                                    <?= champCsrf() ?>
                                                    <input type="hidden" name="action" value="emprunt_retirer">
                                                    <input type="hidden" name="id_exemplaire" value="<?= (int) $ex['id_exemplaire'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" title="Retirer de l'emprunt"><i class="bi bi-box-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Retirer de l'emprunt</span></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="small text-muted" title="Attendez le retour pour le retirer"><i class="bi bi-lock" aria-hidden="true"></i><span class="visually-hidden">Prêté : attendez le retour</span></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <form method="POST" class="carte-corps border-top">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="emprunt_ajouter">
                    <h2 class="h6 fw-bold text-success">Mettre un exemplaire en emprunt</h2>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold" for="source">Exemplaire</label>
                            <select id="source" name="source" class="form-select form-select-sm">
                                <?php foreach ($exemplaires as $ex): ?>
                                    <option value="<?= (int) $ex['id_exemplaire'] ?>">Prendre 1 ex. du stock : <?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? $ex['etat']) ?>, <?= formaterPrix($ex['prix']) ?> (<?= (int) $ex['quantite'] ?> en stock)</option>
                                <?php endforeach; ?>
                                <option value="nouveau" <?= $exemplaires ? '' : 'selected' ?>>Nouvel exemplaire réservé à l'emprunt</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" for="etat_emprunt">État (nouvel ex.)</label>
                            <select id="etat_emprunt" name="etat_emprunt" class="form-select form-select-sm">
                                <?php foreach (ETATS_EXEMPLAIRE as $cle => $libelle): ?>
                                    <option value="<?= $cle ?>" <?= $cle === 'bon' ? 'selected' : '' ?>><?= $libelle ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold" for="duree">Durée (jours)</label>
                            <input type="number" id="duree" name="duree" value="<?= DUREE_EMPRUNT_DEFAUT ?>" min="7" max="90" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold" for="renouvellements">Renouvellements</label>
                            <input type="number" id="renouvellements" name="renouvellements" value="<?= RENOUVELLEMENTS_DEFAUT ?>" min="0" max="5" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                        <div class="form-check small m-0">
                            <input type="checkbox" class="form-check-input" id="blocage_retard" name="blocage_retard" value="1" checked>
                            <label class="form-check-label" for="blocage_retard">Bloquer les emprunts du lecteur en cas de retard (autant de jours que de retard, <?= BLOCAGE_MAX_JOURS ?> au maximum)</label>
                        </div>
                        <button class="btn btn-sm btn-vert fw-bold"><i class="bi bi-book" aria-hidden="true"></i> Mettre en emprunt</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
