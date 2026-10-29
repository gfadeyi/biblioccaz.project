<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../recuperation_mdp_lib.php';
exigerDroit('admin');

$erreur = '';
$saisie = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => '', 'role' => 'client'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('membre_creer.php');
    foreach ($saisie as $champ => $valeurParDefaut) {
        $saisie[$champ] = is_string($_POST[$champ] ?? null) ? trim($_POST[$champ]) : '';
    }
    $saisie['email'] = mb_strtolower($saisie['email']);

    if ($saisie['prenom'] === '' || $saisie['nom'] === '' || $saisie['pseudo'] === '' || $saisie['email'] === '') {
        $erreur = 'Merci de remplir tous les champs obligatoires.';
    } elseif (mb_strlen($saisie['prenom']) > 100 || mb_strlen($saisie['nom']) > 100) {
        $erreur = 'Le prénom et le nom ne doivent pas dépasser 100 caractères.';
    } elseif (!preg_match('/\A[\p{L}\p{N}._-]{3,50}\z/u', $saisie['pseudo'])) {
        $erreur = 'Le pseudo doit contenir de 3 à 50 caractères : lettres, chiffres, point, tiret ou tiret bas.';
    } elseif (mb_strlen($saisie['email']) > 150 || !filter_var($saisie['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif (!in_array($saisie['role'], ROLES_ATTRIBUABLES, true)) {
        $erreur = 'Rôle inconnu.';
    } else {
        $stmt = $pdo->prepare('SELECT pseudo = ? AS meme_pseudo FROM user WHERE pseudo = ? OR email = ? LIMIT 1');
        $stmt->execute([$saisie['pseudo'], $saisie['pseudo'], $saisie['email']]);
        $existant = $stmt->fetch();
        if ($existant) {
            $erreur = $existant['meme_pseudo'] ? 'Ce pseudo est déjà utilisé.' : 'Un compte existe déjà avec cette adresse email.';
        }
    }

    if ($erreur === '') {
        try {
            $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie) VALUES (?, ?, ?, ?, ?, ?, 'actif', 0, 0)")
                ->execute([$saisie['pseudo'], $saisie['email'], password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $saisie['nom'], $saisie['prenom'], $saisie['role']]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] !== 1062) {
                throw $e;
            }
            $erreur = 'Ce pseudo ou cette adresse email vient d\'être utilisé par un autre compte.';
        }
    }

    if ($erreur === '') {
        $idUser = (int) $pdo->lastInsertId();
        $jeton = preparerInvitation($pdo, $idUser);
        $libelleRole = mb_strtolower(ROLES_LIBELLES[$saisie['role']]);
        insertLog('ADMIN', "Compte @{$saisie['pseudo']} créé depuis le back-office (rôle : {$saisie['role']})");

        $mailEnvoye = false;
        require_once __DIR__ . '/../mailer.php';
        try {
            $mail = creerMailer();
            $mail->addAddress($saisie['email'], $saisie['prenom'] . ' ' . $saisie['nom']);
            habillerMail($mail, 'Votre compte BIBLIOccaz est prêt',
                "Bonjour {$saisie['prenom']},\n\nL'équipe BIBLIOccaz vient de vous créer un compte (rôle : $libelleRole), avec le pseudo {$saisie['pseudo']}.\n\nPour l'activer, choisissez votre mot de passe avec le bouton ci-dessous. Ce lien est valable " . INVITATION_DUREE_HEURES . " heures. Passé ce délai, utilisez « Mot de passe oublié » sur la page de connexion.",
                SITE_URL . '/modifier_mdp.php?token=' . $jeton, 'Choisir mon mot de passe');
            $mail->send();
            $mailEnvoye = true;
        } catch (Throwable $e) {
            insertLog('ERREUR', "Mail d'invitation non envoyé à @{$saisie['pseudo']} : " . $e->getMessage());
        }

        rediriger('utilisateurs.php?q=' . rawurlencode($saisie['pseudo']), $mailEnvoye ? 'success' : 'warning', $mailEnvoye
            ? "Le compte @{$saisie['pseudo']} est créé. Une invitation a été envoyée à {$saisie['email']} pour choisir son mot de passe."
            : "Le compte @{$saisie['pseudo']} est créé, mais l'invitation n'a pas pu partir. La personne peut utiliser « Mot de passe oublié » avec son adresse email.");
    }
}

$titrePage = 'Créer un compte';
$pageActive = 'utilisateurs';
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted mb-0">Le compte est actif tout de suite. La personne reçoit un mail pour choisir elle-même son mot de passe ; aucun mot de passe ne passe par l'équipe.</p>
    <a href="utilisateurs.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour aux membres</a>
</div>

<?php if ($erreur): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i><?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST" class="carte p-4" style="max-width: 720px;">
    <?= champCsrf() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="prenom" class="form-label small fw-bold">Prénom *</label>
            <input type="text" id="prenom" name="prenom" class="form-control" maxlength="100" autocomplete="off" value="<?= e($saisie['prenom']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="nom" class="form-label small fw-bold">Nom *</label>
            <input type="text" id="nom" name="nom" class="form-control" maxlength="100" autocomplete="off" value="<?= e($saisie['nom']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="pseudo" class="form-label small fw-bold">Pseudo *</label>
            <input type="text" id="pseudo" name="pseudo" class="form-control" minlength="3" maxlength="50" autocomplete="off" value="<?= e($saisie['pseudo']) ?>" aria-describedby="aide-pseudo" required>
            <div id="aide-pseudo" class="form-text">Lettres, chiffres, point, tiret ou tiret bas.</div>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label small fw-bold">Adresse email *</label>
            <input type="email" id="email" name="email" class="form-control" maxlength="150" autocomplete="off" value="<?= e($saisie['email']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="role" class="form-label small fw-bold">Rôle *</label>
            <select id="role" name="role" class="form-select" aria-describedby="aide-role">
                <?php foreach (ROLES_ATTRIBUABLES as $role): ?>
                    <option value="<?= $role ?>" <?= $saisie['role'] === $role ? 'selected' : '' ?>><?= ROLES_LIBELLES[$role] ?></option>
                <?php endforeach; ?>
            </select>
            <div id="aide-role" class="form-text">Un compte administrateur ne peut pas être créé depuis cette page.</div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-success"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Créer le compte et envoyer l'invitation</button>
        <a href="utilisateurs.php" class="btn btn-link">Annuler</a>
    </div>
</form>

<?php include __DIR__ . '/_bas.php'; ?>
