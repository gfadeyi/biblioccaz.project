<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$idUser = (int) ($_SESSION['user_id'] ?? 0);
$code = trim($_POST['code'] ?? $_GET['code'] ?? '');
$codeCourt = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['code_court'] ?? ''));
$erreur = '';
$succes = '';
$don = null;

if ($code !== '' || $codeCourt !== '') {
    $stmt = $pdo->prepare("
        SELECT d.*, dn.pseudo AS donneur_pseudo, dn.email AS donneur_email
        FROM don d INNER JOIN user dn ON dn.id = d.id_donneur
        WHERE " . ($code !== '' ? "d.code = ?" : "d.code_court = ?"));
    $stmt->execute([$code !== '' ? $code : $codeCourt]);
    $don = $stmt->fetch() ?: null;
    if (!$don) {
        $erreur = "Code inconnu. Vérifiez les 6 caractères donnés par le donneur.";
    }
}

if ($don && $idUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {
    $statut = statutDon($don);
    if (!verifierCsrf()) {
        $erreur = "Session expirée, merci de réessayer.";
    } elseif ((int) $don['id_donneur'] === $idUser) {
        $erreur = "C'est la personne qui récupère le livre qui doit valider le retrait, pas le donneur.";
    } elseif (!($statut === 'reserve' && (int) $don['id_beneficiaire'] === $idUser) && $statut !== 'disponible') {
        $erreur = "Ce don n'est plus disponible (statut : " . DON_STATUTS[$statut][0] . ").";
    } else {
        $maj = $pdo->prepare("
            UPDATE don SET statut = 'recupere', id_beneficiaire = ?, date_recuperation = NOW(),
                           date_reservation = COALESCE(date_reservation, NOW())
            WHERE id_don = ? AND (statut = 'disponible' OR (statut = 'reserve' AND id_beneficiaire = ?))");
        $maj->execute([$idUser, $don['id_don'], $idUser]);

        if ($maj->rowCount()) {
            $pdo->prepare("UPDATE user SET solde_points = solde_points + ? WHERE id = ?")->execute([DON_POINTS, $don['id_donneur']]);
            insertLog('DON', "Don #{$don['id_don']} récupéré (+" . DON_POINTS . " points pour le donneur)");
            notifierParMail($don['donneur_email'], "Votre don a été récupéré",
                "Bonjour {$don['donneur_pseudo']},\n\nVotre livre « {$don['titre']} » a bien été récupéré par {$_SESSION['pseudo']}.\n"
                . "Merci pour votre générosité ! Vous gagnez " . DON_POINTS . " points BIBLIOccaz.");
            $succes = "Retrait validé ! Bonne lecture avec « {$don['titre']} ».";
            $don['statut'] = 'recupere';
        } else {
            $erreur = "Ce don vient d'être pris par quelqu'un d'autre.";
        }
    }
}

$csrf = $idUser ? jetonCsrf() : '';
include 'header.php';
?>

<div class="container my-5" style="max-width: 520px;">
    <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 20px;">
        <i class="bi bi-qr-code-scan fs-1" style="color:#274e13;"></i>
        <h2 class="fw-bold mt-2 mb-3">Valider un retrait</h2>

        <?php if ($succes): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succes) ?></div>
            <a href="mes_dons.php" class="btn btn-success rounded-pill">Voir mes dons</a>

        <?php elseif (!$idUser): ?>
            <p class="text-muted">Connectez-vous avec le compte qui récupère le livre, puis scannez à nouveau le QR code.</p>
            <a href="login.php" class="btn btn-success rounded-pill">Se connecter</a>

        <?php elseif ($don && !$erreur && in_array(statutDon($don), ['disponible', 'reserve'], true)): ?>
            <div class="bg-light rounded-3 p-3 mb-3 text-start">
                <div class="fw-bold"><?= htmlspecialchars($don['titre']) ?></div>
                <div class="small text-muted">Don de <?= htmlspecialchars($don['donneur_pseudo']) ?> · <?= htmlspecialchars($don['lieu_retrait']) ?></div>
            </div>
            <p class="small">Vous avez le livre en main ? Confirmez la récupération :</p>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="code" value="<?= htmlspecialchars($don['code']) ?>">
                <button name="confirmer" value="1" class="btn btn-success rounded-pill fw-bold w-100 py-2">J'ai bien récupéré ce livre</button>
            </form>

        <?php else: ?>
            <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
            <?php if ($don && !$erreur): ?>
                <div class="alert alert-secondary">Ce don est <?= strtolower(DON_STATUTS[statutDon($don)][0]) ?>.</div>
            <?php endif; ?>
            <p class="text-muted small">Pas de QR code sous la main ? Saisissez le code à 6 caractères affiché sur la page du donneur.</p>
            <form method="POST" class="d-flex gap-2">
                <input type="text" name="code_court" maxlength="6" required class="form-control text-center text-uppercase font-monospace fs-4"
                       style="letter-spacing: 6px;" placeholder="ABC234" autocomplete="off" value="<?= htmlspecialchars($codeCourt) ?>">
                <button class="btn btn-success rounded-pill px-4">Valider</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
