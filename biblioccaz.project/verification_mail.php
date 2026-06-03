<?php
require_once 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT id FROM user WHERE code_verification = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE user SET est_verifie = 1, code_verification = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        header("Location: login.php?verif=success");
    } else {
        echo "Lien invalide ou expiré.";
    }
}
?>