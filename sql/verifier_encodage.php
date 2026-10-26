<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../secrets.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$colonnesTexte = $pdo->query("
    SELECT table_name, column_name FROM information_schema.columns
    WHERE table_schema = DATABASE() AND data_type IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext')")->fetchAll(PDO::FETCH_NUM);

$marqueursCasses = ['├', '┬', 'Ã', 'Â', 'â€', '╗', '®'];
$conditions = implode(' OR ', array_fill(0, count($marqueursCasses), 'BINARY `%s` LIKE ?'));
$motifs = array_map(fn($marqueur) => '%' . $marqueur . '%', $marqueursCasses);
$problemes = 0;

foreach ($colonnesTexte as [$table, $colonne]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE " . str_replace('%s', $colonne, $conditions));
    $stmt->execute($motifs);
    $nombre = (int) $stmt->fetchColumn();
    if ($nombre > 0) {
        echo "$table.$colonne : $nombre ligne(s) suspecte(s)\n";
        $problemes += $nombre;
    }
}

echo $problemes === 0 ? "Encodage correct : aucun caractère cassé.\n" : "À vérifier : $problemes ligne(s) suspecte(s).\n";
exit($problemes === 0 ? 0 : 1);
