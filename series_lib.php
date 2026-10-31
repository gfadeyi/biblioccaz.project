<?php
require_once __DIR__ . '/livres_lib.php';

function serieDuLivre(PDO $pdo, int $idLivre): ?array
{
    $requete = $pdo->prepare('SELECT s.*, ls.numero_tome FROM livre_serie ls INNER JOIN serie s ON s.id_serie = ls.id_serie WHERE ls.id_livre = ?');
    $requete->execute([$idLivre]);
    return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
}

function tomesDeSerie(PDO $pdo, int $idSerie): array
{
    $requete = $pdo->prepare("SELECT l.*, ls.numero_tome, av.moyenne AS note_moyenne, COALESCE(av.nb, 0) AS nb_avis FROM livre_serie ls INNER JOIN livre l ON l.id_livre = ls.id_livre LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre WHERE ls.id_serie = ? AND l.is_valide = 1 ORDER BY ls.numero_tome, l.id_livre");
    $requete->execute([$idSerie]);
    return $requete->fetchAll(PDO::FETCH_ASSOC);
}

function offresMagasinDuLivre(PDO $pdo, int $idLivre): array
{
    return offresMagasinDesLivres($pdo, [$idLivre]);
}

function offresMagasinDesLivres(PDO $pdo, array $identifiants): array
{
    $identifiants = array_values(array_unique(array_filter(array_map('intval', $identifiants), fn($id) => $id > 0)));
    if (!$identifiants) { return []; }
    $marqueurs = implode(',', array_fill(0, count($identifiants), '?'));
    $requete = $pdo->prepare("SELECT e.id_exemplaire, e.id_livre, e.etat, e.prix, e.quantite, e.is_disponible, l.is_valide, CASE WHEN e.is_disponible = 1 AND l.is_valide = 1 THEN GREATEST(0, e.quantite - COALESCE((SELECT SUM(r.quantite) FROM panier_reservation r WHERE r.id_exemplaire = e.id_exemplaire AND r.date_expiration > NOW() AND r.id_panier <> ?), 0)) ELSE 0 END AS libre FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre WHERE e.id_livre IN ($marqueurs) AND e.id_proprietaire IS NULL AND e.type_offre = 'vente' ORDER BY e.prix, e.id_exemplaire");
    $requete->execute([$_SESSION['panier_identifiant'] ?? '', ...$identifiants]);
    $offres = $requete->fetchAll(PDO::FETCH_ASSOC);
    foreach ($offres as &$offre) { $offre['libre'] = (int) $offre['libre']; }
    return $offres;
}

function selectionSerieDisponible(PDO $pdo, int $idSerie): array
{
    $tomes = tomesDeSerie($pdo, $idSerie);
    $offres = [];
    $total = 0.0;
    $totalNeuf = 0.0;
    $referencesConnues = true;
    $offresParLivre = [];
    foreach (offresMagasinDesLivres($pdo, array_column($tomes, 'id_livre')) as $offre) {
        if ($offre['libre'] > 0) { $offresParLivre[(int) $offre['id_livre']][] = $offre; }
    }
    foreach ($tomes as $tome) {
        $disponibles = $offresParLivre[(int) $tome['id_livre']] ?? [];
        if (!$disponibles) { continue; }
        $offres[] = (int) $disponibles[0]['id_exemplaire'];
        $total += (float) $disponibles[0]['prix'];
        $reference = (float) ($tome['prix_neuf'] ?? 0);
        if ($reference <= 0) { $referencesConnues = false; }
        $totalNeuf += $reference;
    }
    return ['tomes' => $tomes, 'offres' => $offres, 'total' => $total, 'total_neuf' => $referencesConnues ? $totalNeuf : null, 'indisponibles' => count($tomes) - count($offres)];
}

function numeroTomeLisible(string|float $numero): string
{
    return rtrim(rtrim(number_format((float) $numero, 2, '.', ''), '0'), '.');
}

function etatsMagasinDuLivre(PDO $pdo, int $idLivre): array
{
    $etats = ['use' => null, 'bon' => null, 'tres bon' => null, 'neuf' => null];
    foreach (offresMagasinDuLivre($pdo, $idLivre) as $offre) {
        if (!array_key_exists($offre['etat'], $etats)) { continue; }
        $ancien = $etats[$offre['etat']];
        if ($ancien === null || ($ancien['libre'] === 0 && $offre['libre'] > 0)) { $etats[$offre['etat']] = $offre; }
    }
    return $etats;
}

function prixNeufReference(PDO $pdo, array $livre): ?float
{
    if ((float) ($livre['prix_neuf'] ?? 0) > 0) { return (float) $livre['prix_neuf']; }
    $requete = $pdo->prepare("SELECT MIN(prix) FROM exemplaire WHERE id_livre = ? AND id_proprietaire IS NULL AND type_offre = 'vente' AND etat = 'neuf'");
    $requete->execute([(int) $livre['id_livre']]);
    $prix = $requete->fetchColumn();
    return $prix !== null && (float) $prix > 0 ? (float) $prix : null;
}
