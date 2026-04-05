<?php 
include 'config.php';

$pseudo_saisi = "";
$etape_mdp = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pseudo'])) {
    $pseudo_saisi = trim($_POST['pseudo']);
    
    if (isset($_POST['action_suivant'])) {
        $stmt = $pdo->prepare("SELECT pseudo FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo_saisi, $pseudo_saisi]);
        $user = $stmt->fetch();

        if ($user) {
            $pseudo_saisi = $user['pseudo'];
            $etape_mdp = true;
        } else {
            header("Location: inscription.php?email=" . urlencode($pseudo_saisi));
            exit();
        }
    }
}

$titre = "Se connecter / S'inscrire";
include 'header.php'; 
?>

<div class="container py-5">
    <div class="auth-container shadow-sm mx-auto">
        <h2 class="fw-bold mb-3" style="color: var(--bibli-green) !important;">Se connecter / S'inscrire</h2>
        
        <form action="login.php" method="POST">
            <?php if (!$etape_mdp): ?>
                <p class="small mb-4" style="color: var(--text-color) !important; opacity: 0.7;">Renseignez votre identifiant ou e-mail.</p>
                <div class="mb-3">
                    <input type="text" name="pseudo" class="form-control text-center" placeholder="E-mail ou Pseudo" style="border-radius: 30px; padding: 12px; background-color: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color);" required>
                </div>
                <button type="submit" name="action_suivant" class="btn w-100" style="background-color: var(--bibli-green) !important; color: white; font-weight: bold; padding: 12px; border-radius: 30px; border: none;">
                    Suivant
                </button>
            <?php else: ?>
                <p class="small mb-4" style="color: var(--text-color) !important; opacity: 0.7;">Bonjour <strong><?= htmlspecialchars($pseudo_saisi) ?></strong>, entrez votre mot de passe.</p>
                <input type="hidden" name="pseudo" value="<?= htmlspecialchars($pseudo_saisi) ?>">
                <div class="mb-3">
                    <input type="password" name="mdp" class="form-control text-center" placeholder="Mot de passe" style="border-radius: 30px; padding: 12px; background-color: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color);" required autofocus>
                </div>
                <button type="submit" formaction="traitement_login.php" class="btn w-100" style="background-color: var(--bibli-green) !important; color: white; font-weight: bold; padding: 12px; border-radius: 30px; border: none;">
                    Se connecter
                </button>
                <div class="mt-3 text-center">
                    <a href="login.php" style="font-size: 0.8rem; color: var(--text-color); opacity: 0.6; text-decoration: none;">Ce n'est pas vous ?</a>
                </div>
            <?php endif; ?>
        </form>

        <div class="d-flex align-items-center my-4">
            <hr class="flex-grow-1" style="opacity: 0.2;">
            <span class="mx-3 small" style="opacity: 0.7;">Ou</span>
            <hr class="flex-grow-1" style="opacity: 0.2;">
        </div>

        <div class="d-grid gap-2">
            <button type="button" class="btn border" style="border-radius: 30px; color: var(--text-color); background-color: var(--input-bg);"><i class="bi bi-google text-danger me-2"></i> Avec Google</button>
            <button type="button" class="btn border" style="border-radius: 30px; color: var(--text-color); background-color: var(--input-bg);"><i class="bi bi-facebook text-primary me-2"></i> Avec Facebook</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>