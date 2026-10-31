<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');
$defaut = droitsEquipeParDefaut(DROITS);
$etat = chargerParametreSite($pdo, 'droits_equipe', $defaut);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('droits_equipe.php');
    try {
        if (($_POST['confirmer'] ?? '') !== '1' || !is_array($_POST['droits'] ?? [])) { throw new DomainException('Confirmez la modification des droits.'); }
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT);
        if ($version === false || $version < 0) { throw new DomainException('Version invalide.'); }
        $droits = ($_POST['action'] ?? '') === 'retablir' ? $defaut : validerDroitsEquipe($_POST['droits'] ?? []);
        $modifie = enregistrerParametreSite($pdo, 'droits_equipe', $droits, $defaut, $version, (int) $_SESSION['user_id']);
        if ($modifie) { insertLog('DROITS', 'Modification des familles de droits de l’équipe, version ' . ($version + 1)); }
        rediriger('droits_equipe.php', 'success', $modifie ? 'Droits enregistrés. Ils s’appliquent dès la prochaine requête des membres de l’équipe.' : 'Aucune modification.');
    } catch (DomainException $erreur) {
        rediriger('droits_equipe.php', 'warning', $erreur->getMessage());
    } catch (Throwable $erreur) {
        error_log('Paramétrage des droits : ' . $erreur->getMessage());
        rediriger('droits_equipe.php', 'danger', 'Les droits n’ont pas pu être enregistrés.');
    }
}
$historique = $etat['disponible'] ? historiqueParametreSite($pdo, 'droits_equipe') : [];
$titrePage = 'Droits de l’équipe';
$pageActive = 'droits_equipe';
include __DIR__ . '/_haut.php';
?>
<p class="text-muted">Attribuez les familles de fonctionnalités aux gestionnaires et aux modérateurs. Les comptes clients et auteurs ne peuvent pas accéder au back-office.</p>
<div class="alert alert-info">L’administration des comptes, des paramètres, des sessions et du journal reste réservée aux administrateurs. Les droits des administrateurs ne peuvent pas être retirés ici.</div>
<?php if (!$etat['disponible']): ?><div class="alert alert-warning">Installez <code>sql/maj_parametres_site.sql</code> pour activer les réglages. Les droits habituels sont conservés.</div><?php endif; ?>
<form method="POST" class="carte p-4 mb-4">
    <?= champCsrf() ?>
    <input type="hidden" name="version" value="<?= (int) $etat['version'] ?>">
    <fieldset <?= $etat['disponible'] ? '' : 'disabled' ?>>
        <legend class="h5">Accès par famille</legend>
        <?php foreach (FAMILLES_DROITS_CONFIGURABLES as $famille => $libelle): ?>
            <section class="border rounded p-3 mb-3">
                <h2 class="h6"><?= e($libelle) ?></h2>
                <div class="d-flex flex-wrap gap-4">
                    <?php foreach (ROLES_DROITS_CONFIGURABLES as $role): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="droits[<?= e($famille) ?>][<?= e($role) ?>]" id="<?= e($famille . '-' . $role) ?>" value="1" <?= !empty($etat['valeurs'][$famille][$role]) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= e($famille . '-' . $role) ?>"><?= e(ROLES_LIBELLES[$role]) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <div class="form-check mb-3">
            <input class="form-check-input" id="confirmer" type="checkbox" name="confirmer" value="1" required>
            <label class="form-check-label" for="confirmer">Je confirme l’application de ces accès à toute l’équipe.</label>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success" name="action" value="enregistrer">Enregistrer les droits</button>
            <button class="btn btn-outline-secondary" name="action" value="retablir">Rétablir les droits habituels</button>
        </div>
    </fieldset>
</form>
<section class="carte p-4">
    <h2 class="h5">Historique</h2>
    <?php if (!$historique): ?><p class="text-muted mb-0">Aucune modification enregistrée.</p><?php endif; ?>
    <?php foreach ($historique as $ligne): ?><p class="mb-2">Version <?= (int) $ligne['version'] ?> · <?= e($ligne['date_modification']) ?> · <?= e($ligne['pseudo'] ?? 'Ancien administrateur') ?></p><?php endforeach; ?>
</section>
<?php include __DIR__ . '/_bas.php'; ?>
