<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('utilisateurs.php');
    $idUser = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($idUser === (int) $_SESSION['user_id']) {
        rediriger('utilisateurs.php', 'warning', "Vous ne pouvez pas modifier votre propre compte ici.");
    }

    $stmt = $pdo->prepare("SELECT pseudo FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $pseudo = $stmt->fetchColumn();
    if ($pseudo === false) {
        rediriger('utilisateurs.php', 'warning', "Ce compte n'existe pas.");
    }

    switch ($action) {
        case 'accepter':
            $pdo->prepare("UPDATE user SET role = 'moderateur', statut = 'valide_moderateur' WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Candidature acceptée : @$pseudo devient modérateur");
            rediriger('utilisateurs.php', 'success', "@$pseudo est maintenant modérateur.");

        case 'refus_temporaire':
        case 'refus_definitif':
            $statut = $action === 'refus_temporaire' ? 'refuse_temporaire' : 'refuse_definitif';
            $pdo->prepare("UPDATE user SET statut = ? WHERE id = ?")->execute([$statut, $idUser]);
            insertLog('ADMIN', "Candidature de @$pseudo refusée ($statut)");
            rediriger('utilisateurs.php', 'success', "Candidature refusée.");

        case 'changer_role':
            $role = $_POST['nouveau_role'] ?? '';
            if (!isset(ROLES_LIBELLES[$role])) {
                rediriger('utilisateurs.php', 'danger', "Rôle inconnu.");
            }
            $pdo->prepare("UPDATE user SET role = ? WHERE id = ?")->execute([$role, $idUser]);
            insertLog('ADMIN', "Rôle de @$pseudo changé en $role");
            rediriger('utilisateurs.php', 'success', "@$pseudo est maintenant " . mb_strtolower(ROLES_LIBELLES[$role]) . ".");

        case 'suspendre':
            $pdo->prepare("UPDATE user SET statut = 'refuse_definitif' WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Compte de @$pseudo suspendu");
            rediriger('utilisateurs.php', 'success', "Le compte de @$pseudo est suspendu : il ne peut plus se connecter.");

        case 'reactiver':
            $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Compte de @$pseudo réactivé");
            rediriger('utilisateurs.php', 'success', "Le compte de @$pseudo est réactivé.");

        case 'supprimer':
            $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Suppression définitive du compte #$idUser (@$pseudo)");
            rediriger('utilisateurs.php', 'success', "Le compte de @$pseudo a été supprimé.");
    }
    rediriger('utilisateurs.php');
}

$q = trim($_GET['q'] ?? '');
$filtreRole = $_GET['role'] ?? '';
$where = ["statut <> 'en_attente_moderateur'"];
$params = [];
if ($q !== '') {
    $where[] = "(pseudo LIKE ? OR email LIKE ? OR nom LIKE ? OR prenom LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (isset(ROLES_LIBELLES[$filtreRole])) {
    $where[] = "role = ?";
    $params[] = $filtreRole;
}
$stmt = $pdo->prepare("
    SELECT id, pseudo, email, nom, prenom, role, statut, email_verifie, date_inscription, last_activity
    FROM user WHERE " . implode(' AND ', $where) . "
    ORDER BY FIELD(role, 'admin', 'gestionnaire', 'moderateur', 'auteur', 'client', 'user'), pseudo");
$stmt->execute($params);
$membres = $stmt->fetchAll();

$candidatures = $pdo->query("SELECT id, pseudo, email, nom, prenom, date_inscription FROM user WHERE statut = 'en_attente_moderateur' ORDER BY id")->fetchAll();

function badgeStatut(string $statut): string
{
    return match ($statut) {
        'refuse_definitif', 'banni' => '<span class="badge bg-danger">Suspendu</span>',
        'refuse_temporaire'         => '<span class="badge bg-warning text-dark">Refusé (temp.)</span>',
        'valide_moderateur'         => '<span class="badge bg-info text-dark">Accepté, 1re connexion à venir</span>',
        default                     => '<span class="badge bg-success">Actif</span>',
    };
}

function boutonAction(int $id, string $action, string $libelle, string $classe, string $confirmation = ''): string
{
    return '<form method="POST" class="d-inline"' . ($confirmation ? ' data-confirmer="' . e($confirmation) . '"' : '') . '>'
        . champCsrf()
        . '<input type="hidden" name="user_id" value="' . $id . '">'
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<button class="btn btn-sm ' . $classe . '">' . $libelle . '</button></form>';
}

$titrePage = 'Membres';
$pageActive = 'utilisateurs';
include __DIR__ . '/_haut.php';
?>

<?php if ($candidatures): ?>
    <div class="carte mb-4 border-warning">
        <div class="carte-entete bg-warning-subtle"><span><i class="bi bi-person-plus me-2"></i>Candidatures modérateur (<?= count($candidatures) ?>)</span></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <tbody>
                    <?php foreach ($candidatures as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e(trim($c['prenom'] . ' ' . $c['nom'])) ?: '@' . e($c['pseudo']) ?></div>
                                <div class="small text-muted">@<?= e($c['pseudo']) ?> · <?= e($c['email']) ?></div>
                            </td>
                            <td class="text-end text-nowrap">
                                <?= boutonAction($c['id'], 'accepter', '<i class="bi bi-check-lg"></i> Accepter', 'btn-success') ?>
                                <?= boutonAction($c['id'], 'refus_temporaire', 'Refuser pour l\'instant', 'btn-outline-warning', 'Refuser temporairement cette candidature ?') ?>
                                <?= boutonAction($c['id'], 'refus_definitif', 'Refuser définitivement', 'btn-outline-danger', 'Refuser définitivement cette candidature ?') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" style="max-width: 320px;" placeholder="Pseudo, email ou nom">
        <select name="role" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
            <option value="">Tous les rôles</option>
            <?php foreach (ROLES_LIBELLES as $cle => $libelle): ?>
                <option value="<?= $cle ?>" <?= $filtreRole === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        <?php if ($q !== '' || $filtreRole !== ''): ?><a href="utilisateurs.php" class="btn btn-link">Effacer</a><?php endif; ?>
    </form>
    <a href="export_membres.php" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</a>
</div>

<div class="carte">
    <div class="carte-entete"><?= count($membres) ?> compte<?= count($membres) > 1 ? 's' : '' ?></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase">
                <tr><th>Membre</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php if (!$membres): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun compte ne correspond.</td></tr>
                <?php endif; ?>
                <?php foreach ($membres as $m): ?>
                    <?php $moi = (int) $m['id'] === (int) $_SESSION['user_id']; ?>
                    <?php $suspendu = in_array($m['statut'], ['refuse_definitif', 'refuse_temporaire', 'banni'], true); ?>
                    <tr>
                        <td>
                            <div class="fw-bold">@<?= e($m['pseudo']) ?> <?php if ($moi): ?><span class="badge bg-secondary">vous</span><?php endif; ?></div>
                            <div class="small text-muted">
                                <?= e(trim($m['prenom'] . ' ' . $m['nom'])) ?> · <?= e($m['email']) ?>
                                <?php if (!$m['email_verifie']): ?><span class="badge bg-light text-muted border">email non confirmé</span><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($moi): ?>
                                <?= ROLES_LIBELLES[$m['role']] ?? e($m['role']) ?>
                            <?php else: ?>
                                <form method="POST" class="d-flex gap-1">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="action" value="changer_role">
                                    <select name="nouveau_role" class="form-select form-select-sm" style="width: auto;" aria-label="Rôle de @<?= e($m['pseudo']) ?>">
                                        <?php foreach (ROLES_LIBELLES as $cle => $libelle): ?>
                                            <option value="<?= $cle ?>" <?= $m['role'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary" title="Appliquer"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeStatut($m['statut']) ?></td>
                        <td class="small text-muted"><?= date('d/m/Y', strtotime($m['date_inscription'])) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if (!$moi): ?>
                                <?= $suspendu
                                    ? boutonAction($m['id'], 'reactiver', '<i class="bi bi-unlock"></i> Réactiver', 'btn-outline-success')
                                    : boutonAction($m['id'], 'suspendre', '<i class="bi bi-slash-circle"></i> Suspendre', 'btn-outline-warning', 'Suspendre ce compte ? La personne ne pourra plus se connecter.') ?>
                                <?= boutonAction($m['id'], 'supprimer', '<i class="bi bi-trash"></i>', 'btn-outline-danger', 'Supprimer définitivement ce compte et ses commandes, avis et dons ?') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
