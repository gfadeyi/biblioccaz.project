<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../connexion_equipe_lib.php';

if (estEquipe()) {
    rediriger('index.php');
}

$erreur = '';
$compteSuspendu = finSessionSuspendue();
$finSession = messageFinSession();
if (($_GET['connexion'] ?? '') === 'indisponible') { $erreur = 'Connexion temporairement indisponible. Merci de réessayer.'; }
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = is_string($_POST['identifiant'] ?? null) ? trim($_POST['identifiant']) : '';
    $mdp = is_string($_POST['mdp'] ?? null) ? $_POST['mdp'] : '';
    $user = null;
    if (!is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
        $erreur = 'Session expirée, actualisez la page.';
    } else {
        try {
            $user = authentifierEquipeLimitee($pdo, $identifiant, $mdp, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        } catch (DomainException $erreurConnexion) {
            $erreur = $erreurConnexion->getMessage();
            insertLog('SECURITE', 'Échec ou limitation de connexion au back-office');
        } catch (Throwable $erreurConnexion) {
            error_log('Connexion équipe : ' . $erreurConnexion->getMessage());
            $erreur = 'Connexion temporairement indisponible. Réessayez plus tard.';
        }
    }
    if ($user) {
        if ($user['role'] === 'moderateur' && $user['statut'] === 'valide_moderateur') {
            $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?")->execute([$user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['connexion_time'] = time();
        try {
            enregistrerSessionUtilisateur($pdo, $user);
        } catch (RuntimeException $e) {
            insertLog('ERREUR', 'Connexion au back-office temporairement indisponible');
            rediriger('login.php?connexion=indisponible');
        }

        insertLog('CONNEXION', "Connexion au back-office");
        $retour = $_SESSION['admin_retour'] ?? '';
        unset($_SESSION['admin_retour']);
        rediriger(preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%-]*)?$/', $retour) ? $retour : 'index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion · Back-office BIBLIOccaz</title>
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-icons-1.11.0/bootstrap-icons.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('admin/admin.css', '../') ?>">
</head>
<body class="admin">
<div class="connexion-admin">
    <div class="carte shadow">
        <div class="text-center mb-4">
            <i class="bi bi-book-half fs-1" style="color: var(--vert);"></i>
            <h1 class="h4 mt-2 mb-0 titre-connexion-admin">Back-office BIBLIOccaz</h1>
            <p class="text-muted small mb-0">Espace réservé à l'équipe</p>
        </div>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning small" role="status">Vous avez été déconnecté après une période d'inactivité.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deconnecte'])): ?>
            <div class="alert alert-success small" role="status">Vous êtes déconnecté.</div>
        <?php endif; ?>
        <?php if ($finSession): ?>
            <div class="alert <?= $compteSuspendu ? 'alert-danger' : 'alert-info' ?> small" role="<?= $compteSuspendu ? 'alert' : 'status' ?>"><?= e($finSession) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?= champCsrf() ?>
            <div class="mb-3">
                <label for="identifiant" class="form-label small fw-bold">Email ou pseudo</label>
                <input type="text" id="identifiant" name="identifiant" class="form-control" value="<?= e($identifiant) ?>" required autocomplete="username" autofocus>
            </div>
            <div class="mb-3">
                <label for="mdp" class="form-label small fw-bold">Mot de passe</label>
                <input type="password" id="mdp" name="mdp" class="form-control" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-vert w-100 py-2 fw-bold">Se connecter</button>
        </form>

        <div class="d-flex justify-content-between mt-4 small">
            <a href="../recuparation_mdp.php" class="text-muted">Mot de passe oublié ?</a>
            <a href="../index.php" class="text-muted"><i class="bi bi-arrow-left"></i> Retour au site</a>
        </div>
    </div>
</div>

<script src="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.bundle.min.js', '../') ?>"></script>
</body>
</html>
