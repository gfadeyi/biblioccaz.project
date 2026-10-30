<?php
require_once __DIR__ . '/_init.php';

if (estEquipe()) {
    rediriger('index.php');
}

$erreur = '';
$compteSuspendu = finSessionSuspendue();
$finSession = messageFinSession();
if (($_GET['connexion'] ?? '') === 'indisponible') { $erreur = 'Connexion temporairement indisponible. Merci de réessayer.'; }
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = $_POST['mdp'] ?? '';

    $captcha = $_SESSION['captcha'] ?? null;
    unset($_SESSION['captcha']);

    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$identifiant, $identifiant]);
    $user = $stmt->fetch();

    if (!$captcha || empty($captcha['valide']) || time() - $captcha['valide_le'] > 300) {
        $erreur = "Veuillez compléter le puzzle de sécurité.";
    } elseif (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
        $erreur = "Identifiants incorrects.";
        insertLog('SECURITE', "Échec de connexion au back-office pour « $identifiant »");
    } elseif (!in_array($user['role'], ROLES_EQUIPE, true)) {
        $erreur = "Ce compte n'a pas accès au back-office.";
        insertLog('SECURITE', "Tentative d'accès au back-office par un compte non autorisé (user #{$user['id']})");
    } elseif ((int) $user['email_verifie'] === 0) {
        $erreur = "Confirmez d'abord votre adresse email.";
    } elseif (in_array($user['statut'], ['refuse_temporaire', 'refuse_definitif', 'banni'], true)) {
        $erreur = "Ce compte est suspendu.";
    } else {
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
    <link rel="stylesheet" href="admin.css">
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
            <div class="mb-3">
                <label for="identifiant" class="form-label small fw-bold">Email ou pseudo</label>
                <input type="text" id="identifiant" name="identifiant" class="form-control" value="<?= e($identifiant) ?>" required autocomplete="username" autofocus>
            </div>
            <div class="mb-3">
                <label for="mdp" class="form-label small fw-bold">Mot de passe</label>
                <input type="password" id="mdp" name="mdp" class="form-control" required autocomplete="current-password">
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#captchaModal" id="captchaTriggerBtn">
                    <i class="bi bi-puzzle"></i> Vérification de sécurité
                </button>
                <input type="hidden" name="captcha_token" id="captchaToken">
            </div>

            <button type="submit" class="btn btn-vert w-100 py-2 fw-bold">Se connecter</button>
        </form>

        <div class="d-flex justify-content-between mt-4 small">
            <a href="../recuparation_mdp.php" class="text-muted">Mot de passe oublié ?</a>
            <a href="../index.php" class="text-muted"><i class="bi bi-arrow-left"></i> Retour au site</a>
        </div>
    </div>
</div>

<div class="modal fade" id="captchaModal" tabindex="-1" aria-labelledby="captchaTitre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="captchaTitre">Reconstituez l'image</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-muted small mb-3">Cliquez sur deux cases pour échanger leurs places.</p>
                <div id="captchaGrid"></div>
                <p id="captchaMessage" class="mt-3 small fw-bold mb-0" style="min-height: 20px;"></p>
            </div>
        </div>
    </div>
</div>

<script src="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.bundle.min.js', '../') ?>"></script>
<script src="../javascript/captcha_puzzle.js?v=<?= filemtime(__DIR__ . '/../javascript/captcha_puzzle.js') ?>"></script>
<script>initCaptchaPuzzle('', { image: 'puzzle_image.php', piece: 'puzzle_piece.php', verifier: 'puzzle_verifier.php' });</script>
</body>
</html>
