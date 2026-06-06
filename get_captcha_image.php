<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$dir = 'img/captcha/';
$images = [];

// On scanne le dossier
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $images[] = $dir . $file;
        }
    }
}

// Si le dossier est vide ou inaccessible, on met tes images par défaut
if (empty($images)) {
    $images = ['img/fond_puzzle_2.jpg', 'img/fond_puzzle_3.jpg', 'img/fond_puzzle_4.jpg'];
}

// Choix aléatoire
$randomImage = $images[array_rand($images)];

// On génère les positions secrètes
$targetX = rand(60, 210); 
$targetY = rand(20, 80);  

// On stocke TOUT en session (X et Y)
$_SESSION['captcha_target_x'] = $targetX;
$_SESSION['captcha_target_y'] = $targetY;

// On renvoie la réponse proprement
header('Content-Type: application/json');
echo json_encode([
    'image' => $randomImage,
    'targetY' => $targetY
]);
exit();