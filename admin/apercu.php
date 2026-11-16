<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../apercu_lib.php';
if (!estEquipe()) {
    header('Location: login.php');
    exit();
}
if (!ouvrirApercuBackOffice((int) $_SESSION['user_id'])) {
    rediriger('index.php', 'danger', "L'aperçu du site est momentanément indisponible.");
}
insertLog('ADMIN', 'Ouverture de l’aperçu du site public');
header('Location: ../');
exit();
