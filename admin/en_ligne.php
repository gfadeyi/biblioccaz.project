<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../sessions_supervision_lib.php';
exigerDroit('admin');
header('Cache-Control: no-store');
$suiviDisponible = suiviSessionsDisponible($pdo) && roleSessionDisponible($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null)) { rediriger('en_ligne.php', 'danger', 'Requête invalide.'); }
    exigerPost('en_ligne.php');
    if (($_POST['action'] ?? '') === 'clients_auteurs') {
        $comptes = filter_var($_POST['comptes_attendus'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $sessions = filter_var($_POST['sessions_attendues'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if (!$suiviDisponible || $comptes === false || $sessions === false || ($_POST['confirmer'] ?? '') !== '1') { rediriger('en_ligne.php','danger','Aperçu ou confirmation invalide.'); }
        try {
            $bilan = revoquerSessionsClientsAuteurs($pdo,(int) $_SESSION['user_id'],$comptes,$sessions);
            insertLog('SECURITE', 'Fermeture globale clients/auteurs : ' . $bilan['sessions'] . ' sessions, ' . $bilan['utilisateurs'] . ' comptes');
            rediriger('en_ligne.php','success',$bilan['sessions'] . ' session(s) fermée(s) pour ' . $bilan['utilisateurs'] . ' compte(s) client ou auteur.');
        } catch (DomainException $e) { rediriger('en_ligne.php','warning',$e->getMessage()); }
        catch (PDOException $e) { rediriger('en_ligne.php','danger','Fermeture indisponible. Actualisez avant de réessayer.'); }
    }
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
$recherche = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,100) : '';
$role = is_string($_GET['role'] ?? null) && isset(ROLES_LIBELLES[$_GET['role']]) ? $_GET['role'] : '';
$multiples = ($_GET['multiples'] ?? '') === '1';
$page = filter_var($_GET['page'] ?? 1,FILTER_VALIDATE_INT,['options' => ['min_range'=>1]]) ?: 1;
$tableau = $suiviDisponible ? tableauSessions($pdo,$recherche,$role,$multiples,$page) : null;
include __DIR__ . '/_haut.php';
?>
<p class="text-muted small">Sessions valides selon le délai d'inactivité de dix minutes. La dernière requête connue n'est pas une preuve de présence humaine en temps réel. Une fermeture prend effet dès la prochaine requête authentifiée.</p>
<?php if (!$suiviDisponible): ?>
<div class="alert alert-warning" role="status">Le tableau nécessite la migration des sessions et sql/maj_sessions_supervision.sql. Aucun décompte ni fermeture n'est proposé tant que le suivi du rôle n'est pas disponible.</div>
<?php else: ?>
<?php include __DIR__ . '/_sessions_tableau.php'; ?>
<?php endif; ?>
<?php
include __DIR__ . '/_bas.php';
