<?php
$titre = "Inscription";
require_once 'config.php';
include 'header.php';
 
$erreur = "";
$succes = "";
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo         = trim($_POST['pseudo'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $mot_de_passe   = $_POST['mot_de_passe'] ?? '';
    $confirmation   = $_POST['confirmation'] ?? '';
    $nom            = trim($_POST['nom'] ?? '');
    $prenom         = trim($_POST['prenom'] ?? '');
    $connexion_auto = isset($_POST['connexion_auto']);
 
    if (!$pseudo || !$email || !$mot_de_passe || !$nom || !$prenom) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $erreur = "Ce pseudo ou cet email est déjà utilisé.";
        } else {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $code_verif = rand(100000, 999999);

            $stmt = $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, code_verification, est_verifie) VALUES (?, ?, ?, ?, ?, 'client', ?, 1)");
            $stmt->execute([$pseudo, $email, $hash, $nom, $prenom, $code_verif]);
            $id = $pdo->lastInsertId();
            
            if ($connexion_auto) {
                $_SESSION['user_id'] = $id;
                $_SESSION['pseudo']  = $pseudo;
                $_SESSION['role']    = 'client';
                $_SESSION['nom']     = $nom;
                $_SESSION['prenom']  = $prenom;

                insertLog($pdo, 'INSCRIPTION', "Nouvel utilisateur inscrit et connecté : " . $pseudo, $id);
                
                header("Location: index.php");
                exit();
            } else {
                insertLog($pdo, 'INSCRIPTION', "Nouvel utilisateur inscrit : " . $pseudo, $id);
                header("Location: login.php?inscription=success");
                exit();
            }
        }
    }
}

$token = bin2hex(random_bytes(32));


$sql = "INSERT INTO users (email, password, validation_token, is_verified) 
        VALUES (:email, :password, :token, 0)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'email'    => $email_utilisateur,
    'password' => $password_hashe, 
    'token'    => $token
]);


?>
 
<div class="container my-5" style="max-width: 550px;">
    <div class="card shadow" style="border: 2px solid #b6d7a8; border-radius: 15px;">
        <div class="card-body p-4">
 
            <h2 class="fw-bold text-bibli-green text-center mb-4">
                <i class="bi bi-person-plus"></i> Créer un compte
            </h2>
 
            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>
 
            <form method="POST">
 
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Prénom *</label>
                        <input type="text" name="prenom" class="form-control"
                               placeholder="Ex: Marie"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="nom" class="form-control"
                               placeholder="Ex: Dupont"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Pseudo *</label>
                        <input type="text" name="pseudo" class="form-control"
                               placeholder="Ex: marie_dupont"
                               value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Email *</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="Ex: marie@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mot de passe *</label>
                        <input type="password" name="mot_de_passe" class="form-control"
                               placeholder="Min. 6 caractères" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Confirmation *</label>
                        <input type="password" name="confirmation" class="form-control"
                               placeholder="Répéter le mot de passe" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="connexion_auto" id="connexion_auto" checked>
                            <label class="form-check-label" for="connexion_auto">
                                 Me connecter automatiquement après l'inscription
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-bibli w-100 py-2">
                            <i class="bi bi-person-check"></i> Créer mon compte
                        </button>
                    </div>
                </div>
 
            </form>
 
            <hr style="color: #93c47d;" class="my-3">
            <p class="text-center mb-0" style="font-size: 0.9rem;">
                Déjà un compte ?
                <a href="login.php" style="color: #274e13; font-weight: bold;">Se connecter</a>
            </p>
 
        </div>
    </div>
</div>
 
<?php include 'footer.php'; ?>