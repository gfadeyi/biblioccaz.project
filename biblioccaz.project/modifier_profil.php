<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $ville = $_POST['ville'];
    $cp = $_POST['cp'];

    $stmt = $pdo->prepare("UPDATE user SET nom = ?, prenom = ?, email = ?, ville = ?, cp = ? WHERE id = ?");
    if ($stmt->execute([$nom, $prenom, $email, $ville, $cp, $user_id])) {
        $message = "success";
    } else {
        $message = "error";
    }
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="d-flex align-items-center mb-4">
                <a href="profil.php" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">Modifier mes infos</h2>
            </div>

            <?php if ($message === "success"): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="bi bi-check-circle me-2"></i> Vos informations ont été mises à jour !
                </div>
            <?php elseif ($message === "error"): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i> Une erreur est survenue.
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <form action="modifier_profil.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Adresse E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Code Postal</label>
                            <input type="text" name="cp" class="form-control" value="<?= htmlspecialchars($user['cp']) ?>" required>
                        </div>
                        <div class="col-md-8 mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($user['ville']) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill" style="background-color: #274e13; border: none;">
                        ENREGISTRER LES MODIFICATIONS
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center">
                <a href="modifier_mdp.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-lock me-1"></i> Souhaitez-vous changer votre mot de passe ?
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>