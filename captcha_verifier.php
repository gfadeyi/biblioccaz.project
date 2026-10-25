<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
header('Cache-Control: no-store');

const MAX_TENTATIVES = 40;

$captcha = $_SESSION['captcha'] ?? null;
if (!$captcha || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'expire' => true]);
    exit();
}

$_SESSION['captcha']['tentatives']++;
if ($_SESSION['captcha']['tentatives'] > MAX_TENTATIVES) {
    unset($_SESSION['captcha']);
    echo json_encode(['ok' => false, 'expire' => true]);
    exit();
}

$ordre = explode(',', $_POST['ordre'] ?? '');
$ok = count($ordre) === count($captcha['pieces']);
if ($ok) {
    foreach ($ordre as $position => $id) {
        if (!isset($captcha['pieces'][$id]) || $captcha['pieces'][$id] !== $position) {
            $ok = false;
            break;
        }
    }
}

if ($ok) {
    $_SESSION['captcha']['valide'] = true;
    $_SESSION['captcha']['valide_le'] = time();
}

echo json_encode(['ok' => $ok]);
