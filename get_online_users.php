<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT pseudo, email, last_activity FROM user WHERE last_activity > NOW() - INTERVAL 5 MINUTE ORDER BY last_activity DESC");
$enLigne = $stmt->fetchAll();

if (empty($enLigne)) {
    echo '<tr><td colspan="3" class="text-center py-4 text-muted">Aucun utilisateur actif.</td></tr>';
} else {
    foreach ($enLigne as $u) {
        echo '<tr>
                <td class="ps-4"><span class="text-success me-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i></span><span class="fw-bold">' . htmlspecialchars($u['pseudo']) . '</span></td>
                <td class="text-muted">' . htmlspecialchars($u['email']) . '</td>
                <td class="text-end pe-4 text-muted small">' . date('H:i:s', strtotime($u['last_activity'])) . '</td>
              </tr>';
    }
}
?>