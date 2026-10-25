<?php
require_once __DIR__ . '/_init.php';
if (isset($_SESSION['user_id'])) {
    insertLog('DECONNEXION', "Déconnexion du back-office");
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php?deconnecte=1');
exit();
