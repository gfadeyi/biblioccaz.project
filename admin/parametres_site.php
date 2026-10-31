<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../parametres_techniques_lib.php';
exigerDroit('admin');
$defaut = parametresTechniquesParDefaut();
$etat = chargerParametreSite($pdo, 'technique', $defaut);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('parametres_site.php');
    try {
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT);
        if ($version === false || $version < 0 || !is_array($_POST['reglages'] ?? null)) { throw new DomainException('Réglages invalides.'); }
        $valeurs = validerParametresTechniques($_POST['reglages']);
        $modifie = enregistrerParametreSite($pdo, 'technique', $valeurs, $defaut, $version, (int) $_SESSION['user_id']);
        if ($modifie) { insertLog('PARAMETRES', 'Modification des paramètres techniques, version ' . ($version + 1)); }
        rediriger('parametres_site.php', 'success', $modifie ? 'Paramètres enregistrés et publiés.' : 'Aucune modification.');
    } catch (DomainException $erreur) {
        rediriger('parametres_site.php', 'warning', $erreur->getMessage());
    } catch (Throwable $erreur) {
        error_log('Paramètres techniques : ' . $erreur->getMessage());
        rediriger('parametres_site.php', 'danger', 'Les paramètres n’ont pas pu être enregistrés.');
    }
}
$historique = $etat['disponible'] ? historiqueParametreSite($pdo, 'technique') : [];
$titrePage = 'Paramètres du site';
$pageActive = 'parametres_site';
include __DIR__ . '/_haut.php';
$groupes = [
    'Courriels' => ['mail_from', 'mail_from_name', 'mail_reply_to'],
    'Informations légales' => ['editeur_nom', 'editeur_statut', 'editeur_adresse', 'editeur_email', 'directeur_publication', 'hebergeur_nom', 'hebergeur_adresse', 'hebergeur_telephone', 'date_mise_a_jour'],
    'Textes des pages' => ['texte_mentions', 'texte_confidentialite', 'texte_cgu'],
];
?>
<p class="text-muted">Les informations actuelles sont conservées tant que vous ne les modifiez pas. Chaque enregistrement est historisé.</p>
<?php if (!$etat['disponible']): ?><div class="alert alert-warning">Installez <code>sql/maj_parametres_site.sql</code> pour activer les réglages.</div><?php endif; ?>
<form method="POST" class="carte p-4 mb-4">
    <?= champCsrf() ?>
    <input type="hidden" name="version" value="<?= (int) $etat['version'] ?>">
    <fieldset <?= $etat['disponible'] ? '' : 'disabled' ?>>
        <legend class="visually-hidden">Réglages du site</legend>
        <?php foreach ($groupes as $titre => $cles): ?>
            <details class="border rounded p-3 mb-3" <?= $titre === 'Courriels' ? 'open' : '' ?>>
                <summary class="fw-bold"><?= e($titre) ?></summary>
                <?php if ($titre === 'Courriels'): ?><p class="small text-muted mt-3">Choisissez une adresse d’envoi autorisée par votre fournisseur sur le domaine déjà configuré. Les identifiants SMTP restent dans la configuration privée.</p><?php endif; ?>
                <?php if ($titre === 'Textes des pages'): ?><p class="small text-muted mt-3">Texte brut, sans HTML. Un texte personnalisé remplace le contenu de la page correspondante. Laissez les mentions ou la confidentialité vides pour conserver les pages standard. Les CGU apparaissent dans le pied de page une fois renseignées.</p><?php endif; ?>
                <?php foreach ($cles as $cle): ?>
                    <?php [$libelle, $type, $maximum] = CHAMPS_TECHNIQUES[$cle]; ?>
                    <div class="mt-3">
                        <label class="form-label" for="reglage-<?= e($cle) ?>"><?= e($libelle) ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea id="reglage-<?= e($cle) ?>" name="reglages[<?= e($cle) ?>]" class="form-control" rows="<?= str_starts_with($cle, 'texte_') ? 8 : 3 ?>" maxlength="<?= $maximum ?>"><?= e($etat['valeurs'][$cle] ?? $defaut[$cle]) ?></textarea>
                        <?php else: ?>
                            <input id="reglage-<?= e($cle) ?>" name="reglages[<?= e($cle) ?>]" class="form-control" type="<?= e($type) ?>" maxlength="<?= $maximum ?>" value="<?= e($etat['valeurs'][$cle] ?? $defaut[$cle]) ?>" <?= in_array($cle, ['mail_from', 'mail_from_name'], true) ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
        <button class="btn btn-success">Enregistrer les paramètres</button>
    </fieldset>
</form>
<section class="carte p-4">
    <h2 class="h5">Historique</h2>
    <?php if (!$historique): ?><p class="text-muted mb-0">Aucune modification enregistrée.</p><?php endif; ?>
    <?php foreach ($historique as $ligne): ?><p class="mb-2">Version <?= (int) $ligne['version'] ?> · <?= e($ligne['date_modification']) ?> · <?= e($ligne['pseudo'] ?? 'Ancien administrateur') ?></p><?php endforeach; ?>
</section>
<?php include __DIR__ . '/_bas.php'; ?>
