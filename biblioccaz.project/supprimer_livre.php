<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if ($id) {
    $query = $pdo->prepare("DELETE FROM livre WHERE id_livre = :id");
    $query->execute(['id' => $id]);
}

header("Location: inventaire_admin.php");
exit();