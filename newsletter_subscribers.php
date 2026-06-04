<?php
require_once 'config.php'; 

if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
    
    try {
        // 2. On utilise $pdo (comme sur votre capture) pour vérifier les doublons
        $check = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() == 0) {
            // 3. Si l'email n'existe pas, on l'ajoute
            $req = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $req->execute([$email]);
        }
        
        header('Location: index.php?newsletter=success');
        exit();

    } catch (PDOException $e) {

        die("Erreur lors de l'inscription : " . $e->getMessage());
    }
} else {
    // Si accès direct à la page sans formulaire
    header('Location: index.php');
    exit();
}
?>