<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$id = (int) ($_GET['id'] ?? 0);
$evt = chargerEvenement($pdo, $id);
if (!$evt) {
    rediriger('evenements.php', 'warning', "Cet événement n'existe pas.");
}
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    rediriger("evenement_participants.php?id=$id", 'danger', "Dompdf est introuvable : lancez « composer install » à la racine du projet.");
}
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$stmt = $pdo->prepare("
    SELECT i.*, u.pseudo, u.nom, u.prenom, u.email
    FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
    WHERE i.id_evenement = ? AND i.statut IN ('inscrit', 'attente')
    ORDER BY i.statut = 'attente', u.nom, u.prenom, u.pseudo");
$stmt->execute([$id]);
$personnes = $stmt->fetchAll();
insertLog('EVENEMENT', "Export PDF des participants de l'événement #$id");

$lignes = '';
$n = 0;
foreach ($personnes as $p) {
    if ($p['statut'] !== 'inscrit') {
        continue;
    }
    $n++;
    $lignes .= '<tr><td>' . $n . '</td>'
        . '<td>' . e(trim($p['nom'] . ' ' . $p['prenom'])) . '</td>'
        . '<td>' . e($p['pseudo']) . '</td>'
        . '<td>' . e($p['email']) . '</td>'
        . '<td class="code">' . e($p['code_court']) . '</td>'
        . '<td class="case">' . ($p['present'] ? '&#10004;' : '') . '</td></tr>';
}
$attente = array_filter($personnes, fn($p) => $p['statut'] === 'attente');
$lignesAttente = '';
foreach (array_values($attente) as $rang => $p) {
    $lignesAttente .= '<tr><td>' . ($rang + 1) . '</td><td>' . e(trim($p['nom'] . ' ' . $p['prenom'])) . '</td><td>' . e($p['pseudo']) . '</td><td>' . e($p['email']) . '</td></tr>';
}

$html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>
    body { font-family: Helvetica, Arial, sans-serif; color: #333; font-size: 11px; }
    h1 { color: #274e13; margin: 0 0 4px; font-size: 18px; }
    h2 { font-size: 13px; margin: 18px 0 6px; }
    .infos { color: #555; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    th { background: #d9ead3; }
    .code { font-family: monospace; letter-spacing: 2px; }
    .case { width: 50px; text-align: center; }
</style></head><body>
    <h1>' . e($evt['titre']) . '</h1>
    <div class="infos">' . e(ucfirst(horaireEvenement($evt))) . ' · ' . e($evt['lieu']) . '<br>'
    . $n . ' inscrit' . ($n > 1 ? 's' : '') . ' sur ' . (int) $evt['places_max'] . ' places · document confidentiel, exporté le ' . date('d/m/Y à H:i') . '</div>
    <table>
        <thead><tr><th>#</th><th>Nom</th><th>Pseudo</th><th>Email</th><th>Billet</th><th class="case">Présent</th></tr></thead>
        <tbody>' . ($lignes ?: '<tr><td colspan="6">Aucun inscrit.</td></tr>') . '</tbody>
    </table>'
    . ($lignesAttente ? '<h2>Liste d\'attente</h2><table><thead><tr><th>#</th><th>Nom</th><th>Pseudo</th><th>Email</th></tr></thead><tbody>' . $lignesAttente . '</tbody></table>' : '')
    . '</body></html>';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('participants_evenement_' . $id . '.pdf', ['Attachment' => true]);
