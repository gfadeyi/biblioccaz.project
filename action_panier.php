if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: index.php?error=action_interdite");
    exit();
}