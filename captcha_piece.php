<?php
// Renvoie l'image d'UNE pièce du puzzle, découpée côté serveur.
// Le navigateur ne peut donc pas deviner la solution à partir du code de la page.

if (session_status() === PHP_SESSION_NONE) { session_start(); }

const LARGEUR = 300;   // taille du puzzle complet
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

// Redimensionne l'image en 300x150 (recadrage centré, sans déformation)
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

// Découpe la pièce demandée
$index = $captcha['pieces'][$id];
$pw = LARGEUR / COLS;
$ph = HAUTEUR / ROWS;
$piece = imagecreatetruecolor($pw, $ph);
imagecopy($piece, $puzzle, 0, 0, ($index % COLS) * $pw, intdiv($index, COLS) * $ph, $pw, $ph);

header('Content-Type: image/png');
header('Cache-Control: no-store');
imagepng($piece);
