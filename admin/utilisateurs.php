<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$filtreRole = is_string($_GET['role'] ?? null) && isset(ROLES_LIBELLES[$_GET['role']]) ? $_GET['role'] : '';
$tris = [
    'recents' => ['Les plus récents', 'date_inscription DESC, id DESC'],
    'anciens' => ['Les plus anciens', 'date_inscription ASC, id ASC'],
    'roles' => ['Par rôle puis pseudo', "FIELD(role, 'admin', 'gestionnaire', 'moderateur', 'auteur', 'client'), pseudo, id"],
];
$tri = is_string($_GET['tri'] ?? null) && isset($tris[$_GET['tri']]) ? $_GET['tri'] : 'recents';
$retourMembres = 'utilisateurs.php?' . http_build_query(['q' => $q, 'role' => $filtreRole, 'tri' => $tri]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retourMembres);
    $idUser = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($idUser === (int) $_SESSION['user_id']) {
        rediriger($retourMembres, 'warning', "Vous ne pouvez pas modifier votre propre compte ici.");
    }

    $stmt = $pdo->prepare("SELECT pseudo, role FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $cible = $stmt->fetch();
    if (!$cible) {
        rediriger($retourMembres, 'warning', "Ce compte n'existe pas.");
    }
    $pseudo = $cible['pseudo'];
    if ($cible['role'] === 'admin') {
        rediriger($retourMembres, 'warning', "Un compte administrateur ne peut pas être modifié, suspendu ou supprimé depuis cette page.");
    }

    switch ($action) {
        case 'changer_role':
            $role = $_POST['nouveau_role'] ?? '';
            if (!in_array($role, ROLES_ATTRIBUABLES, true)) {
                rediriger($retourMembres, 'danger', "Rôle inconnu.");
            }
            $pdo->prepare("UPDATE user SET role = ? WHERE id = ?")->execute([$role, $idUser]);
            insertLog('ADMIN', "Rôle de @$pseudo changé en $role");
            rediriger($retourMembres, 'success', "@$pseudo est maintenant " . mb_strtolower(ROLES_LIBELLES[$role]) . ".");

        case 'suspendre':
            $pdo->prepare("UPDATE user SET statut = 'refuse_definitif' WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Compte de @$pseudo suspendu");
            rediriger($retourMembres, 'success', "Le compte de @$pseudo est suspendu : il ne peut plus se connecter.");

        case 'reactiver':
            $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Compte de @$pseudo réactivé");
            rediriger($retourMembres, 'success', "Le compte de @$pseudo est réactivé.");

        case 'supprimer':
            $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$idUser]);
            insertLog('ADMIN', "Suppression définitive du compte #$idUser (@$pseudo)");
            rediriger($retourMembres, 'success', "Le compte de @$pseudo a été supprimé.");
    }
    rediriger($retourMembres);
}

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
$requeteMembres = "
    SELECT id, pseudo, email, nom, prenom, role, statut, email_verifie, is_newsletter, date_inscription, last_activity%s
    FROM user WHERE " . implode(' AND ', $where) . "
    ORDER BY " . $tris[$tri][1];
try {
    $stmt = $pdo->prepare(sprintf($requeteMembres, ', telephone'));
    $stmt->execute($params);
} catch (PDOException $e) {
    $stmt = $pdo->prepare(sprintf($requeteMembres, ''));
    $stmt->execute($params);
}
$membres = $stmt->fetchAll();

$nbCandidatures = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();

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

<?php if ($nbCandidatures): ?>
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span><i class="bi bi-person-plus me-1" aria-hidden="true"></i> <?= $nbCandidatures ?> candidature<?= $nbCandidatures > 1 ? 's' : '' ?> à la modération en attente.</span>
        <a href="candidatures.php" class="btn btn-sm btn-success">Voir les candidatures</a>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher un membre" style="max-width: 320px;" placeholder="Pseudo, email ou nom">
        <select name="role" class="form-select" aria-label="Filtrer par rôle" style="max-width: 200px;" onchange="this.form.submit()">
            <option value="">Tous les rôles</option>
            <?php foreach (ROLES_LIBELLES as $cle => $libelle): ?>
                <option value="<?= $cle ?>" <?= $filtreRole === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tri" class="form-select" aria-label="Trier les membres" style="max-width: 240px;" onchange="this.form.submit()">
            <?php foreach ($tris as $cle => [$libelle]): ?>
                <option value="<?= e($cle) ?>" <?= $tri === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
        <?php if ($q !== '' || $filtreRole !== ''): ?><a href="utilisateurs.php?<?= e(http_build_query(['tri' => $tri])) ?>" class="btn btn-link">Effacer</a><?php endif; ?>
    </form>
    <a href="membre_creer.php" class="btn btn-success"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Créer un compte</a>
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
                    <?php $verrouille = $moi || $m['role'] === 'admin'; ?>
                    <?php $suspendu = in_array($m['statut'], ['refuse_definitif', 'refuse_temporaire', 'banni'], true); ?>
                    <tr>
                        <td>
                            <div class="fw-bold">@<?= e($m['pseudo']) ?> <?php if ($moi): ?><span class="badge bg-secondary">vous</span><?php endif; ?></div>
                            <div class="small text-muted">
                                <?= e(trim($m['prenom'] . ' ' . $m['nom'])) ?> · <?= e($m['email']) ?>
                                <?php if (!empty($m['telephone'])): ?> · <i class="bi bi-telephone" aria-hidden="true"></i><span class="visually-hidden">Téléphone :</span> <?= e($m['telephone']) ?><?php endif; ?>
                                <?php if ($m['is_newsletter']): ?><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-envelope-paper"></i> newsletter</span><?php endif; ?>
                                <?php if (!$m['email_verifie']): ?><span class="badge bg-light text-muted border">email non confirmé</span><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($verrouille): ?>
                                <span class="badge <?= $m['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>" title="Rôle non modifiable"><i class="bi bi-lock-fill"></i> <?= ROLES_LIBELLES[$m['role']] ?? e($m['role']) ?></span>
                            <?php else: ?>
                                <form method="POST" class="d-flex gap-1">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="action" value="changer_role">
                                    <select name="nouveau_role" class="form-select form-select-sm" style="width: auto;" aria-label="Rôle de @<?= e($m['pseudo']) ?>">
                                        <?php foreach (ROLES_ATTRIBUABLES as $cle): ?>
                                            <option value="<?= $cle ?>" <?= $m['role'] === $cle ? 'selected' : '' ?>><?= ROLES_LIBELLES[$cle] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary" title="Appliquer"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeStatut($m['statut']) ?></td>
                        <td class="small text-muted"><?= date('d/m/Y', strtotime($m['date_inscription'])) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if (!$verrouille): ?>
                                <?= $suspendu
                                    ? boutonAction($m['id'], 'reactiver', '<i class="bi bi-unlock"></i> Réactiver', 'btn-outline-success')
                                    : boutonAction($m['id'], 'suspendre', '<i class="bi bi-slash-circle"></i> Suspendre', 'btn-outline-warning', 'Suspendre ce compte ? La personne ne pourra plus se connecter.') ?>
                                <?= boutonAction($m['id'], 'supprimer', '<i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer @' . e($m['pseudo']) . '</span>', 'btn-outline-danger', 'Supprimer définitivement ce compte et ses commandes, avis et dons ?') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
