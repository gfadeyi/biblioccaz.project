<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!function_exists('imagewebp')) { fwrite(STDERR, "Extension GD avec WebP requise.\n"); exit(1); }
$options = getopt('', ['racine:', 'sortie:']);
$projet = realpath($options['racine'] ?? dirname(__DIR__));
if (!$projet || !is_dir($projet . '/img/livres')) { exit(1); }
$racine = $projet . '/img/';
$dossier = $options['sortie'] ?? $racine . 'livres/miniatures';
if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) { exit(1); }
$bilan = ['creees' => 0, 'existantes' => 0, 'sans_gain' => 0, 'erreurs' => [], 'originaux_octets' => 0, 'miniatures_octets' => 0];
$fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine . 'livres', FilesystemIterator::SKIP_DOTS));
foreach ($fichiers as $fichier) {
    $source = str_replace('\\', '/', $fichier->getPathname());
    if (!$fichier->isFile() || $fichier->isLink() || str_contains($source, '/miniatures/') || !in_array(strtolower($fichier->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true)) { continue; }
    $relatif = substr($source, strlen(str_replace('\\', '/', $racine)));
    $cible = $dossier . '/' . hash('sha256', $relatif) . '.webp';
    if (is_file($cible) && filemtime($cible) >= filemtime($source)) {
        $bilan['existantes']++;
        $bilan['originaux_octets'] += filesize($source);
        $bilan['miniatures_octets'] += filesize($cible);
        continue;
    }
    $info = @getimagesize($source);
    if (!$info || $info[0] * $info[1] > 20000000) { $bilan['erreurs'][] = $relatif; continue; }
    $image = match ($info[2]) { IMAGETYPE_JPEG => @imagecreatefromjpeg($source), IMAGETYPE_PNG => @imagecreatefrompng($source), IMAGETYPE_WEBP => @imagecreatefromwebp($source), default => false };
    if (!$image) { $bilan['erreurs'][] = $relatif; continue; }
    $rapport = min(1, 360 / $info[0], 260 / $info[1]);
    $largeur = max(1, (int) round($info[0] * $rapport));
    $hauteur = max(1, (int) round($info[1] * $rapport));
    $petite = imagecreatetruecolor($largeur, $hauteur);
    imagealphablending($petite, false);
    imagesavealpha($petite, true);
    imagefilledrectangle($petite, 0, 0, $largeur - 1, $hauteur - 1, imagecolorallocatealpha($petite, 0, 0, 0, 127));
    imagecopyresampled($petite, $image, 0, 0, 0, 0, $largeur, $hauteur, $info[0], $info[1]);
    $temporaire = $cible . '.tmp-' . bin2hex(random_bytes(4));
    $cree = imagewebp($petite, $temporaire, 82);
    imagedestroy($image);
    imagedestroy($petite);
    if (!$cree || !is_file($temporaire)) { $bilan['erreurs'][] = $relatif; continue; }
    if (filesize($temporaire) >= filesize($source)) { unlink($temporaire); $bilan['sans_gain']++; continue; }
    if (!chmod($temporaire, 0644) || !rename($temporaire, $cible)) { $bilan['erreurs'][] = $relatif; continue; }
    $bilan['creees']++;
    $bilan['originaux_octets'] += filesize($source);
    $bilan['miniatures_octets'] += filesize($cible);
}
echo json_encode($bilan, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($bilan['erreurs'] ? 1 : 0);
