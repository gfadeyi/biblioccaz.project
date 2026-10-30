<?php

require_once __DIR__ . '/sessions_lib.php';
demarrerSession();

const LARGEUR = 300;
const HAUTEUR = 150;
const COLS = 3;
const ROWS = 2;

$id = $_GET['id'] ?? '';
$captcha = $_SESSION['captcha'] ?? null;

if (!$captcha || !isset($captcha['pieces'][$id]) || !extension_loaded('gd')) {
    http_response_code(404);
    exit();
}

$source = @imagecreatefromstring(file_get_contents($captcha['image']));
if (!$source) {
    http_response_code(500);
    exit();
}

$sw = imagesx($source);
$sh = imagesy($source);
$ratio = max(LARGEUR / $sw, HAUTEUR / $sh);
$cw = (int) round(LARGEUR / $ratio);
$ch = (int) round(HAUTEUR / $ratio);
$cx = (int) (($sw - $cw) / 2);
$cy = (int) (($sh - $ch) / 2);

$puzzle = imagecreatetruecolor(LARGEUR, HAUTEUR);
imagefill($puzzle, 0, 0, imagecolorallocate($puzzle, 255, 255, 255));
imagecopyresampled($puzzle, $source, 0, 0, $cx, $cy, LARGEUR, HAUTEUR, $cw, $ch);

$index = $captcha['pieces'][$id];
$pw = LARGEUR / COLS;
$ph = HAUTEUR / ROWS;
$piece = imagecreatetruecolor($pw, $ph);
imagecopy($piece, $puzzle, 0, 0, ($index % COLS) * $pw, intdiv($index, COLS) * $ph, $pw, $ph);

header('Content-Type: image/png');
header('Cache-Control: no-store');
imagepng($piece);
