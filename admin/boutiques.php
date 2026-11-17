<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

$bibliothequeBoutiques = __DIR__ . '/../emprunts_boutiques_lib.php';
if (is_file($bibliothequeBoutiques)) {
    require_once $bibliothequeBoutiques;
}

$disponible = false;
$boutiques = [];
$membres = [];
$stockSansBoutique = [];
$repriseDisponible = false;
$messageMigration = 'La gestion des boutiques est indisponible. Vérifiez la bibliothèque emprunts_boutiques_lib.php et la migration sql/maj_emprunts_boutiques.sql.';

try {
    if (function_exists('empruntsBoutiquesDisponibles') && function_exists('boutiquesEmprunt')
        && function_exists('affecterMembreBoutique') && function_exists('modifierInformationsBoutique')) {
        $disponible = empruntsBoutiquesDisponibles($pdo);
    }
    if ($disponible) {
        $boutiques = boutiquesEmprunt($pdo, false);
        $requete = $pdo->prepare("SELECT u.id, u.pseudo, u.role, be.id_boutique
            FROM user u LEFT JOIN boutique_equipe be ON be.id_user = u.id
            WHERE u.role IN (?, ?) AND u.statut = ? ORDER BY u.pseudo, u.id");
        $requete->execute(['gestionnaire', 'moderateur', 'actif']);
        $membres = $requete->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $exception) {
    $disponible = false;
    $boutiques = [];
    $membres = [];
}

if ($disponible) {
    require_once __DIR__ . '/../emprunts_lib.php';
    try {
        $requete = $pdo->prepare("SELECT l.id_livre, l.titre, COUNT(*) AS nombre_disponibles
            FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre
            WHERE e.type_offre = ? AND e.id_proprietaire IS NULL AND e.is_disponible = 1 AND e.id_boutique IS NULL AND e.statut_pret = ?
              AND NOT EXISTS (SELECT 1 FROM emprunt em WHERE em.id_exemplaire = e.id_exemplaire AND em.statut IN (?, ?))
            GROUP BY l.id_livre, l.titre ORDER BY l.titre, l.id_livre LIMIT 100");
        $requete->execute(['emprunt', 'disponible', 'reserve', 'en_cours']);
        $stockSansBoutique = $requete->fetchAll(PDO::FETCH_ASSOC);
        $repriseDisponible = function_exists('affecterStockDisponibleBoutique');
    } catch (PDOException $exception) {
        $stockSansBoutique = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('boutiques');
    if (!$disponible) {
        rediriger('boutiques', 'warning', $messageMigration);
    }
    $action = $_POST['action'] ?? '';
    $idBoutique = filter_var($_POST['id_boutique'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $boutiqueChoisie = null;
    foreach ($boutiques as $boutique) {
        if ((int) $boutique['id_boutique'] === $idBoutique) {
            $boutiqueChoisie = $boutique;
            break;
        }
    }
    try {
        if ($action === 'coordonnees') {
            $adresse = $_POST['adresse'] ?? '';
            $horaires = $_POST['horaires'] ?? '';
            if (!$boutiqueChoisie || !is_string($adresse) || !is_string($horaires)) {
                rediriger('boutiques', 'danger', 'Boutique ou coordonnées invalides.');
            }
            $adresse = trim($adresse);
            $horaires = trim($horaires);
            if (mb_strlen($adresse) > 255 || mb_strlen($horaires) > 255) {
                rediriger('boutiques', 'danger', 'L’adresse et les horaires sont limités à 255 caractères chacun.');
            }
            [$type, $message] = modifierInformationsBoutique($pdo, $idBoutique, $adresse, $horaires);
        } elseif ($action === 'affectation') {
            $idMembre = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($idMembre === false || $idBoutique === false || ($idBoutique !== 0 && (!$boutiqueChoisie || !(bool) $boutiqueChoisie['actif']))) {
                rediriger('boutiques', 'danger', 'Membre ou boutique active invalide.');
            }
            $requete = $pdo->prepare('SELECT id FROM user WHERE id = ? AND role IN (?, ?) AND statut = ?');
            $requete->execute([$idMembre, 'gestionnaire', 'moderateur', 'actif']);
            if (!$requete->fetchColumn()) {
                rediriger('boutiques', 'danger', 'Seuls les gestionnaires et modérateurs actifs peuvent être affectés.');
            }
            [$type, $message] = affecterMembreBoutique($pdo, $idMembre, $idBoutique);
        } elseif ($action === 'reprise_stock') {
            $idLivre = filter_var($_POST['id_livre'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $idEquipe = (int) ($_SESSION['user_id'] ?? 0);
            if (!$repriseDisponible) {
                rediriger('boutiques', 'warning', $messageMigration);
            }
            if ($idLivre === false || $idEquipe < 1 || !$boutiqueChoisie || !(bool) $boutiqueChoisie['actif']) {
                rediriger('boutiques', 'danger', 'Livre, membre de l’équipe ou boutique active invalide.');
            }
            if (($_POST['presence_physique'] ?? '') !== '1') {
                rediriger('boutiques', 'danger', 'Confirmez la présence physique de tout le stock concerné dans la boutique choisie.');
            }
            [$type, $message] = affecterStockDisponibleBoutique($pdo, $idLivre, $idBoutique, $idEquipe);
        } else {
            rediriger('boutiques', 'danger', 'Action inconnue.');
        }
        rediriger('boutiques', in_array($type, ['success', 'warning', 'danger', 'info'], true) ? $type : 'info', $message);
    } catch (PDOException $exception) {
        rediriger('boutiques', 'danger', 'Enregistrement impossible. Vérifiez la migration sql/maj_emprunts_boutiques.sql avant de réessayer.');
    }
}

$titrePage = 'Boutiques de démonstration';
$sousTitrePage = 'Coordonnées pratiques et affectations de l’équipe aux six boutiques du réseau fictif.';
$pageActive = 'boutiques';
include __DIR__ . '/_haut.php';
?>

<div class="alert alert-warning" role="status">
    <span>Réseau fictif de démonstration : ces boutiques ne sont pas des points de retrait réels. N’inventez pas d’adresse réelle. L’adresse et les horaires peuvent rester vides.</span>
</div>

<?php if (!$disponible): ?>
    <div class="alert alert-info" role="status"><?= e($messageMigration) ?></div>
<?php else: ?>
    <section aria-labelledby="titre-boutiques" class="mb-4">
        <h2 class="h5 mb-3" id="titre-boutiques">Boutiques <span class="etiquette etiquette-neutre"><?= count($boutiques) ?></span></h2>
        <?php if (!$boutiques): ?>
            <p class="text-muted">Aucune boutique disponible. Vérifiez la migration sql/maj_emprunts_boutiques.sql.</p>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($boutiques as $boutique): ?>
                <?php $idBoutique = (int) $boutique['id_boutique']; ?>
                <div class="col-12 col-lg-6">
                    <div class="carte carte-corps h-100">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <h3 class="h6 mb-0"><?= e($boutique['nom']) ?></h3>
                            <span class="etiquette etiquette-neutre"><?= $boutique['actif'] ? 'Active' : 'Inactive' ?></span>
                        </div>
                        <p class="text-muted small"><?= e($boutique['ville']) ?> · <?= e($boutique['slug']) ?></p>
                        <form method="post" action="boutiques">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="coordonnees">
                            <input type="hidden" name="id_boutique" value="<?= $idBoutique ?>">
                            <div class="mb-3">
                                <label class="form-label" for="adresse-<?= $idBoutique ?>">Adresse de démonstration</label>
                                <input class="form-control" id="adresse-<?= $idBoutique ?>" name="adresse" maxlength="255" value="<?= e($boutique['adresse']) ?>" aria-describedby="aide-coordonnees-<?= $idBoutique ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="horaires-<?= $idBoutique ?>">Horaires de démonstration</label>
                                <input class="form-control" id="horaires-<?= $idBoutique ?>" name="horaires" maxlength="255" value="<?= e($boutique['horaires']) ?>" aria-describedby="aide-coordonnees-<?= $idBoutique ?>">
                            </div>
                            <p class="form-text" id="aide-coordonnees-<?= $idBoutique ?>">Champs facultatifs, 255 caractères maximum chacun.</p>
                            <button class="btn btn-vert" type="submit">Enregistrer les coordonnées</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="carte carte-corps mb-4" aria-labelledby="titre-reprise-stock">
        <h2 class="h5" id="titre-reprise-stock">Reprise du stock sans boutique</h2>
        <p class="text-muted">Exemplaires d’emprunt disponibles sans boutique, hors réservations et prêts en cours. Au maximum 100 livres sont affichés. Aucune affectation automatique.</p>
        <?php if (!$repriseDisponible): ?>
            <div class="alert alert-info" role="status">Reprise indisponible : vérifiez la fonction affecterStockDisponibleBoutique et la migration sql/maj_emprunts_boutiques.sql.</div>
        <?php elseif (!$stockSansBoutique): ?>
            <p class="mb-0">Aucun stock disponible sans boutique à reprendre.</p>
        <?php else: ?>
            <?php foreach ($stockSansBoutique as $stockLivre): ?>
                <?php $idLivre = (int) $stockLivre['id_livre']; ?>
                <form method="post" action="boutiques" class="row g-3 border-top py-3">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="reprise_stock">
                    <input type="hidden" name="id_livre" value="<?= $idLivre ?>">
                    <div class="col-12 col-lg-5">
                        <h3 class="h6"><a href="exemplaires?id=<?= $idLivre ?>&amp;vue=emprunt"><?= e($stockLivre['titre']) ?></a></h3>
                        <p class="mb-0"><?= (int) $stockLivre['nombre_disponibles'] ?> exemplaire(s) disponible(s) sans boutique</p>
                    </div>
                    <div class="col-12 col-lg-7">
                        <label class="form-label" for="boutique-stock-<?= $idLivre ?>">Boutique où se trouve le stock</label>
                        <select class="form-select" name="id_boutique" id="boutique-stock-<?= $idLivre ?>" required>
                            <option value="" selected disabled>Choisir explicitement une boutique</option>
                            <?php foreach ($boutiques as $boutique): ?>
                                <?php if (!$boutique['actif']) { continue; } ?>
                                <option value="<?= (int) $boutique['id_boutique'] ?>"><?= e($boutique['nom']) ?> — <?= e($boutique['ville']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-check my-3">
                            <input class="form-check-input" type="checkbox" name="presence_physique" value="1" id="presence-stock-<?= $idLivre ?>" required>
                            <label class="form-check-label" for="presence-stock-<?= $idLivre ?>">Je confirme que tous les exemplaires disponibles sans boutique de ce livre à affecter sont physiquement présents dans la boutique choisie.</label>
                        </div>
                        <button class="btn btn-vert" type="submit">Affecter le stock disponible</button>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="carte carte-corps" aria-labelledby="titre-affectations">
        <h2 class="h5" id="titre-affectations">Affectations de l’équipe</h2>
        <p class="text-muted">Gestionnaires et modérateurs actifs uniquement. Aucune affectation automatique. Les comptes administrateurs sont exclus.</p>
        <?php if (!$membres): ?>
            <p class="mb-0">Aucun membre éligible.</p>
        <?php endif; ?>
        <?php foreach ($membres as $membre): ?>
            <?php
            $idMembre = (int) $membre['id'];
            $affectationActuelle = (int) ($membre['id_boutique'] ?? 0);
            $affectationProposee = $affectationActuelle === 0;
            foreach ($boutiques as $boutique) {
                if ((int) $boutique['id_boutique'] === $affectationActuelle && $boutique['actif']) {
                    $affectationProposee = true;
                }
            }
            ?>
            <form method="post" action="boutiques" class="row g-2 align-items-end border-top py-3">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="affectation">
                <input type="hidden" name="id_user" value="<?= $idMembre ?>">
                <div class="col-12 col-md-4">
                    <p class="fw-semibold mb-1"><?= e($membre['pseudo']) ?></p>
                    <span class="text-muted small"><?= e(ROLES_LIBELLES[$membre['role']] ?? $membre['role']) ?></span>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="boutique-membre-<?= $idMembre ?>">Boutique de <?= e($membre['pseudo']) ?></label>
                    <select class="form-select" name="id_boutique" id="boutique-membre-<?= $idMembre ?>" required>
                        <?php if (!$affectationProposee): ?>
                            <option value="" selected disabled>Affectation inactive ou indisponible : choisir une action</option>
                        <?php endif; ?>
                        <option value="0"<?= $affectationActuelle === 0 ? ' selected' : '' ?>>Aucune boutique (retirer l’affectation)</option>
                        <?php foreach ($boutiques as $boutique): ?>
                            <?php if (!$boutique['actif']) { continue; } ?>
                            <option value="<?= (int) $boutique['id_boutique'] ?>"<?= (int) $boutique['id_boutique'] === $affectationActuelle ? ' selected' : '' ?>><?= e($boutique['nom']) ?> — <?= e($boutique['ville']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-vert w-100" type="submit">Enregistrer l’affectation</button>
                </div>
            </form>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
