<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../notifications_lib.php';
exigerDroit('admin');

$onglet = in_array($_GET['onglet'] ?? '', ['desinscrits', 'historique'], true) ? $_GET['onglet'] : 'inscrits';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('newsletter.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'desabonner') {
        $id = (int) ($_POST['id'] ?? 0);
        if (($_POST['origine'] ?? '') === 'membre') {
            $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->prepare("UPDATE user SET is_newsletter = 0, date_newsletter = NOW() WHERE id = ?")->execute([$id]);
            journalNewsletter($pdo, (string) $stmt->fetchColumn(), $id, false, 'admin');
            insertLog('ADMIN', "Désinscription newsletter forcée pour le compte #$id");
        } else {
            $stmt = $pdo->prepare("SELECT email FROM newsletter_abonne WHERE id_abonne = ?");
            $stmt->execute([$id]);
            $pdo->prepare("UPDATE newsletter_abonne SET actif = 0, date_desinscription = NOW() WHERE id_abonne = ?")->execute([$id]);
            journalNewsletter($pdo, (string) $stmt->fetchColumn(), null, false, 'admin');
            insertLog('ADMIN', "Désinscription newsletter forcée pour l'abonné sans compte #$id");
        }
        rediriger('newsletter.php?onglet=' . $onglet, 'success', "Abonné retiré de la newsletter.");
    }

    if ($action === 'envoyer') {
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($sujet === '' || $message === '') {
            rediriger('newsletter.php', 'danger', "Le sujet et le message sont obligatoires.");
        }
        $abonnes = array_values(array_unique(array_merge(
            $pdo->query("SELECT email FROM user WHERE is_newsletter = 1")->fetchAll(PDO::FETCH_COLUMN),
            $pdo->query("SELECT email FROM newsletter_abonne WHERE actif = 1")->fetchAll(PDO::FETCH_COLUMN)
        )));
        if (!$abonnes) {
            rediriger('newsletter.php', 'warning', "Aucun abonné actif.");
        }

        require_once __DIR__ . '/../mailer.php';
        try {
            $mail = creerMailer();
            $mail->addAddress(MAIL_FROM, 'Abonnés ' . MAIL_FROM_NAME);
            foreach ($abonnes as $email) {
                $mail->addBCC($email);
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

$abonnes = $pdo->query("
    SELECT 'membre' AS origine, id, pseudo, role, email, is_newsletter AS actif, date_newsletter AS depuis
    FROM user WHERE date_newsletter IS NOT NULL
    UNION ALL
    SELECT 'externe', id_abonne, NULL, NULL, email, actif, COALESCE(date_desinscription, date_inscription)
    FROM newsletter_abonne
    ORDER BY depuis DESC")->fetchAll();
$actifs = array_filter($abonnes, fn($a) => (int) $a['actif'] === 1);
$desinscrits = array_filter($abonnes, fn($a) => (int) $a['actif'] === 0);
$liste = $onglet === 'desinscrits' ? $desinscrits : $actifs;

$historique = [];
$nouveaux30 = 0;
try {
    $historique = $pdo->query("
        SELECT h.*, u.pseudo FROM newsletter_historique h LEFT JOIN user u ON u.id = h.id_user
        ORDER BY h.date_action DESC, h.id_historique DESC LIMIT 100")->fetchAll();
    $nouveaux30 = (int) $pdo->query("
        SELECT COUNT(DISTINCT email) FROM newsletter_historique
        WHERE action = 'inscription' AND source <> 'reprise' AND date_action >= NOW() - INTERVAL 30 DAY")->fetchColumn();
} catch (PDOException $e) {
}
$sources = ['site' => 'Pied de page du site', 'profil' => 'Profil du membre', 'admin' => 'Back-office', 'reprise' => 'Avant historique'];

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
        <div class="row g-3 mb-3">
            <?php foreach ([
                ['envelope-check', 'Abonnés actifs', count($actifs)],
                ['person-plus', 'Nouveaux (30 jours)', $nouveaux30],
                ['envelope-x', 'Désinscrits', count($desinscrits)],
            ] as [$icone, $libelle, $valeur]): ?>
                <div class="col-4">
                    <div class="stat">
                        <span class="stat-icone"><i class="bi bi-<?= $icone ?>"></i></span>
                        <span><span class="stat-valeur d-block"><?= $valeur ?></span><span class="stat-libelle"><?= $libelle ?></span></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <ul class="nav nav-pills mb-3">
            <?php foreach (['inscrits' => 'Inscrits (' . count($actifs) . ')', 'desinscrits' => 'Désinscrits (' . count($desinscrits) . ')', 'historique' => 'Historique'] as $cle => $libelle): ?>
                <li class="nav-item"><a class="nav-link <?= $onglet === $cle ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>"><?= $libelle ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="carte">
            <div class="table-responsive">
                <?php if ($onglet === 'historique'): ?>
                    <table class="table align-middle small">
                        <thead class="table-light text-uppercase"><tr><th>Date</th><th>Qui</th><th>Mouvement</th><th>Depuis</th></tr></thead>
                        <tbody>
                            <?php if (!$historique): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Aucun mouvement enregistré.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($historique as $h): ?>
                                <tr>
                                    <td class="text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($h['date_action'])) ?></td>
                                    <td>
                                        <?php if ($h['pseudo']): ?><div class="fw-bold">@<?= e($h['pseudo']) ?></div><?php else: ?><div class="fw-bold text-muted">Sans compte</div><?php endif; ?>
                                        <div class="text-muted"><?= e($h['email']) ?></div>
                                    </td>
                                    <td><?= $h['action'] === 'inscription' ? '<span class="badge bg-success"><i class="bi bi-plus-lg"></i> Inscription</span>' : '<span class="badge bg-secondary"><i class="bi bi-dash-lg"></i> Désinscription</span>' ?></td>
                                    <td class="text-muted"><?= e($sources[$h['source']] ?? $h['source']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <table class="table align-middle small">
                        <thead class="table-light text-uppercase"><tr><th>Abonné</th><th><?= $onglet === 'inscrits' ? 'Inscrit le' : 'Désinscrit le' ?></th><th class="text-end"></th></tr></thead>
                        <tbody>
                            <?php if (!$liste): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4"><?= $onglet === 'inscrits' ? "Aucun abonné pour l'instant." : 'Aucun désinscrit.' ?></td></tr>
                            <?php endif; ?>
                            <?php foreach ($liste as $a): ?>
                                <tr>
                                    <td>
                                        <?php if ($a['origine'] === 'membre'): ?>
                                            <div class="fw-bold">@<?= e($a['pseudo']) ?> <span class="badge bg-light text-dark border fw-normal"><?= ROLES_LIBELLES[$a['role']] ?? e($a['role']) ?></span></div>
                                        <?php else: ?>
                                            <div class="fw-bold text-muted">Sans compte</div>
                                        <?php endif; ?>
                                        <div class="text-muted"><?= e($a['email']) ?></div>
                                    </td>
                                    <td class="text-muted"><?= date('d/m/Y H:i', strtotime($a['depuis'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($a['actif']): ?>
                                            <form method="POST" data-confirmer="Désabonner <?= e($a['email']) ?> ?">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="desabonner">
                                                <input type="hidden" name="origine" value="<?= $a['origine'] ?>">
                                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="Désabonner"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
