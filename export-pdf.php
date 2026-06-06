<?php

require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


try {
    $host = 'localhost'; 
    $dbname = 'biblioccaz'; 
    $username = 'admin_biblio';
    $password = 'Esgi_2026_Biblio!';
    
 
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    
    $result = $conn->query("SELECT nom, prenom, email  FROM user ORDER BY id DESC");
    
    if (!$result) {
        die("Erreur de requête : " . $conn->error);
    }
    
    $inscrits = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();

} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}


$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des inscrits</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; font-size: 12px; }
        h1 { text-align: center; color: #0056b3; margin-bottom: 20px; }
        .date-export { text-align: right; margin-bottom: 20px; font-style: italic; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; color: #000; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>

    <div class="date-export">Exporté le : ' . date('d/m/Y H:i') . '</div>
    <h1>Liste des Inscrits sur le Site</h1>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Adresse Email</th>
                <th>Date d\'inscription</th>
            </tr>
        </thead>
        <tbody>';


foreach ($inscrits as $row) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['nom']) . '</td>
        <td>' . htmlspecialchars($row['prenom']) . '</td>
        <td>' . htmlspecialchars($row['email']) . '</td>
        <td>' . date('d/m/Y', strtotime($row['date_inscription'])) . '</td>
    </tr>';
}

$html .= '
        </tbody>
    </table>

</body>
</html>';


$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);


$dompdf->setPaper('A4', 'portrait');

$dompdf->render();


$dompdf->stream("liste_inscrits_" . date('Y-m-d') . ".pdf", ["Attachment" => true]);