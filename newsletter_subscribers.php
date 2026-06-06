<?php
require_once 'config.php'; 


if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
    
    try {

        $check = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() == 0) {

            $req = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $req->execute([$email]);
        }
        
        header('Location: index.php?newsletter=success');
        exit();

    } catch (PDOException $e) {
        die("Erreur lors de l'inscription : " . $e->getMessage());
    }
} else {
    header('Location: index.php');
    exit();
}
?>