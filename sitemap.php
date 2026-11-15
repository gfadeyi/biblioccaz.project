<?php
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/sessions_lib.php';
require_once __DIR__ . '/erreurs_lib.php';
installerGestionErreurs();
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('X-Content-Type-Options: nosniff');
if (!modeProduction()) { http_response_code(404); exit; }
$pdoSitemap = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
$requete = $pdoSitemap->prepare('SELECT id_livre FROM livre WHERE is_valide = 1 ORDER BY id_livre LIMIT 45000');
$requete->execute();
$ids = $requete->fetchAll(PDO::FETCH_COLUMN);
$pages = ['', 'qui-sommes-nous', 'catalogue.php', 'bd_mangas.php', 'jeunesse.php', 'roman.php', 'scolaire.php', 'petits_prix.php', 'dons.php', 'evenements.php', 'auteurs.php'];
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach (array_merge($pages, array_map(fn($id) => 'detail_livre.php?id=' . (int) $id, $ids)) as $page) {
    $xml .= '<url><loc>' . htmlspecialchars(rtrim(SITE_URL, '/') . '/' . $page, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
}
echo $xml . '</urlset>';
