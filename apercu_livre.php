<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/series_lib.php';
require_once __DIR__ . '/avis_lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$requete = $pdo->prepare('SELECT * FROM livre WHERE id_livre = ? AND is_valide = 1');
$requete->execute([$id]);
$livre = $requete->fetch();
if (!$livre) { http_response_code(404); echo json_encode(['message' => 'Livre introuvable.']); exit(); }
$note = noteDuLivre($pdo, $id);
$etats = [];
foreach (etatsMagasinDuLivre($pdo, $id) as $etat => $offre) {
    $etats[] = ['etat' => $etat, 'libelle' => $etat === 'use' ? 'Correct' : ($etat === 'tres bon' ? 'Très bon' : ucfirst($etat)), 'id_exemplaire' => $offre ? (int) $offre['id_exemplaire'] : null, 'prix' => $offre ? (float) $offre['prix'] : null, 'disponible' => $offre && $offre['libre'] > 0, 'stock_bas' => $offre ? stockEstBas((int) $offre['libre']) : false];
}
echo json_encode(['id' => $id, 'titre' => $livre['titre'], 'auteur' => $livre['auteur'], 'couverture' => urlImage($livre['couverture']), 'note' => (float) $note['moyenne'], 'avis' => (int) $note['nb'], 'prix_neuf' => prixNeufReference($pdo, $livre), 'etats' => $etats, 'lien' => 'detail_livre.php?id=' . $id], JSON_THROW_ON_ERROR);
