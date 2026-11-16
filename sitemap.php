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
$listeSitemap = function (string $sql, string $modele) use ($pdoSitemap): array {
    try {
        $lecture = $pdoSitemap->prepare($sql);
        $lecture->execute();
        return array_map(fn($valeur) => sprintf($modele, rawurlencode((string) $valeur)), $lecture->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $erreur) {
        return [];
    }
};
$pages = array_merge(
    ['', 'qui-sommes-nous', 'catalogue', 'nouveautes', 'meilleures_ventes', 'petits_prix', 'derniere-chance', 'plus_dons', 'plus_emprunts', 'bd_mangas', 'jeunesse', 'roman', 'scolaire', 'dons', 'evenements', 'auteurs', 'mentions_legales', 'confidentialite'],
    $listeSitemap("SELECT id_evenement FROM evenement WHERE statut = 'publie' AND date_fin >= NOW() ORDER BY date_debut LIMIT 1000", 'evenement?id=%s'),
    $listeSitemap('SELECT DISTINCT ls.id_serie FROM livre_serie ls INNER JOIN livre l ON l.id_livre = ls.id_livre WHERE l.is_valide = 1 ORDER BY ls.id_serie LIMIT 3000', 'serie?id=%s'),
    $listeSitemap("SELECT f.slug FROM fiche_auteur f INNER JOIN user u ON u.id = f.id_user WHERE f.est_masquee = 0 AND u.role = 'auteur' AND u.statut NOT IN ('refuse_temporaire', 'refuse_definitif') AND f.slug REGEXP '^[a-z0-9-]{1,150}$' ORDER BY f.slug LIMIT 1000", 'auteur?a=%s'),
    $listeSitemap('SELECT id_livre FROM livre WHERE is_valide = 1 ORDER BY id_livre LIMIT 40000', 'livre?id=%s')
);
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($pages as $page) {
    $xml .= '<url><loc>' . htmlspecialchars(rtrim(SITE_URL, '/') . '/' . $page, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
}
echo $xml . '</urlset>';
