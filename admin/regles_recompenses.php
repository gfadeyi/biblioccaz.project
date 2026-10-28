<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../points_lib.php';
exigerDroit('admin');
$etatRegles = chargerReglesRecompenses($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null)) { rediriger('regles_recompenses.php', 'danger', 'Requête invalide.'); }
    exigerPost('regles_recompenses.php');
    try {
        if (!$etatRegles['disponible'] || ($_POST['confirmer'] ?? '') !== '1') { throw new DomainException('La migration et la confirmation sont nécessaires avant enregistrement.'); }
        $version = entierRegle($_POST['version'] ?? null, 1, 1000000);
        $saisie = array_intersect_key($_POST, reglesRecompensesParDefaut());
        $modifie = enregistrerReglesRecompenses($pdo, (int) $_SESSION['user_id'], $saisie, $version);
        if ($modifie) { insertLog('RECOMPENSES', 'Modification du barème, version ' . ($version + 1)); }
        rediriger('regles_recompenses.php', 'success', $modifie ? 'Règles enregistrées. Les mouvements historiques sont conservés.' : 'Aucune modification.');
    } catch (DomainException $e) {
        rediriger('regles_recompenses.php', 'danger', $e->getMessage());
    } catch (PDOException $e) {
        rediriger('regles_recompenses.php', 'danger', 'Enregistrement indisponible. Aucune règle modifiée.');
    }
}
$regles = $etatRegles['regles'];
$historique = [];
if ($etatRegles['disponible']) {
    $stmt = $pdo->prepare('SELECT h.version, h.date_modification, h.ancienne_configuration, h.nouvelle_configuration, u.pseudo FROM regle_recompense_historique h LEFT JOIN user u ON u.id = h.id_modifie_par ORDER BY h.version DESC LIMIT 20');
    $stmt->execute();
    $historique = $stmt->fetchAll();
}
$titrePage = 'Règles de récompenses';
$pageActive = 'regles_recompenses';
include __DIR__ . '/_haut.php';
?>
<p class="text-muted">Barème appliqué aux prochaines attributions. Les points déjà gagnés et les récompenses en attente gardent leur montant. Abaisser un palier peut débloquer un nouveau badge lors du prochain contrôle de l'activité existante.</p>
<?php if (!$etatRegles['disponible']): ?><div class="alert alert-warning" role="status">Les règles par défaut sont utilisées. La migration des règles de récompenses est nécessaire pour les modifier.</div><?php endif; ?>
<form method="POST" class="carte p-4">
    <?= champCsrf() ?>
    <input type="hidden" name="version" value="<?= (int) $etatRegles['version'] ?>">
    <fieldset <?= $etatRegles['disponible'] ? '' : 'disabled' ?>>
        <legend class="h5">Points attribués</legend>
        <div class="row g-3 mb-4">
        <?php foreach (BAREME_RECOMPENSES as $cle => [$libelle, , $maximum]): ?>
            <div class="col-md-6 col-xl-4"><label for="points-<?= e($cle) ?>" class="form-label"><?= e($libelle) ?></label><input id="points-<?= e($cle) ?>" name="points[<?= e($cle) ?>]" type="number" class="form-control" min="0" max="<?= $maximum ?>" step="1" required value="<?= $regles['points'][$cle] ?>"></div>
        <?php endforeach; ?>
        </div>
        <p class="small text-muted">Zéro désactive les points de cette action, sans supprimer son historique ni ses badges.</p>
        <h2 class="h5">Plafonds mensuels de récompenses</h2>
        <div class="row g-3 mb-4">
        <?php foreach (QUOTAS_RECOMPENSES as $cle => $defaut): ?>
            <div class="col-md-4"><label for="quota-<?= e($cle) ?>" class="form-label"><?= e(BAREME_RECOMPENSES[$cle][0]) ?></label><input id="quota-<?= e($cle) ?>" name="quotas[<?= e($cle) ?>]" type="number" class="form-control" min="0" max="100" step="1" required value="<?= $regles['quotas'][$cle] ?>"></div>
        <?php endforeach; ?>
        </div>
        <h2 class="h5">Seuils des six niveaux</h2>
        <p class="small text-muted">Les noms et avantages restent inchangés. Le total gagné historique est comparé aux nouveaux seuils, sans réécriture des points.</p>
        <div class="row g-3 mb-4">
        <?php foreach (NIVEAUX as $index => $niveau): ?>
            <div class="col-md-6 col-xl-4"><label for="niveau-<?= $index ?>" class="form-label"><?= e($niveau['nom']) ?></label><input id="niveau-<?= $index ?>" name="niveaux[<?= $index ?>]" type="number" class="form-control" min="0" max="1000000" step="1" required value="<?= $regles['niveaux'][$index] ?>"></div>
        <?php endforeach; ?>
        </div>
        <h2 class="h5">Paliers des badges</h2>
        <?php foreach (badgesRegles($pdo) as $code => $badge): ?>
            <fieldset class="border rounded p-3 mb-3"><legend class="float-none w-auto px-2 fs-6"><?= e($badge['nom']) ?></legend>
            <?php if (isset($badge['rare'])): ?><p class="small text-muted"><?= e($badge['rare']) ?>. Le nombre cible est réglable ci-dessous.</p><?php endif; ?>
            <div class="row g-3">
            <?php foreach ($regles['badges'][$code] as $index => $seuil): ?>
                <div class="col-sm-4"><label for="badge-<?= e($code) ?>-<?= $index ?>" class="form-label"><?= isset($badge['rare']) ? 'Objectif' : e(PALIERS_BADGES[$index + 1]) ?></label><input id="badge-<?= e($code) ?>-<?= $index ?>" name="badges[<?= e($code) ?>][<?= $index ?>]" type="number" class="form-control" min="1" max="<?= in_array($code, ['explorateur', 'pilier'], true) ? 6 : 100000 ?>" step="1" required value="<?= $seuil ?>"></div>
            <?php endforeach; ?>
            </div></fieldset>
        <?php endforeach; ?>
        <label class="form-check py-3"><input type="checkbox" name="confirmer" value="1" class="form-check-input" required><span class="form-check-label">Je confirme l'application des règles aux prochaines attributions et contrôles de niveaux/badges.</span></label>
        <button type="submit" class="btn btn-vert py-3">Enregistrer les règles</button>
    </fieldset>
</form>
<section class="carte p-4 mt-4" aria-labelledby="historiqueRegles"><h2 id="historiqueRegles" class="h5">Historique des vingt dernières versions</h2>
<?php if (!$historique): ?><p class="text-muted mb-0">Aucune modification enregistrée.</p><?php endif; ?>
<?php foreach ($historique as $ligne): ?>
    <details class="border-bottom py-3"><summary>Version <?= (int) $ligne['version'] ?> — <?= e($ligne['pseudo'] ?? 'Compte archivé') ?> — <?= e($ligne['date_modification']) ?></summary>
    <ul class="small mt-2">
    <?php $ancienne = json_decode($ligne['ancienne_configuration'], true); $nouvelle = json_decode($ligne['nouvelle_configuration'], true); ?>
    <?php foreach ($nouvelle as $section => $champs): foreach ($champs as $cle => $valeur): if (($ancienne[$section][$cle] ?? null) === $valeur) continue; ?>
        <li><?= e($section . ' / ' . $cle) ?> : <?= e(json_encode($ancienne[$section][$cle] ?? null)) ?> → <?= e(json_encode($valeur)) ?></li>
    <?php endforeach; endforeach; ?>
    </ul></details>
<?php endforeach; ?>
</section>
<?php include __DIR__ . '/_bas.php'; ?>
