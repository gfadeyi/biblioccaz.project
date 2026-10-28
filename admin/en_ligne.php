<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');
header('Cache-Control: no-store');
$suiviDisponible = suiviSessionsDisponible($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null)) { rediriger('en_ligne.php', 'danger', 'Requête invalide.'); }
    exigerPost('en_ligne.php');
    $idMembre = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $idSuivi = filter_var($_POST['id_suivi'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? '';
    if (!$suiviDisponible || !$idMembre || !$idSuivi || ($_POST['confirmer'] ?? '') !== '1' || !in_array($action, ['une', 'toutes'], true)) {
        rediriger('en_ligne.php', 'danger', 'Action invalide ou confirmation manquante.');
    }
    try {
        $nombre = revoquerSessionsUtilisateur($pdo, (int) $_SESSION['user_id'], $idMembre, $action === 'une' ? $idSuivi : null);
        insertLog('SECURITE', 'Revocation de ' . $nombre . ' connexion(s) du membre #' . $idMembre);
        rediriger('en_ligne.php', 'success', $nombre . ' connexion(s) fermée(s).');
    } catch (DomainException $e) {
        rediriger('en_ligne.php', 'danger', $e->getMessage());
    } catch (PDOException $e) {
        rediriger('en_ligne.php', 'danger', 'Le contrôle des connexions est temporairement indisponible.');
    }
}
$titrePage = 'Connexions actives';
$pageActive = 'en_ligne';
include __DIR__ . '/_haut.php';
?>
<p class="text-muted small">Connexions actives dans les dix dernières minutes, site et back-office distingués. Une fermeture prend effet à la prochaine requête. Les connexions des autres administrateurs sont protégées.</p>
<?php if (!$suiviDisponible): ?>
<div class="alert alert-warning" role="status">Le suivi individuel nécessite la migration des sessions. Aucune révocation n'est disponible pour le moment.</div>
<?php else: ?>
<div class="carte" id="suiviSessions" data-csrf="<?= e(jetonCsrf()) ?>">
    <div class="carte-entete"><span id="nbEnLigne" role="status">Chargement des connexions…</span><button type="button" class="btn btn-sm btn-outline-secondary" id="actualiserSessions">Actualiser</button></div>
    <p class="small text-muted px-3 pt-3">Confirmez avant de fermer une connexion ou toutes les connexions du même compte. La liste affiche au maximum 500 connexions.</p>
    <div class="table-responsive">
        <table class="table align-middle">
            <caption class="visually-hidden">Connexions actives et actions de fermeture</caption>
            <thead class="table-light small text-uppercase"><tr><th scope="col">Membre</th><th scope="col">Connexion</th><th scope="col">Dernière activité</th><th scope="col">Actions</th></tr></thead>
            <tbody id="corpsEnLigne"><tr><td colspan="4">Chargement…</td></tr></tbody>
        </table>
    </div>
    <noscript><p class="alert alert-info m-3">Activez JavaScript pour consulter et contrôler les connexions.</p></noscript>
</div>
<?php endif; ?>
<?php
$scriptsPage = '<script src="../javascript/sessions_admin.js?v=' . filemtime(__DIR__ . '/../javascript/sessions_admin.js') . '"></script>';
include __DIR__ . '/_bas.php';
