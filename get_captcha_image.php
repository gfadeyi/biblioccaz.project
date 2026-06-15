<?php

header('Content-Type: application/json');

$dir = 'img/captcha/';
$images = [];


if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $images[] = $dir . $file;
        }
    }
}

if (empty($images)) {
    $images = ['img/fond_puzzle_2.jpg']; 
}

$chosenImage = $images[array_rand($images)];


echo json_encode(['image' => $chosenImage]);
exit();