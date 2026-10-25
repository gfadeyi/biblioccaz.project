<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$id = (int) ($_GET['id'] ?? 0);
$evt = chargerEvenement($pdo, $id);
if (!$evt) {
    rediriger('evenements.php', 'warning', "Cet événement n'existe pas.");
}
$retour = "evenement_participants.php?id=$id";
$presencesOuvertes = $evt['statut'] === 'publie' && date('Y-m-d') >= substr($evt['date_debut'], 0, 10);

function validerPresence(PDO $pdo, array $inscription, array $evt, bool $present): void
{
    $pdo->prepare("UPDATE evenement_inscription SET present = ?, date_presence = " . ($present ? "NOW()" : "NULL") . " WHERE id_inscription = ?")
        ->execute([$present ? 1 : 0, $inscription['id_inscription']]);
    insertLog('EVENEMENT', "Présence " . ($present ? 'validée' : 'retirée') . " pour l'inscription #{$inscription['id_inscription']} (événement #{$evt['id_evenement']})");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare("
        SELECT i.*, u.pseudo FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
        WHERE i.id_evenement = ? AND " . ($action === 'code' ? "i.code_court = ?" : "i.id_inscription = ?"));
    $stmt->execute([$id, $action === 'code' ? strtoupper(trim($_POST['code'] ?? '')) : (int) ($_POST['id_inscription'] ?? 0)]);
    $inscription = $stmt->fetch();

    if (!$inscription) {
        rediriger($retour, 'warning', $action === 'code' ? "Aucun billet avec ce code pour cet événement." : "Inscription introuvable.");
    }

    if (in_array($action, ['code', 'present', 'absent'], true)) {
        if (!$presencesOuvertes) {
            rediriger($retour, 'warning', "Les présences se valident à partir du jour de l'événement.");
        }
        if ($inscription['statut'] !== 'inscrit') {
            rediriger($retour, 'warning', "@{$inscription['pseudo']} n'a pas de place confirmée (" . ($inscription['statut'] === 'attente' ? "liste d'attente" : 'inscription annulée') . ").");
        }
        if ($action === 'code' && $inscription['present']) {
            rediriger($retour, 'info', "Le billet de @{$inscription['pseudo']} a déjà été validé.");
        }
        validerPresence($pdo, $inscription, $evt, $action !== 'absent');
        rediriger($retour, 'success', $action === 'absent' ? "Présence retirée pour @{$inscription['pseudo']}." : "Présence validée pour @{$inscription['pseudo']}.");
    }

    if ($evt['statut'] !== 'publie' || $evt['date_fin'] < date('Y-m-d H:i:s')) {
        rediriger($retour, 'warning', "Cet événement est terminé ou annulé : les inscriptions ne peuvent plus changer.");
    }

    if ($action === 'inscrire') {
        $pdo->beginTransaction();
        $promus = promouvoirListeAttente($pdo, $id, (int) $inscription['id_inscription']);
        $pdo->commit();
        if (!$promus) {
            rediriger($retour, 'warning', "Plus aucune place libre : augmentez le nombre de places ou retirez un participant.");
        }
        prevenirPromus($promus, $evt);
        rediriger($retour, 'success', "@{$inscription['pseudo']} est inscrit et a été prévenu par mail.");
    }

    if ($action === 'retirer') {
        [$type, ] = desinscrireDeEvenement($pdo, $id, (int) $inscription['id_user'], true);
        notifierParMail(emailUtilisateur($pdo, (int) $inscription['id_user']), "Inscription annulée : " . $evt['titre'],
            "Votre inscription à « {$evt['titre']} » a été annulée par l'équipe BIBLIOccaz.\nPour toute question, répondez à l'organisateur de l'événement.");
        rediriger($retour, $type, "@{$inscription['pseudo']} a été retiré de l'événement.");
    }
    rediriger($retour);
}

$stmt = $pdo->prepare("
    SELECT i.*, u.pseudo, u.nom, u.prenom, u.email
    FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
    WHERE i.id_evenement = ? AND i.statut IN ('inscrit', 'attente')
    ORDER BY i.statut = 'attente', i.date_inscription, i.id_inscription");
$stmt->execute([$id]);
$personnes = $stmt->fetchAll();
$inscrits = array_values(array_filter($personnes, fn($p) => $p['statut'] === 'inscrit'));
$attente = array_values(array_filter($personnes, fn($p) => $p['statut'] === 'attente'));
$nbPresents = count(array_filter($inscrits, fn($p) => $p['present']));
$modifiable = $evt['statut'] === 'publie' && $evt['date_fin'] >= date('Y-m-d H:i:s');

function bouton(string $action, int $idInscription, string $libelle, string $classe, string $confirmation = ''): string
{
    return '<form method="POST" class="d-inline"' . ($confirmation ? ' data-confirmer="' . e($confirmation) . '"' : '') . '>'
        . champCsrf()
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<input type="hidden" name="id_inscription" value="' . $idInscription . '">'
        . '<button class="btn btn-sm ' . $classe . '">' . $libelle . '</button></form>';
}

$titrePage = $evt['titre'];
$pageActive = 'evenements';
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="evenements.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Événements</a>
    <?php if ($evt['statut'] === 'publie'): ?>
        <a href="evenement.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier / annuler</a>
    <?php endif; ?>
    <a href="evenement_pdf.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> Liste PDF</a>
    <a href="../evenement.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Voir sur le site</a>
</div>

<p class="mb-3">
    <?= badgeTypeEvenement($evt['type']) ?> <?= badgeEvenement($evt) ?>
    <span class="text-muted small ms-2"><?= ucfirst(horaireEvenement($evt)) ?> · <?= e($evt['lieu']) ?></span>
</p>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['people', 'Inscrits', count($inscrits) . ' / ' . $evt['places_max']],
        ['hourglass-split', "En liste d'attente", count($attente)],
        ['person-check', 'Présents', $nbPresents],
        ['percent', 'Taux de présence', count($inscrits) ? round(100 * $nbPresents / count($inscrits)) . ' %' : '—'],
    ] as [$icone, $libelle, $valeur]): ?>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <span class="stat-icone"><i class="bi bi-<?= $icone ?>"></i></span>
                <span><span class="stat-valeur d-block"><?= $valeur ?></span><span class="stat-libelle"><?= $libelle ?></span></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($presencesOuvertes): ?>
    <form method="POST" class="carte p-3 mb-4 d-flex flex-wrap align-items-center gap-2">
        <?= champCsrf() ?>
        <input type="hidden" name="action" value="code">
        <label class="fw-bold small me-2" for="code"><i class="bi bi-qr-code-scan"></i> Valider un billet</label>
        <input type="text" id="code" name="code" class="form-control font-monospace text-uppercase" style="max-width: 160px; letter-spacing: 3px;" maxlength="6" placeholder="CODE" autocomplete="off" autofocus required>
        <button class="btn btn-vert">Valider</button>
        <span class="small text-muted">Ou scannez le QR code du billet avec votre téléphone (connecté au back-office).</span>
    </form>
<?php elseif ($evt['statut'] === 'publie'): ?>
    <div class="alert alert-light border small"><i class="bi bi-info-circle"></i> La validation des présences s'ouvrira le jour de l'événement.</div>
<?php endif; ?>

<div class="carte mb-4">
    <div class="carte-entete">Inscrits (<?= count($inscrits) ?>)</div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase"><tr><th>Participant</th><th>Billet</th><th>Inscrit le</th><th class="text-center">Présence</th><th class="text-end"></th></tr></thead>
            <tbody>
                <?php if (!$inscrits): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Personne n'est encore inscrit.</td></tr>
                <?php endif; ?>
                <?php foreach ($inscrits as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= e(trim($p['prenom'] . ' ' . $p['nom'])) ?: '@' . e($p['pseudo']) ?></div>
                            <div class="small text-muted">@<?= e($p['pseudo']) ?> · <?= e($p['email']) ?></div>
                        </td>
                        <td class="font-monospace small"><?= e($p['code_court']) ?></td>
                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p['date_inscription'])) ?></td>
                        <td class="text-center">
                            <?php if ($p['present']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Présent</span>
                                <?php if ($presencesOuvertes): ?><?= bouton('absent', (int) $p['id_inscription'], 'Annuler', 'btn-link text-muted p-0 ms-1 small') ?><?php endif; ?>
                            <?php elseif ($presencesOuvertes): ?>
                                <?= bouton('present', (int) $p['id_inscription'], '<i class="bi bi-check2-square"></i> Présent', 'btn-outline-success') ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($modifiable): ?>
                                <?= bouton('retirer', (int) $p['id_inscription'], '<i class="bi bi-person-x"></i>', 'btn-outline-danger', "Retirer @{$p['pseudo']} de l'événement ? Il sera prévenu par mail.") ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="carte">
    <div class="carte-entete">
        <span>Liste d'attente (<?= count($attente) ?>)</span>
        <span class="small fw-normal text-muted"><?= $evt['attente_auto'] ? 'Inscription automatique dès qu\'une place se libère' : 'Inscription manuelle' ?></span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <tbody>
                <?php if (!$attente): ?>
                    <tr><td class="text-center text-muted py-4">Personne en attente.</td></tr>
                <?php endif; ?>
                <?php foreach ($attente as $rang => $p): ?>
                    <tr>
                        <td style="width: 3rem;" class="fw-bold text-muted"><?= $rang + 1 ?></td>
                        <td>
                            <div class="fw-bold">@<?= e($p['pseudo']) ?></div>
                            <div class="small text-muted"><?= e($p['email']) ?> · en attente depuis le <?= date('d/m/Y H:i', strtotime($p['date_inscription'])) ?></div>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($modifiable): ?>
                                <?= bouton('inscrire', (int) $p['id_inscription'], '<i class="bi bi-person-check"></i> Inscrire', 'btn-outline-success') ?>
                                <?= bouton('retirer', (int) $p['id_inscription'], '<i class="bi bi-x-lg"></i>', 'btn-outline-danger', "Retirer @{$p['pseudo']} de la liste d'attente ?") ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
