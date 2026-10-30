<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../regles_emprunts_lib.php';
exigerDroit('admin');
$etat = chargerReglesEmprunts($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('regles_emprunts.php');
    try {
        if (!$etat['disponible']) { throw new DomainException('Les réglages ne sont pas encore installés.'); }
        if (($_POST['confirmer'] ?? '') !== '1') { throw new DomainException('Confirmez les modifications avant de les enregistrer.'); }
        $version = $_POST['version'] ?? null;
        if (!is_string($version) || !ctype_digit($version) || (int) $version < 1) { throw new DomainException('Version invalide. Rechargez la page.'); }
        if (!is_array($_POST['regles'] ?? null)) { throw new DomainException('Réglages invalides.'); }
        $modifie = enregistrerReglesEmprunts($pdo, (int) $_SESSION['user_id'], $_POST['regles'], (int) $version);
        if ($modifie) { insertLog('EMPRUNT', 'Modification des règles des emprunts, version ' . ((int) $version + 1)); }
        rediriger('regles_emprunts.php', 'success', $modifie ? 'Règles enregistrées. Les emprunts déjà réservés conservent leurs conditions.' : 'Aucune modification.');
    } catch (DomainException $erreur) {
        rediriger('regles_emprunts.php', 'danger', $erreur->getMessage());
    } catch (PDOException $erreur) {
        insertLog('ERREUR', 'Enregistrement des règles des emprunts impossible.');
        rediriger('regles_emprunts.php', 'danger', 'Enregistrement indisponible. Aucune règle modifiée.');
    }
}
$historique = [];
if ($etat['disponible']) {
    $requete = $pdo->prepare('SELECT h.*, u.pseudo FROM regle_emprunt_historique h LEFT JOIN user u ON u.id = h.id_modifie_par ORDER BY h.version DESC LIMIT 20');
    $requete->execute();
    $historique = $requete->fetchAll();
}
$titrePage = 'Règles des emprunts';
$pageActive = 'regles_emprunts';
include __DIR__ . '/_haut.php';
?>
<p class="text-muted">Les limites concernent les nouvelles demandes. Réduire une limite n'annule aucune réservation existante. Le délai de retrait et la durée de prolongation sont conservés pour chaque emprunt à sa réservation.</p>
<p class="text-muted">La durée et le nombre de renouvellements proposés s'appliquent aux nouveaux exemplaires. Les exemplaires déjà au catalogue gardent leurs réglages, modifiables depuis leur fiche.</p>
<?php if (!$etat['disponible']): ?><div class="alert alert-warning" role="status">Les valeurs par défaut sont utilisées. Installez la migration des règles des emprunts pour pouvoir les modifier.</div><?php endif; ?>
<form method="POST" class="carte p-4 mb-4">
    <?= champCsrf() ?>
    <input type="hidden" name="version" value="<?= (int) $etat['version'] ?>">
    <fieldset <?= $etat['disponible'] ? '' : 'disabled' ?>>
        <legend class="h5">Limites et durées</legend>
        <div class="row g-3">
        <?php foreach (REGLES_EMPRUNTS as $cle => [$libelle, , $minimum, $maximum]): ?>
            <div class="col-md-6">
                <label for="regle-<?= e($cle) ?>" class="form-label"><?= e($libelle) ?></label>
                <input type="number" id="regle-<?= e($cle) ?>" name="regles[<?= e($cle) ?>]" class="form-control" min="<?= $minimum ?>" max="<?= $maximum ?>" step="1" required value="<?= $etat['regles'][$cle] ?>">
                <div class="form-text">De <?= $minimum ?> à <?= $maximum ?><?= $cle === 'renouvellements_max' ? ' ; zéro interdit le renouvellement' : '' ?>.</div>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="form-check my-4">
            <input type="checkbox" id="confirmer" name="confirmer" value="1" class="form-check-input" required>
            <label class="form-check-label" for="confirmer">Je confirme l'application de ces règles aux nouvelles opérations.</label>
        </div>
        <button type="submit" class="btn btn-vert">Enregistrer les règles</button>
    </fieldset>
</form>
<section class="carte p-4" aria-labelledby="historique-regles">
    <h2 class="h5" id="historique-regles">Historique des modifications</h2>
    <?php if (!$historique): ?><p class="text-muted mb-0">Aucune modification enregistrée.</p><?php endif; ?>
    <?php foreach ($historique as $modification): ?>
        <details class="border rounded p-3 mt-3">
            <summary>Version <?= (int) $modification['version'] ?> · <?= e(date('d/m/Y H:i', strtotime($modification['date_modification']))) ?> · <?= e($modification['pseudo'] ?? 'Ancien administrateur') ?></summary>
            <ul class="mt-3 mb-0">
                <?php $ancienne = json_decode($modification['ancienne_configuration'], true, 512, JSON_THROW_ON_ERROR); $nouvelle = json_decode($modification['nouvelle_configuration'], true, 512, JSON_THROW_ON_ERROR); ?>
                <?php foreach (REGLES_EMPRUNTS as $cle => [$libelle]): ?>
                    <?php if ($ancienne[$cle] !== $nouvelle[$cle]): ?><li><?= e($libelle) ?> : <?= (int) $ancienne[$cle] ?> → <?= (int) $nouvelle[$cle] ?></li><?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/_bas.php'; ?>
