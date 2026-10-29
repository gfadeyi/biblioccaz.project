<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('candidatures.php');
    $idCandidat = (int) ($_POST['user_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $messageDecision = mb_substr(trim($_POST['message'] ?? ''), 0, 1000);
    $prevenir = !empty($_POST['prevenir']);

    if (!in_array($decision, ['accepter', 'refuser'], true)) {
        rediriger('candidatures.php');
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, pseudo, prenom, email, statut FROM user WHERE id = ? FOR UPDATE");
    $stmt->execute([$idCandidat]);
    $candidat = $stmt->fetch();
    if (!$candidat || $candidat['statut'] !== 'en_attente_moderateur') {
        $pdo->rollBack();
        rediriger('candidatures.php', 'warning', "Cette candidature a déjà été traitée.");
    }

    if ($decision === 'accepter') {
        $pdo->prepare("UPDATE user SET role = 'moderateur', statut = 'valide_moderateur' WHERE id = ?")->execute([$idCandidat]);
    } else {
        $pdo->prepare("UPDATE user SET role = 'client', statut = 'actif' WHERE id = ?")->execute([$idCandidat]);
    }
    try {
        $pdo->prepare("
            UPDATE candidature_moderateur
            SET statut = ?, date_decision = NOW(), id_decideur = ?, message_decision = ?
            WHERE id_user = ? AND statut = 'en_attente'")
            ->execute([$decision === 'accepter' ? 'acceptee' : 'refusee', (int) $_SESSION['user_id'], $messageDecision ?: null, $idCandidat]);
    } catch (PDOException $e) {
    }
    $pdo->commit();

    $pseudo = $candidat['pseudo'];
    insertLog('ADMIN', $decision === 'accepter' ? "Candidature acceptée : @$pseudo devient modérateur" : "Candidature de @$pseudo refusée, compte lecteur conservé");

    $mailEnvoye = false;
    if ($prevenir) {
        require_once __DIR__ . '/../mailer.php';
        $complement = $messageDecision !== '' ? "\n\nMessage de l'équipe :\n" . $messageDecision : '';
        try {
            $mail = creerMailer();
            $mail->addAddress($candidat['email'], $candidat['prenom']);
            if ($decision === 'accepter') {
                habillerMail($mail, "Votre candidature est acceptée",
                    "Bonjour " . $candidat['prenom'] . ",\n\nBonne nouvelle : l'équipe BIBLIOccaz a accepté votre candidature. Vous êtes maintenant modérateur.\n\nConnectez-vous au back-office avec vos identifiants habituels pour valider les avis et les fiches de livres." . $complement,
                    SITE_URL . "/admin/login.php", "Accéder au back-office");
            } else {
                habillerMail($mail, "Votre candidature à la modération",
                    "Bonjour " . $candidat['prenom'] . ",\n\nMerci pour l'intérêt que vous portez à BIBLIOccaz. Après étude, l'équipe n'a pas retenu votre candidature pour le moment.\n\nVotre compte lecteur reste actif : vous pouvez continuer à acheter, donner des livres et participer aux événements." . $complement,
                    SITE_URL, "Revenir sur BIBLIOccaz");
            }
            $mail->send();
            $mailEnvoye = true;
        } catch (Throwable $e) {
            insertLog('ERREUR', "Mail de décision de candidature non envoyé à @$pseudo : " . $e->getMessage());
        }
    }

    $texte = $decision === 'accepter' ? "@$pseudo est maintenant modérateur." : "Candidature de @$pseudo refusée : son compte lecteur reste actif.";
    if ($prevenir) {
        $texte .= $mailEnvoye ? " Le mail de réponse est parti." : " Le mail n'a pas pu partir.";
    }
    rediriger('candidatures.php', $prevenir && !$mailEnvoye ? 'warning' : 'success', $texte);
}

$tableAbsente = false;
try {
    $enAttente = $pdo->query("
        SELECT u.id, u.pseudo, u.email, u.nom, u.prenom, u.email_verifie, u.date_inscription, c.motivations, c.date_candidature
        FROM user u
        LEFT JOIN candidature_moderateur c ON c.id_user = u.id AND c.statut = 'en_attente'
        WHERE u.statut = 'en_attente_moderateur'
        ORDER BY COALESCE(c.date_candidature, u.date_inscription)")->fetchAll();
    $historique = $pdo->query("
        SELECT c.statut, c.date_candidature, c.date_decision, c.motivations, c.message_decision, u.pseudo, u.prenom, u.nom, d.pseudo AS pseudo_decideur
        FROM candidature_moderateur c
        JOIN user u ON u.id = c.id_user
        LEFT JOIN user d ON d.id = c.id_decideur
        WHERE c.statut <> 'en_attente'
        ORDER BY c.date_decision DESC
        LIMIT 30")->fetchAll();
} catch (PDOException $e) {
    $tableAbsente = true;
    $enAttente = $pdo->query("
        SELECT id, pseudo, email, nom, prenom, email_verifie, date_inscription, NULL AS motivations, date_inscription AS date_candidature
        FROM user WHERE statut = 'en_attente_moderateur' ORDER BY id")->fetchAll();
    $historique = [];
}

$titrePage = 'Candidatures';
$pageActive = 'candidatures';
$sousTitrePage = "Les personnes qui souhaitent rejoindre l'équipe de modération.";
include __DIR__ . '/_haut.php';
?>

<?php if (aLeDroit('candidatures_auteur')): ?>
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link active bg-success" href="candidatures.php" aria-current="page">Modération</a></li>
        <li class="nav-item"><a class="nav-link text-success" href="candidatures_auteur.php">Auteurs<?php if (($compteurs['candidatures_auteur'] ?? 0) > 0): ?> <span class="badge rounded-pill bg-success ms-1"><?= (int) $compteurs['candidatures_auteur'] ?></span><?php endif; ?></a></li>
    </ul>
<?php endif; ?>

<?php if ($tableAbsente): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Importez <code>sql/maj_candidatures.sql</code> pour enregistrer les motivations et l'historique des décisions.</div>
<?php endif; ?>

<?php if (!$enAttente): ?>
    <div class="carte p-5 text-center mb-4">
        <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
        <p class="text-muted mb-0 mt-2">Aucune candidature en attente.</p>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($enAttente as $c): ?>
        <?php $nomComplet = trim($c['prenom'] . ' ' . $c['nom']); ?>
        <div class="col-xl-6">
            <article class="carte p-4 h-100 d-flex flex-column">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1"><?= e($nomComplet ?: '@' . $c['pseudo']) ?></h2>
                        <p class="small text-muted mb-0">@<?= e($c['pseudo']) ?> · <?= e($c['email']) ?></p>
                    </div>
                    <div class="text-sm-end small text-muted">
                        Reçue le <?= date('d/m/Y', strtotime($c['date_candidature'])) ?>
                        <div class="mt-1">
                            <?php if ($c['email_verifie']): ?>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-envelope-check" aria-hidden="true"></i> Email confirmé</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-envelope-exclamation" aria-hidden="true"></i> Email pas encore confirmé</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h3 class="small fw-bold text-uppercase text-muted mb-2">Motivations</h3>
                <?php if ($c['motivations']): ?>
                    <blockquote class="candidature-motivations mb-3"><?= nl2br(e($c['motivations'])) ?></blockquote>
                <?php else: ?>
                    <p class="small text-muted fst-italic mb-3">Non enregistrées (candidature envoyée avant la mise à jour).</p>
                <?php endif; ?>

                <form method="POST" class="mt-auto">
                    <?= champCsrf() ?>
                    <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                    <label for="message-<?= $c['id'] ?>" class="form-label small fw-bold">Message au candidat <span class="fw-normal text-muted">(facultatif)</span></label>
                    <textarea id="message-<?= $c['id'] ?>" name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" placeholder="Ajouté au mail de réponse"></textarea>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="prevenir" value="1" id="prevenir-<?= $c['id'] ?>" checked>
                        <label class="form-check-label small" for="prevenir-<?= $c['id'] ?>">Prévenir le candidat par mail</label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button name="decision" value="accepter" class="btn btn-success fw-bold"><i class="bi bi-check-lg" aria-hidden="true"></i> Accepter</button>
                        <button name="decision" value="refuser" class="btn btn-outline-danger"><i class="bi bi-x-lg" aria-hidden="true"></i> Refuser</button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Refuser ne suspend pas le compte : la personne garde un compte lecteur.</p>
                </form>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($historique): ?>
    <div class="carte">
        <div class="carte-entete">Décisions récentes</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr><th>Candidat</th><th>Décision</th><th>Par</th><th>Message envoyé</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $h): ?>
                        <tr>
                            <td>
                                <div class="fw-bold">@<?= e($h['pseudo']) ?></div>
                                <div class="small text-muted">Reçue le <?= date('d/m/Y', strtotime($h['date_candidature'])) ?></div>
                            </td>
                            <td>
                                <?php if ($h['statut'] === 'acceptee'): ?>
                                    <span class="badge rounded-pill bg-success">Acceptée</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-secondary">Refusée</span>
                                <?php endif; ?>
                                <div class="small text-muted"><?= $h['date_decision'] ? date('d/m/Y', strtotime($h['date_decision'])) : '' ?></div>
                            </td>
                            <td class="small"><?= $h['pseudo_decideur'] ? '@' . e($h['pseudo_decideur']) : '—' ?></td>
                            <td class="small text-muted"><?= $h['message_decision'] ? e($h['message_decision']) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
