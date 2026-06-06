<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }
include 'header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Utilisateurs en ligne</h2>
            <p class="text-muted small">Actualisation automatique toutes les 5 secondes</p>
        </div>
        <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Pseudo</th>
                        <th>Email</th>
                        <th class="text-end pe-4">Dernière activité</th>
                    </tr>
                </thead>
                <tbody id="online-users-body">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function refreshOnlineUsers() {
    fetch('get_online_users.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('online-users-body').innerHTML = data;
        });
}
setInterval(refreshOnlineUsers, 5000);
refreshOnlineUsers();
</script>

<?php include 'footer.php'; ?>