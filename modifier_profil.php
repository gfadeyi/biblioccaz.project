<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'compte_lib.php';
require_once 'categories_lib.php';
require_once 'recommandations_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$erreur = "";
$erreurPreferences = "";
$succes = isset($_GET['enregistre']);
$succesPreferences = isset($_GET['preferences']);
$action = $_POST['action'] ?? 'infos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preferences') {
    if (!verifierCsrf()) {
        $erreurPreferences = "La session a expiré, merci de renvoyer le formulaire.";
    } else {
        try {
            enregistrerPreferences($pdo, $user_id, idsChoisis($_POST['categories'] ?? []), idsChoisis($_POST['themes'] ?? []));
            insertLog('PROFIL', "Préférences de lecture mises à jour");
            header('Location: modifier_profil.php?preferences=1#preferences');
            exit();
        } catch (PDOException $e) {
            $erreurPreferences = "Les préférences de lecture ne sont pas encore disponibles.";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $telephone = trim((string) ($_POST['telephone'] ?? ''));

    if (!verifierCsrf()) {
        $erreur = "La session a expiré, merci de renvoyer le formulaire.";
    } elseif (!$nom || !$prenom || !$ville || !$cp) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!telephoneValide($telephone)) {
        $erreur = "Le numéro de téléphone n'est pas valide.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE user SET nom = ?, prenom = ?, ville = ?, cp = ?, telephone = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $ville, $cp, $telephone !== '' ? $telephone : null, $user_id]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1054) { throw $e; }
            $stmt = $pdo->prepare("UPDATE user SET nom = ?, prenom = ?, ville = ?, cp = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $ville, $cp, $user_id]);
        }
        header('Location: modifier_profil.php?enregistre=1');
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($erreur) {
    $user = array_merge($user, ['nom' => $nom, 'prenom' => $prenom, 'ville' => $ville, 'cp' => $cp, 'telephone' => $telephone]);
}

$afficherPreferences = in_array($user['role'], ROLES_SUGGESTIONS, true);
$preferences = $afficherPreferences ? preferencesLecteur($pdo, $user_id) : ['categories' => [], 'tags' => []];
if ($erreurPreferences) {
    $preferences = ['categories' => idsChoisis($_POST['categories'] ?? []), 'tags' => idsChoisis($_POST['themes'] ?? [])];
}
$arbrePreferences = $afficherPreferences ? arbreCategories($pdo) : [];
$themesPreferences = $afficherPreferences ? (tagsParFamille(listerTags($pdo))['theme'] ?? []) : [];

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--large">
        <div class="carte-formulaire__icone"><i class="bi bi-person-gear" aria-hidden="true"></i></div>
        <h1>Modifier mes infos</h1>
        <p class="carte-formulaire__intro">Ces informations servent à vos commandes et à vous écrire.</p>

        <?php if ($succes): ?>
            <div class="alert alert-success small" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> Vos informations ont été mises à jour.</div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form action="modifier_profil.php" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="infos">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" autocomplete="given-name" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom *</label>
                    <input type="text" id="nom" name="nom" class="form-control" autocomplete="family-name" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                </div>
                <div class="col-md-7">
                    <span class="form-label d-block">Adresse email actuelle</span>
                    <p class="text-break mb-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <a href="modifier_email.php" class="small">Changer mon adresse email</a>
                </div>
                <div class="col-md-5">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" class="form-control" autocomplete="tel" maxlength="20" aria-describedby="aide-telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                    <div id="aide-telephone" class="form-text">Facultatif, jamais affiché aux autres membres.</div>
                </div>
                <div class="col-md-4">
                    <label for="cp" class="form-label">Code postal *</label>
                    <input type="text" id="cp" name="cp" class="form-control" autocomplete="postal-code" inputmode="numeric" value="<?= htmlspecialchars($user['cp'] ?? '') ?>" required>
                </div>
                <div class="col-md-8">
                    <label for="ville" class="form-label">Ville *</label>
                    <input type="text" id="ville" name="ville" class="form-control" autocomplete="address-level2" value="<?= htmlspecialchars($user['ville'] ?? '') ?>" required>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="bouton bouton-principal">Enregistrer les modifications</button>
                </div>
            </div>
        </form>
        <p class="carte-formulaire__pied">
            <a href="profil.php">Retour à mon compte</a> · <a href="modifier_mdp.php">Changer mon mot de passe</a>
        </p>
    </div>

    <?php if ($afficherPreferences): ?>
        <section class="carte-formulaire carte-formulaire--large preferences-lecture" id="preferences" aria-labelledby="titre-preferences">
            <h2 id="titre-preferences" class="preferences-lecture__titre"><?= iconeDessin('lunettes') ?> Mes préférences de lecture</h2>
            <p class="preferences-lecture__intro">Facultatif : cochez ce que vous aimez lire pour recevoir des <a href="suggestions.php">suggestions</a> qui vous ressemblent. Vous pouvez tout décocher à tout moment.</p>

            <?php if ($succesPreferences): ?>
                <div class="alert alert-success small" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> Vos préférences de lecture sont enregistrées.</div>
            <?php endif; ?>
            <?php if ($erreurPreferences): ?>
                <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreurPreferences) ?></div>
            <?php endif; ?>

            <form action="modifier_profil.php#preferences" method="POST">
                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                <input type="hidden" name="action" value="preferences">
                <?php foreach ($arbrePreferences as $universPreference): ?>
                    <fieldset class="preferences-lecture__groupe">
                        <legend><?= htmlspecialchars($universPreference['nom']) ?></legend>
                        <div class="puces-tags">
                            <?php foreach (array_merge([$universPreference + ['nom_affiche' => 'Tout l\'univers']], $universPreference['categories']) as $categoriePreference): ?>
                                <?php $idCase = 'preference-categorie-' . (int) $categoriePreference['id_categorie']; ?>
                                <input type="checkbox" class="btn-check" name="categories[]" value="<?= (int) $categoriePreference['id_categorie'] ?>" id="<?= $idCase ?>" <?= in_array((int) $categoriePreference['id_categorie'], $preferences['categories'], true) ? 'checked' : '' ?>>
                                <label class="puce-tag" for="<?= $idCase ?>"><?= htmlspecialchars($categoriePreference['nom_affiche'] ?? $categoriePreference['nom']) ?><?php if (isset($categoriePreference['nom_affiche'])): ?><span class="visually-hidden"> <?= htmlspecialchars($universPreference['nom']) ?></span><?php endif; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
                <?php if ($themesPreferences): ?>
                    <fieldset class="preferences-lecture__groupe">
                        <legend>Thèmes &amp; ambiances</legend>
                        <div class="puces-tags">
                            <?php foreach ($themesPreferences as $themePreference): ?>
                                <?php $idCase = 'preference-theme-' . (int) $themePreference['id_tag']; ?>
                                <input type="checkbox" class="btn-check" name="themes[]" value="<?= (int) $themePreference['id_tag'] ?>" id="<?= $idCase ?>" <?= in_array((int) $themePreference['id_tag'], $preferences['tags'], true) ? 'checked' : '' ?>>
                                <label class="puce-tag" for="<?= $idCase ?>"><?= htmlspecialchars($themePreference['nom']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>
                <button type="submit" class="bouton bouton-principal mt-2">Enregistrer mes préférences</button>
            </form>
        </section>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
