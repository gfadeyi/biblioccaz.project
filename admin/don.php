<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../moderation_dons_lib.php';
exigerDroit('moderation');

$idDon = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$retour = 'don.php?id=' . $idDon;
$suiviDisponible = suiviModerationDonsDisponible($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    if (!$suiviDisponible) {
        rediriger($retour, 'warning', 'Le suivi des interventions doit être installé avant toute modification.');
    }
    $beneficiaireAttendu = filter_var($_POST['beneficiaire_attendu'] ?? '', FILTER_VALIDATE_INT);
    [$type, $message] = modererDon($pdo, $idDon, (int) $_SESSION['user_id'],
        is_string($_POST['action'] ?? null) ? $_POST['action'] : '',
        is_string($_POST['motif'] ?? null) ? $_POST['motif'] : '',
        is_string($_POST['statut_attendu'] ?? null) ? $_POST['statut_attendu'] : '',
        $beneficiaireAttendu === false || $beneficiaireAttendu === 0 ? null : $beneficiaireAttendu,
        is_string($_POST['reservation_attendue'] ?? null) ? $_POST['reservation_attendue'] : '');
    rediriger($retour, $type, $message);
}

$requete = $pdo->prepare('SELECT d.*, l.couverture, donneur.pseudo AS donneur_pseudo, beneficiaire.pseudo AS beneficiaire_pseudo FROM don d INNER JOIN `user` donneur ON donneur.id = d.id_donneur LEFT JOIN `user` beneficiaire ON beneficiaire.id = d.id_beneficiaire LEFT JOIN livre l ON l.id_livre = d.id_livre WHERE d.id_don = ?');
$requete->execute([$idDon]);
$don = $requete->fetch();
if (!$don) { rediriger('dons.php', 'warning', 'Ce don n’existe pas.'); }
$historique = [];
if ($suiviDisponible) {
    $requete = $pdo->prepare('SELECT m.*, u.pseudo AS moderateur_pseudo, b.pseudo AS beneficiaire_pseudo FROM don_moderation m LEFT JOIN `user` u ON u.id = m.id_moderateur LEFT JOIN `user` b ON b.id = m.id_beneficiaire WHERE m.id_don = ? ORDER BY m.id_moderation DESC');
    $requete->execute([$idDon]);
    $historique = $requete->fetchAll();
}
$requete = $pdo->prepare("SELECT id_conversation, statut, date_creation FROM conversation WHERE type_objet = 'don' AND id_objet = ? ORDER BY id_conversation DESC");
$requete->execute([$idDon]);
$conversations = $requete->fetchAll();
$titrePage = 'Suivi du don #' . $idDon;
$pageActive = 'dons';
include __DIR__ . '/_haut.php';
?>
<a href="dons.php" class="btn btn-outline-secondary mb-3">Retour aux dons</a>
<div class="row g-4">
    <div class="col-lg-7">
        <section class="carte p-4 mb-4">
            <div class="d-flex gap-3 align-items-start mb-3">
                <img src="../<?= e(photoDon($don['photo'], $don['couverture'])) ?>" alt="<?= e(legendePhotoDon($don['photo'], $don['couverture'])) ?>" style="width:80px;max-height:120px;object-fit:contain;flex-shrink:0;">
                <div style="min-width:0;overflow-wrap:anywhere;">
                    <h2 class="h4"><?= e($don['titre']) ?></h2>
                    <p class="text-muted mb-2"><?= e($don['auteur']) ?></p>
                    <?= badgeStatutDon($don) ?>
                </div>
            </div>
            <dl class="row mb-0" style="overflow-wrap:anywhere;">
                <dt class="col-sm-4">Donneur</dt><dd class="col-sm-8">@<?= e($don['donneur_pseudo']) ?></dd>
                <dt class="col-sm-4">Bénéficiaire</dt><dd class="col-sm-8"><?= $don['beneficiaire_pseudo'] ? '@' . e($don['beneficiaire_pseudo']) : 'Aucune réservation' ?></dd>
                <dt class="col-sm-4">État</dt><dd class="col-sm-8"><?= e(DON_ETATS[$don['etat']] ?? $don['etat']) ?></dd>
                <dt class="col-sm-4">Retrait</dt><dd class="col-sm-8"><?= e($don['lieu_retrait']) ?><br>Jusqu’au <?= e(date('d/m/Y', strtotime($don['date_limite']))) ?></dd>
                <?php if ($don['conditions_retrait']): ?><dt class="col-sm-4">Conditions</dt><dd class="col-sm-8"><?= nl2br(e($don['conditions_retrait'])) ?></dd><?php endif; ?>
                <dt class="col-sm-4">Création</dt><dd class="col-sm-8"><?= e(date('d/m/Y à H:i', strtotime($don['date_creation']))) ?></dd>
                <?php foreach (['date_reservation' => 'Réservation', 'date_recuperation' => 'Récupération'] as $champ => $libelle): ?>
                    <?php if ($don[$champ]): ?><dt class="col-sm-4"><?= e($libelle) ?></dt><dd class="col-sm-8"><?= e(date('d/m/Y à H:i', strtotime($don[$champ]))) ?></dd><?php endif; ?>
                <?php endforeach; ?>
            </dl>
            <?php if ($don['description']): ?><hr><h3 class="h6">Description</h3><p class="mb-0" style="overflow-wrap:anywhere;"><?= nl2br(e($don['description'])) ?></p><?php endif; ?>
            <a href="../don_detail.php?id=<?= $idDon ?>" class="btn btn-outline-success mt-3" target="_blank" rel="noopener">Voir l’annonce publique</a>
        </section>
        <section class="carte p-4 mb-4">
            <h2 class="h5">Historique des interventions</h2>
            <?php if (!$suiviDisponible): ?>
                <p class="text-muted mb-0">Le suivi n’est pas encore installé.</p>
            <?php elseif (!$historique): ?>
                <p class="text-muted mb-0">Aucune intervention enregistrée par l’équipe. Les actions antérieures à l’installation du suivi ne figurent pas ici.</p>
            <?php else: ?>
                <?php foreach ($historique as $intervention): ?>
                    <article class="border-top pt-3 mt-3" style="overflow-wrap:anywhere;">
                        <h3 class="h6"><?= $intervention['action'] === 'annuler' ? 'Don annulé' : 'Réservation libérée' ?></h3>
                        <p class="small text-muted mb-2"><?= e(date('d/m/Y à H:i', strtotime($intervention['date_creation']))) ?> · <?= $intervention['moderateur_pseudo'] ? '@' . e($intervention['moderateur_pseudo']) : 'Ancien membre de l’équipe' ?></p>
                        <p class="mb-1"><?= nl2br(e($intervention['motif'])) ?></p>
                        <?php if ($intervention['beneficiaire_pseudo']): ?><p class="small mb-0">Bénéficiaire concerné : @<?= e($intervention['beneficiaire_pseudo']) ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php if ($conversations): ?>
            <section class="carte p-4 mb-4">
                <h2 class="h5">Échanges liés au don</h2>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($conversations as $conversation): ?>
                        <a class="btn btn-outline-secondary" href="conversation.php?id=<?= (int) $conversation['id_conversation'] ?>">Conversation #<?= (int) $conversation['id_conversation'] ?> · <?= e(STATUTS_CONVERSATION[$conversation['statut']][0] ?? $conversation['statut']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <section class="carte p-4">
            <h2 class="h5">Intervenir sur ce don</h2>
            <?php if (!$suiviDisponible): ?>
                <div class="alert alert-warning mb-0">Installez <code>sql/maj_moderation_dons.sql</code> pour activer les interventions avec historique.</div>
            <?php elseif (!in_array($don['statut'], ['disponible', 'reserve'], true)): ?>
                <p class="mb-0 text-muted">Ce don est terminé. Son historique et ses points sont conservés.</p>
            <?php else: ?>
                <p class="small text-muted">Le motif sera conservé dans l’historique et envoyé au donneur et au bénéficiaire éventuel. Les échanges en cours seront fermés.</p>
                <?php if ($don['date_limite'] < date('Y-m-d')): ?><div class="alert alert-warning">La date limite est dépassée. Libérer la réservation ne remettra pas le don dans les offres disponibles.</div><?php endif; ?>
                <form method="POST" data-confirmer="Enregistrer cette intervention et prévenir les personnes concernées ?">
                    <?= champCsrf() ?>
                    <input type="hidden" name="statut_attendu" value="<?= e($don['statut']) ?>">
                    <input type="hidden" name="beneficiaire_attendu" value="<?= (int) $don['id_beneficiaire'] ?>">
                    <input type="hidden" name="reservation_attendue" value="<?= e($don['date_reservation']) ?>">
                    <label for="action" class="form-label">Action</label>
                    <select id="action" name="action" class="form-select mb-3" required>
                        <option value="">Choisir une action</option>
                        <?php if ($don['statut'] === 'reserve'): ?><option value="liberer">Libérer la réservation</option><?php endif; ?>
                        <option value="annuler">Annuler le don</option>
                    </select>
                    <label for="motif" class="form-label">Motif communiqué aux membres</label>
                    <textarea id="motif" name="motif" class="form-control" rows="4" required minlength="10" maxlength="500" aria-describedby="aide-motif"></textarea>
                    <p id="aide-motif" class="form-text">10 à 500 caractères. Expliquez simplement la raison de votre intervention.</p>
                    <button class="btn btn-success w-100">Enregistrer l’intervention</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php include __DIR__ . '/_bas.php'; ?>
