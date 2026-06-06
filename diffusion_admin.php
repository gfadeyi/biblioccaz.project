<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if ($_GET['action'] === 'unsubscribe') {
        $stmtEmail = $pdo->prepare(" SELECT email FROM user WHERE id = ?");
        $stmtEmail -> execute([$id]);
        $user = $stmtEmail->fetch();

        if (user){
            $email =$user['email'];
        }

        $stmt = $pdo->prepare("UPDATE user SET is_newsletter = 0 WHERE id = ?");
        $stmt->execute([$id]);
    
            $apiKey = ''; 
            $idListe = 2; 

        if (!empty($apiKey)){
            $url = 'https://api.brevo.com/v3/contacts/lists/' . $idListe . '/contacts/remove';

            $data = [
                'emails' => [$email]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ]);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            curl_close($ch);   
    }
}
    header("Location: diffusion_admin.php");
    exit();
}

include 'header.php';

$stmt = $pdo->query("SELECT id, pseudo, email, date_newsletter FROM user WHERE is_newsletter = 1 ORDER BY date_newsletter DESC");
$subscribers = $stmt->fetchAll();

$emails_only = array_column($subscribers, 'email');
$copy_list = implode('; ', $emails_only);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-megaphone me-2 text-success"></i>Diffusion & Newsletter</h2>
            <p class="text-muted small mb-0">Gestion de l'audience et export des contacts actifs.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="badge bg-success px-4 py-2 rounded-pill fs-6 shadow-sm">
                <?= count($subscribers) ?> abonnés
            </div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 15px; background-color: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-uppercase small text-muted">Extraire les adresses</h6>
            <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" onclick="navigator.clipboard.writeText('<?= $copy_list ?>')">
                <i class="bi bi-copy me-2"></i>Copier tout
            </button>
        </div>
        <textarea class="form-control border-0 bg-white small shadow-inner" rows="2" readonly style="resize: none;"><?= $copy_list ?: 'Aucun abonné.' ?></textarea>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th class="ps-4">Membre</th>
                    <th>Email</th>
                    <th class="text-center">Abonné le</th>
                    <th class="text-end pe-4">Désabonner</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">Liste vide.</td></tr>
                <?php endif; ?>
                <?php foreach ($subscribers as $s): ?>
                <tr>
                    <td class="ps-4 py-3">
                        <span class="fw-bold"><?= htmlspecialchars($s['pseudo']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td>
                    <td class="text-center small">
                        <?= $s['date_newsletter'] ? date('d/m/Y', strtotime($s['date_newsletter'])) : '-' ?>
                    </td>
                    <td class="text-end pe-4">
                        <a href="diffusion_admin.php?action=unsubscribe&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-x-circle"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>