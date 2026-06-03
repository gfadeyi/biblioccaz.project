<?php
require_once 'config.php';
include 'header.php';

echo '<div class="container my-5 text-center" style="max-width: 600px;">';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id FROM user WHERE email_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE user SET email_verifie = 1, email_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        header("Location: login.php?verif=success");
        exit();
    } else {
        echo "Lien invalide ou expiré.";
    }
} else {
    echo "Aucun token fourni.";
}

echo '</div>';
include 'footer.php';
?>