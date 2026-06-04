<?php

$bdd = new PDO('mysql:host=localhost;dbname=biblioccaz;charset=utf8mb4', 'root', ''); 

if (isset($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
    
    $req = $bdd->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $req->execute([$email]);
    
    echo "Merci ! Vous êtes bien inscrit à la newsletter.";
}
?>

<form method="POST" action="newsletter_subscribers.php">
    <input type="email" name="email" placeholder="Votre adresse email" required>
    <button type="submit">S'inscrire</button>
</form>