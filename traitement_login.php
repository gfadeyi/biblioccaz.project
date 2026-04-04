<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pseudo = $_POST['pseudo'] ?? '';
$mdp = $_POST['mdp'] ?? '';

try {
    $sql = "SELECT * FROM user WHERE pseudo = :p AND mot_de_passe = :m";
    $query = $pdo->prepare($sql);
    $query->execute([
        'p' => $pseudo,
        'm' => $mdp
    ]);

    $user = $query->fetch();

    if ($user) {
        $_SESSION['admin'] = true;
        $_SESSION['pseudo'] = $user['pseudo'];
        header('Location: admin.php');
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
} catch (Exception $e) {
    die("Erreur technique : " . $e->getMessage());
}