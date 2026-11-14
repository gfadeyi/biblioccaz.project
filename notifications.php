<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'notifications_membre_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$notifications = notificationsMembre($pdo, $idUser, 100);
$nonLues = count(array_filter($notifications, fn($n) => !(int) $n['lu']));
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$csrf = jetonCsrf();
$titreDocument = 'Mes notifications';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-notifications">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes notifications</span>
    </nav>

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="titre-page-compte mb-1">Mes notifications</h1>
            <p class="text-muted mb-0">Dons, événements, retours et demandes : tout ce qui vous concerne, au même endroit.</p>
        </div>
        <?php if ($nonLues > 0): ?>
            <form method="POST" action="notification_action.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="tout_lire">
                <button type="submit" class="btn btn-outline-bibli rounded-pill">Tout marquer comme lu</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if (!$notifications): ?>
        <div class="carte-compte text-center text-muted py-5">
            <?= iconeDessin('clochette') ?>
            <p class="mb-0 mt-2">Aucune notification pour le moment.</p>
        </div>
    <?php else: ?>
        <ul class="liste-notifications">
            <?php foreach ($notifications as $notification): ?>
                <li class="notification<?= (int) $notification['lu'] ? '' : ' notification--non-lue' ?>">
                    <form method="POST" action="notification_action.php">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="ouvrir">
                        <input type="hidden" name="id_notification" value="<?= (int) $notification['id_notification'] ?>">
                        <button type="submit" class="notification__bouton">
                            <span class="notification__icone"><?= iconeDessin(iconeNotification($notification['type'])) ?></span>
                            <span class="notification__texte">
                                <span><?= htmlspecialchars($notification['message']) ?></span>
                                <small><?= htmlspecialchars(dateNotification($notification['date_creation'])) ?><?= (int) $notification['lu'] ? '' : ' · <strong>non lue</strong>' ?></small>
                            </span>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
