<?php
// Export PDF de la liste des membres (réservé à l'admin : contient des données personnelles).
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    rediriger('utilisateurs.php', 'danger', "Dompdf est introuvable : lancez « composer install » à la racine du projet.");
}
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$membres = $pdo->query("SELECT pseudo, nom, prenom, email, role, date_inscription FROM user ORDER BY date_inscription DESC")->fetchAll();
insertLog('ADMIN', "Export PDF de la liste des membres (" . count($membres) . " comptes)");

$lignes = '';
foreach ($membres as $m) {
    $lignes .= '<tr>'
        . '<td>' . e($m['pseudo']) . '</td>'
        . '<td>' . e(trim($m['prenom'] . ' ' . $m['nom'])) . '</td>'
        . '<td>' . e($m['email']) . '</td>'
        . '<td>' . e(ROLES_LIBELLES[$m['role']] ?? $m['role']) . '</td>'
        . '<td>' . date('d/m/Y', strtotime($m['date_inscription'])) . '</td>'
        . '</tr>';
}

$html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>
    body { font-family: Helvetica, Arial, sans-serif; color: #333; font-size: 11px; }
    h1 { text-align: center; color: #274e13; margin-bottom: 4px; }
    .date { text-align: center; color: #666; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background: #d9ead3; }
    tr:nth-child(even) td { background: #f7f9f6; }
</style></head><body>
    <h1>Membres BIBLIOccaz</h1>
    <div class="date">' . count($membres) . ' comptes · exporté le ' . date('d/m/Y à H:i') . ' · document confidentiel</div>
    <table>
        <thead><tr><th>Pseudo</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Inscription</th></tr></thead>
        <tbody>' . $lignes . '</tbody>
    </table>
</body></html>';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', false);   // aucune ressource externe nécessaire

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('membres_biblioccaz_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
