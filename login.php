<?php
require_once 'config.php';
require_once 'mot_de_passe_lib.php';

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0");
if (isset($_GET['retour'])) {
    if (!memoriserRetourConnexion((string) $_GET['retour'])) {
        oublierRetourConnexion();
    }
} elseif (!isset($_GET['error']) && !isset($_SESSION['retour_apres_connexion'])) {
    unset($_SESSION['action_apres_connexion']);
}
$livreEnAttente = titreLivreEnAttente($pdo);
include 'header.php';

$error = "";
$success = "";
$compteSuspendu = finSessionSuspendue();
$finSession = messageFinSession();

if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') { $error = "Identifiants incorrects."; }
    elseif ($_GET['error'] == 'limite') { $error = 'Trop de tentatives. Merci de réessayer dans 15 minutes.'; }
    elseif ($_GET['error'] == 'csrf') { $error = 'La session du formulaire a expiré. Rechargez la page.'; }
    elseif ($_GET['error'] == 'captcha') { $error = "Veuillez compléter le puzzle."; }
    elseif ($_GET['error'] == 'not_verified') { $error = "Veuillez confirmer votre email avant de vous connecter."; }
    elseif ($_GET['error'] == 'en_attente_moderateur') { $error = "Votre demande d'inscription en tant que modérateur est en cours d'examen."; }
    elseif ($_GET['error'] == 'refuse_temporaire') { $error = "Votre candidature n'a pas été retenue pour l'instant."; }
    elseif ($_GET['error'] == 'refuse_definitif') { $error = "Votre demande a été rejetée définitivement."; }
    elseif ($_GET['error'] == 'technique') { $error = 'Connexion temporairement indisponible. Merci de réessayer.'; }
    elseif ($_GET['error'] == 'google_annule') { $error = "La connexion avec Google a été annulée."; }
    elseif ($_GET['error'] == 'google_expire') { $error = "La demande de connexion avec Google a expiré. Merci de recommencer."; }
    elseif ($_GET['error'] == 'google_email') { $error = "Votre adresse Google doit être vérifiée par Google pour vous connecter."; }
    elseif ($_GET['error'] == 'google_equipe') { $error = "Les comptes de l'équipe se connectent avec leur mot de passe."; }
    elseif ($_GET['error'] == 'google_autre_compte') { $error = "Ce compte BIBLIOccaz est déjà lié à un autre compte Google."; }
    elseif ($_GET['error'] == 'google_essais') { $error = "Trop d'essais : la liaison avec Google est annulée. Connectez-vous avec votre mot de passe ou réinitialisez-le."; }
}

$identifiantSaisi = '';
$identifiantsIncorrects = ($_GET['error'] ?? '') === '1';
if ($identifiantsIncorrects && is_string($_SESSION['identifiant_connexion'] ?? null)) {
    $identifiantSaisi = mb_substr($_SESSION['identifiant_connexion'], 0, 150);
    if (filter_var($identifiantSaisi, FILTER_VALIDATE_EMAIL)) { $_SESSION['email_inscription'] = $identifiantSaisi; }
}
unset($_SESSION['identifiant_connexion']);
$identifiantMemorise = identifiantMemorise();
if ($identifiantSaisi === '') { $identifiantSaisi = $identifiantMemorise; }
$lienInscription = $identifiantsIncorrects && filter_var($identifiantSaisi, FILTER_VALIDATE_EMAIL) ? 'inscription?depuis=connexion' : 'inscription';

if (isset($_GET['verif'])) { $success = "Compte validé ! Vous pouvez vous connecter."; }
if (isset($_GET['mdp'])) { $success = "Mot de passe modifié ! Vous pouvez vous connecter avec le nouveau."; }
if (isset($_GET['inscription'])) { $success = "Compte créé ! Un mail de validation vient de vous être envoyé : cliquez sur son lien avant de vous connecter."; }
?>

<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><?= iconeDessin('lecteur') ?></div>
        <h1>Se connecter</h1>
        <p class="carte-formulaire__intro">Retrouvez vos favoris, vos points et vos commandes.</p>
        <?php if ($livreEnAttente !== null): ?>
            <div class="alert alert-success small" role="status" data-action-en-attente><i class="bi bi-heart" aria-hidden="true"></i> Connectez-vous : « <?= htmlspecialchars($livreEnAttente) ?> » sera ajouté à vos favoris et vous reviendrez sur la page que vous consultiez.</div>
        <?php elseif (isset($_SESSION['retour_apres_connexion']) && $_SESSION['retour_apres_connexion'] !== 'achat'): ?>
            <div class="alert alert-info small" role="status" data-retour-connexion>Une fois connecté, vous reviendrez sur la page que vous consultiez.</div>
        <?php endif; ?>

        <?php if (isset($_GET['timeout']) || (isset($_GET['msg']) && $_GET['msg'] === 'inactive')): ?>
            <div class="alert alert-info small" role="status"><strong>Déconnexion automatique</strong> après 15 minutes d'inactivité.</div>
        <?php endif; ?>
        <?php if ($finSession): ?><div class="alert <?= $compteSuspendu ? 'alert-danger' : 'alert-info' ?> small" role="<?= $compteSuspendu ? 'alert' : 'status' ?>"><?= htmlspecialchars($finSession) ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger small" role="alert">
                <?= htmlspecialchars($error) ?>
                <?php if ($identifiantsIncorrects): ?>
                    <a class="alerte-inscription" href="<?= $lienInscription ?>"><i class="bi bi-person-plus" aria-hidden="true"></i> Pas encore de compte ? Créer un compte</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success small" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form action="traitement_login" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <div class="mb-3">
                <label for="identifiant" class="form-label">Email ou pseudo</label>
                <input type="text" id="identifiant" name="pseudo" class="form-control" autocomplete="username" value="<?= htmlspecialchars($identifiantSaisi) ?>" required>
            </div>
            <div class="mb-2">
                <?= champMotDePasse('mot-de-passe', 'mdp', 'Mot de passe', 'current-password') ?>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                <div class="form-check rester-connecte">
                    <input class="form-check-input" type="checkbox" id="memoriser-identifiant" name="memoriser_identifiant" value="1" aria-describedby="aide-memoriser-identifiant"<?= $identifiantMemorise !== '' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="memoriser-identifiant">Mémoriser mon identifiant</label>
                    <div class="form-text" id="aide-memoriser-identifiant">Seul votre identifiant est retenu sur cet appareil (<?= IDENTIFIANT_MEMORISE_JOURS ?> jours), jamais votre mot de passe. Vous restez déconnecté après 15 minutes sans activité.</div>
                </div>
                <a href="recuparation_mdp" class="carte-formulaire__lien-discret">Mot de passe oublié ?</a>
            </div>

            <?php include 'partie_captcha.php'; ?>

            <button type="submit" class="bouton bouton-principal">Se connecter</button>
        </form>
        <?php include 'partie_bouton_google.php'; ?>
        <div class="carte-formulaire__encart encart-creation-compte">
            <p><strong>Nouveau sur BIBLIOccaz ?</strong> Créez votre compte pour acheter, donner, emprunter et cumuler des points.</p>
            <a href="<?= $lienInscription ?>" class="bouton bouton-contour"><i class="bi bi-person-plus" aria-hidden="true"></i> Créer un compte</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
