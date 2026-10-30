<?php
require_once __DIR__ . '/_init.php';

const INSTALLATION_JETON_LONGUEUR_MIN = 32;
const INSTALLATION_MDP_LONGUEUR_MIN = 12;

header('X-Robots-Tag: noindex, nofollow');

$jetonAttendu = defined('JETON_INSTALLATION') && is_string(JETON_INSTALLATION) ? trim(JETON_INSTALLATION) : '';
if (mb_strlen($jetonAttendu) < INSTALLATION_JETON_LONGUEUR_MIN) {
    http_response_code(404);
    exit('Page introuvable.');
}

function administrateursUtilisables(PDO $pdo, bool $verrouiller = false): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'admin' AND mot_de_passe LIKE '$%'" . ($verrouiller ? ' FOR UPDATE' : ''))->fetchColumn();
}

function administrateurSansMotDePasse(PDO $pdo): ?int
{
    $id = $pdo->query("SELECT id FROM user WHERE role = 'admin' AND mot_de_passe NOT LIKE '$%' ORDER BY id LIMIT 1 FOR UPDATE")->fetchColumn();
    return $id === false ? null : (int) $id;
}

$installationTerminee = administrateursUtilisables($pdo) > 0;
$compteCree = !empty($_SESSION['installation_admin_creee']);
$erreur = '';
$saisie = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => ''];

if (!$installationTerminee && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($saisie as $champ => $valeurParDefaut) {
        $saisie[$champ] = is_string($_POST[$champ] ?? null) ? trim($_POST[$champ]) : '';
    }
    $saisie['email'] = mb_strtolower($saisie['email']);
    $jetonSaisi = is_string($_POST['jeton'] ?? null) ? trim($_POST['jeton']) : '';
    $mdp = is_string($_POST['mdp'] ?? null) ? $_POST['mdp'] : '';
    $confirmation = is_string($_POST['mdp_confirmation'] ?? null) ? $_POST['mdp_confirmation'] : '';

    if (!verifierCsrf()) {
        $erreur = 'Session expirée ou requête invalide, merci de réessayer.';
    } elseif (!hash_equals($jetonAttendu, $jetonSaisi)) {
        $erreur = "Le jeton d'installation est incorrect.";
        insertLog('SECURITE', "Jeton d'installation incorrect sur la page de création du premier administrateur");
    } elseif ($saisie['prenom'] === '' || $saisie['nom'] === '' || $saisie['pseudo'] === '' || $saisie['email'] === '') {
        $erreur = 'Merci de remplir tous les champs.';
    } elseif (mb_strlen($saisie['prenom']) > 100 || mb_strlen($saisie['nom']) > 100) {
        $erreur = 'Le prénom et le nom ne doivent pas dépasser 100 caractères.';
    } elseif (!preg_match('/\A[\p{L}\p{N}._-]{3,50}\z/u', $saisie['pseudo'])) {
        $erreur = 'Le pseudo doit contenir de 3 à 50 caractères : lettres, chiffres, point, tiret ou tiret bas.';
    } elseif (mb_strlen($saisie['email']) > 150 || !filter_var($saisie['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif (mb_strlen($mdp) < INSTALLATION_MDP_LONGUEUR_MIN) {
        $erreur = 'Le mot de passe doit contenir au moins ' . INSTALLATION_MDP_LONGUEUR_MIN . ' caractères.';
    } elseif (!hash_equals($mdp, $confirmation)) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        $pdo->beginTransaction();
        try {
            if (administrateursUtilisables($pdo, true) > 0) {
                $pdo->rollBack();
                $installationTerminee = true;
            } else {
                $idAReprendre = administrateurSansMotDePasse($pdo);
                $stmt = $pdo->prepare('SELECT pseudo = ? AS meme_pseudo FROM user WHERE (pseudo = ? OR email = ?) AND id <> ? LIMIT 1');
                $stmt->execute([$saisie['pseudo'], $saisie['pseudo'], $saisie['email'], $idAReprendre ?? 0]);
                $existant = $stmt->fetch();
                if ($existant) {
                    $pdo->rollBack();
                    $erreur = $existant['meme_pseudo'] ? 'Ce pseudo est déjà utilisé.' : 'Un compte existe déjà avec cette adresse email.';
                } else {
                    $valeurs = [$saisie['pseudo'], $saisie['email'], password_hash($mdp, PASSWORD_DEFAULT), $saisie['nom'], $saisie['prenom']];
                    if ($idAReprendre === null) {
                        $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie) VALUES (?, ?, ?, ?, ?, 'admin', 'actif', 1, 1)")
                            ->execute($valeurs);
                    } else {
                        $pdo->prepare("UPDATE user SET pseudo = ?, email = ?, mot_de_passe = ?, nom = ?, prenom = ?, statut = 'actif', email_verifie = 1, est_verifie = 1, email_token = NULL WHERE id = ? AND role = 'admin'")
                            ->execute([...$valeurs, $idAReprendre]);
                    }
                    $pdo->commit();
                    insertLog('ADMIN', "Premier compte administrateur @{$saisie['pseudo']} créé par la page d'installation");
                    $_SESSION['installation_admin_creee'] = true;
                    rediriger('installation.php');
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            $erreur = 'Ce pseudo ou cette adresse email vient d\'être utilisé par un autre compte.';
        }
    }
}

if ($installationTerminee) {
    unset($_SESSION['installation_admin_creee']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Installation · Back-office BIBLIOccaz</title>
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-icons-1.11.0/bootstrap-icons.min.css', '../') ?>">
    <link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>">
</head>
<body class="admin">
<main class="connexion-admin">
    <div class="carte shadow">
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock fs-1" style="color: var(--vert);" aria-hidden="true"></i>
            <h1 class="h4 mt-2 mb-0 titre-connexion-admin">Premier administrateur</h1>
            <p class="text-muted small mb-0">Installation de BIBLIOccaz</p>
        </div>

        <?php if ($installationTerminee): ?>
            <?php if ($compteCree): ?>
                <div class="alert alert-success small" role="status"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Le compte administrateur est créé.</div>
            <?php else: ?>
                <div class="alert alert-info small" role="status">Un compte administrateur existe déjà : cette page ne peut plus en créer.</div>
            <?php endif; ?>
            <p class="small">Dernière étape : videz <strong>JETON_INSTALLATION</strong> dans <strong>secrets.php</strong> sur le serveur. La page sera alors introuvable.</p>
            <a href="login.php" class="btn btn-vert w-100 py-2 fw-bold">Aller à la connexion</a>
        <?php else: ?>
            <?php if ($erreur): ?>
                <div class="alert alert-danger small" role="alert"><?= e($erreur) ?></div>
            <?php endif; ?>
            <p class="small text-muted">Cette page ne sert qu'une fois : elle se ferme dès qu'un administrateur existe.</p>
            <form method="POST" action="installation.php" autocomplete="off">
                <?= champCsrf() ?>
                <div class="mb-3">
                    <label for="jeton" class="form-label small fw-bold">Jeton d'installation</label>
                    <input type="password" id="jeton" name="jeton" class="form-control" required autocomplete="off" aria-describedby="aide-jeton">
                    <div id="aide-jeton" class="form-text">La valeur de <strong>JETON_INSTALLATION</strong> dans <strong>secrets.php</strong>.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="prenom" class="form-label small fw-bold">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" maxlength="100" value="<?= e($saisie['prenom']) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="nom" class="form-label small fw-bold">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control" maxlength="100" value="<?= e($saisie['nom']) ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="pseudo" class="form-label small fw-bold">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" class="form-control" minlength="3" maxlength="50" value="<?= e($saisie['pseudo']) ?>" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold">Adresse email</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150" value="<?= e($saisie['email']) ?>" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="mdp" class="form-label small fw-bold">Mot de passe</label>
                    <input type="password" id="mdp" name="mdp" class="form-control" minlength="<?= INSTALLATION_MDP_LONGUEUR_MIN ?>" required autocomplete="new-password" aria-describedby="aide-mdp">
                    <div id="aide-mdp" class="form-text">Au moins <?= INSTALLATION_MDP_LONGUEUR_MIN ?> caractères, différent de tous vos autres mots de passe.</div>
                </div>
                <div class="mb-4">
                    <label for="mdp_confirmation" class="form-label small fw-bold">Confirmer le mot de passe</label>
                    <input type="password" id="mdp_confirmation" name="mdp_confirmation" class="form-control" minlength="<?= INSTALLATION_MDP_LONGUEUR_MIN ?>" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-vert w-100 py-2 fw-bold">Créer le compte administrateur</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
