<?php
// Génère un nouveau puzzle captcha.
// La solution (quelle pièce va où) reste sur le serveur, dans la session :
// le navigateur ne reçoit que des identifiants aléatoires de pièces.

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
header('Cache-Control: no-store');

const CAPTCHA_COLS = 3;
const CAPTCHA_ROWS = 2;

$dir = __DIR__ . '/img/captcha/';
$images = [];
if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $images[] = $dir . $file;
        }
    }
}
if (empty($images)) {
    $images = [__DIR__ . '/img/Biblioccaz_Logo_New.png'];
}

$nbPieces = CAPTCHA_COLS * CAPTCHA_ROWS;

// identifiant aléatoire -> position correcte de la pièce
$pieces = [];
for ($i = 0; $i < $nbPieces; $i++) {
    $pieces[bin2hex(random_bytes(8))] = $i;
}

// Ordre d'affichage mélangé (jamais déjà résolu)
$ordre = array_keys($pieces);
do {
    shuffle($ordre);
    $resolu = true;
    foreach ($ordre as $pos => $id) {
        if ($pieces[$id] !== $pos) { $resolu = false; break; }
    }
} while ($resolu);

$_SESSION['captcha'] = [
    'image'      => $images[array_rand($images)],
    'pieces'     => $pieces,
    'tentatives' => 0,
    'valide'     => false,
    'valide_le'  => 0,
];

echo json_encode(['pieces' => $ordre]);
