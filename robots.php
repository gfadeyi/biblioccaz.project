<?php
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/sessions_lib.php';
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300');
echo "User-agent: *\n";
if (!modeProduction()) { echo "Disallow: /\n"; exit; }
$base = rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/') . '/';
foreach (['admin/', 'secure_data/', 'sauvegardes/', 'sql/', 'vendor/', 'outils/', 'docs/'] as $dossier) { echo 'Disallow: ' . $base . $dossier . "\n"; }
echo 'Sitemap: ' . rtrim(SITE_URL, '/') . "/sitemap.php\n";
