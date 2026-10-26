<?php
require_once 'config.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$erreur = "";
$succes = isset($_GET['enregistre']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $cp = trim($_POST['cp'] ?? '');

    if (!verifierCsrf()) {
        $erreur = "La session a expiré, merci de renvoyer le formulaire.";
    } elseif (!$nom || !$prenom || !$ville || !$cp) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez entrer une adresse email valide.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? AND id <> ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $erreur = "Cette adresse email est déjà utilisée par un autre compte.";
        } else {
            $stmt = $pdo->prepare("UPDATE user SET nom = ?, prenom = ?, email = ?, ville = ?, cp = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $email, $ville, $cp, $user_id]);
            header('Location: modifier_profil.php?enregistre=1');
            exit();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($erreur) {
    $user = array_merge($user, ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'ville' => $ville, 'cp' => $cp]);
}

include 'header.php';
?>

<main class="page-formulaire">
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
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" autocomplete="given-name" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom *</label>
                    <input type="text" id="nom" name="nom" class="form-control" autocomplete="family-name" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label for="email" class="form-label">Adresse email *</label>
                    <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
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
</main>

<?php include 'footer.php'; ?>
