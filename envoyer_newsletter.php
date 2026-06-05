<?php
// 1. Forcer l'affichage des erreurs en cas de problème de code
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; 

try {
    $requete = $pdo->query("SELECT email FROM newsletter_subscribers");
    $abonnes = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

if (empty($abonnes)) {
    die("Aucun abonné trouvé dans la base de données. Inscris-toi d'abord sur le site !");
}

$sujet = "📚 Les nouveautés littéraires du mois sur BIBLIOccaz !";

// Configuration des headers propre et simple (comme dans ton inscription.php)
        $headers = "From:biblioccaz.noreply@gmail.com\r\n";
        $headers .= "Reply-To: biblioccaz.noreply@gmail.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$message_html = '
<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; background-color: #D9EAD3; padding: 20px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <tr>
            <td style="background-color: #274E13; padding: 40px 20px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 28px; letter-spacing: 1px; font-family: \'Francois One\', Arial, sans-serif;">BIBLIOccaz</h1>
                <p style="color: #B6D7A8; margin: 8px 0 0 0; font-size: 14px;">Donnez une seconde vie aux livres</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; color: #333333; line-height: 1.6; font-size: 16px;">
                <h2 style="color: #274E13; margin-top: 0; font-size: 20px;">Bonjour passionné(e) de lecture,</h2>
                <p>De nouvelles pépites d\'occasion viennent d\'arriver sur notre plateforme ! Des romans, des mangas et des livres d\'études vous attendent à prix mini.</p>
                <p>Ne ratez pas l\'occasion de compléter votre bibliothèque avant qu\'ils ne soient réservés.</p>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 35px auto;">
                    <tr>
                        <td style="background-color: #8FCE00; border-radius: 4px; text-align: center;">
                            <a href="http://91.134.143.156/" target="_blank" style="padding: 12px 30px; color: #ffffff; text-decoration: none; font-weight: bold; display: inline-block; font-size: 16px;">
                                Découvrir les nouveautés
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="font-size: 14px; color: #7f8c8d; margin-top: 40px;">À très vite sur notre site,<br>L\'équipe BIBLIOccaz</p>
            </td>
        </tr>
        <tr>
            <td style="background-color: #B6D7A8; padding: 20px; text-align: center; font-size: 12px; color: #274E13;">
                <p style="margin: 0; font-weight: bold;">Vous recevez ce mail car vous aimez les livres d\'occasion.</p>
                <p style="margin: 8px 0 0 0;">
                    <a href="http://91.134.143.156/desinscription.php" style="color: #274E13; text-decoration: underline; font-weight: bold;">Se désabonner de la newsletter</a>
                </p>
            </td>
        </tr>
    </table>
</div>';

$compteur = 0;
foreach ($abonnes as $abonne) {
    // Utilisation directe de la chaîne $headers
    if (mail($abonne['email'], $sujet, $message_html, $headers)) {
        echo "Envoyé avec succès à : " . htmlspecialchars($abonne['email']) . "<br>";
        $compteur++;
    } else {
        echo "Échec de l'envoi pour : " . htmlspecialchars($abonne['email']) . "<br>";
    }
}

echo "<br><strong>Opération terminée !</strong> Total de newsletters envoyées : " . $compteur;
?>