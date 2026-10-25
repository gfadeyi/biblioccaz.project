<?php
// Newsletter : abonnés, désabonnement manuel et envoi d'une campagne.
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('newsletter.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'desabonner') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $pdo->prepare("UPDATE user SET is_newsletter = 0, date_newsletter = NOW() WHERE id = ?")->execute([$id]);
        insertLog('ADMIN', "Désinscription newsletter forcée pour le compte #$id");
        rediriger('newsletter.php', 'success', "Abonné retiré de la newsletter.");
    }

    if ($action === 'envoyer') {
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($sujet === '' || $message === '') {
            rediriger('newsletter.php', 'danger', "Le sujet et le message sont obligatoires.");
        }
        $abonnes = $pdo->query("SELECT email FROM user WHERE is_newsletter = 1")->fetchAll(PDO::FETCH_COLUMN);
        if (!$abonnes) {
            rediriger('newsletter.php', 'warning', "Aucun abonné actif.");
        }

        require_once __DIR__ . '/../mailer.php';
        try {
            $mail = creerMailer();
            $mail->addAddress(MAIL_FROM, 'Abonnés ' . MAIL_FROM_NAME);
            foreach ($abonnes as $email) {
                $mail->addBCC($email);   // copie cachée : les abonnés ne voient pas les adresses des autres
            }
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body = '<div style="font-family: Arial, sans-serif; color: #333;">'
                . nl2br(e($message))
                . '<hr><p style="font-size: 12px; color: gray;">Vous recevez ce message car vous êtes inscrit à la newsletter '
                . e(MAIL_FROM_NAME) . '. Pour vous désabonner, rendez-vous dans votre espace profil.</p></div>';
            $mail->AltBody = $message;
            $mail->send();

            insertLog('ADMIN', "Newsletter « $sujet » envoyée à " . count($abonnes) . " abonnés");
            rediriger('newsletter.php', 'success', "Newsletter envoyée à " . count($abonnes) . " abonné" . (count($abonnes) > 1 ? 's' : '') . ".");
        } catch (Exception $e) {
            rediriger('newsletter.php', 'danger', "Échec de l'envoi : " . $e->getMessage());
        }
    }
    rediriger('newsletter.php');
}

$abonnes = $pdo->query("SELECT id, pseudo, email, is_newsletter, date_newsletter FROM user WHERE date_newsletter IS NOT NULL ORDER BY date_newsletter DESC")->fetchAll();
$actifs = array_filter($abonnes, fn($a) => (int) $a['is_newsletter'] === 1);

$titrePage = 'Newsletter';
$pageActive = 'newsletter';
include __DIR__ . '/_haut.php';
?>

<div class="row g-4">
    <div class="col-xl-5">
        <form method="POST" class="carte p-4" data-confirmer="Envoyer cette newsletter à <?= count($actifs) ?> abonné(s) ?">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="envoyer">
            <h2 class="h6 fw-bold"><i class="bi bi-send me-2 text-success"></i>Nouvelle campagne</h2>
            <p class="small text-muted">Envoyée en copie cachée aux <strong><?= count($actifs) ?></strong> abonnés actifs.</p>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="sujet">Sujet</label>
                <input type="text" id="sujet" name="sujet" class="form-control" maxlength="150" value="Des nouvelles fraîches de BIBLIOccaz ! 📚" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="8" required>Bonjour cher lecteur !

De nouveaux ouvrages viennent d'être ajoutés au catalogue par nos auteurs et notre communauté. N'attendez plus pour venir les découvrir !

L'équipe BIBLIOccaz</textarea>
            </div>
            <button class="btn btn-vert fw-bold" <?= $actifs ? '' : 'disabled' ?>><i class="bi bi-rocket-takeoff me-1"></i> Envoyer</button>
        </form>
    </div>

    <div class="col-xl-7">
        <div class="carte">
            <div class="carte-entete">Historique des abonnements (<?= count($abonnes) ?>)</div>
            <div class="table-responsive">
                <table class="table align-middle small">
                    <thead class="table-light text-uppercase"><tr><th>Membre</th><th>Statut</th><th>Depuis le</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        <?php if (!$abonnes): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucun abonné pour l'instant.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($abonnes as $a): ?>
                            <tr>
                                <td><div class="fw-bold">@<?= e($a['pseudo']) ?></div><div class="text-muted"><?= e($a['email']) ?></div></td>
                                <td><?= $a['is_newsletter'] ? '<span class="badge bg-success">Inscrit</span>' : '<span class="badge bg-secondary">Désinscrit</span>' ?></td>
                                <td class="text-muted"><?= date('d/m/Y', strtotime($a['date_newsletter'])) ?></td>
                                <td class="text-end">
                                    <?php if ($a['is_newsletter']): ?>
                                        <form method="POST" data-confirmer="Désabonner <?= e($a['email']) ?> ?">
                                            <?= champCsrf() ?>
                                            <input type="hidden" name="action" value="desabonner">
                                            <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Désabonner"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
