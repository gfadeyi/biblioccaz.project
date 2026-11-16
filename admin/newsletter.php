<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../notifications_lib.php';
require_once __DIR__ . '/../newsletter_consentement_lib.php';
exigerDroit('admin');
$consentementDisponible = consentementNewsletterDisponible($pdo);

$onglet = in_array($_GET['onglet'] ?? '', ['desinscrits', 'historique'], true) ? $_GET['onglet'] : 'inscrits';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('newsletter');
    $action = $_POST['action'] ?? '';

    if ($action === 'desabonner') {
        $id = (int) ($_POST['id'] ?? 0);
        $table = ($_POST['origine'] ?? '') === 'membre' ? 'user' : 'newsletter_abonne';
        $colonne = $table === 'user' ? 'id' : 'id_abonne';
        $q = $pdo->prepare("SELECT email FROM $table WHERE $colonne = ?");
        $q->execute([$id]);
        $email = $q->fetchColumn();
        if (!$email) { rediriger('newsletter', 'warning', 'Abonné introuvable.'); }
        try { enregistrerPreferenceNewsletter($pdo, $email, false, 'admin'); }
        catch (DomainException $e) { rediriger('newsletter', 'warning', $e->getMessage()); }
        insertLog('ADMIN', 'Désinscription newsletter effectuée.');
        rediriger('newsletter?onglet=' . $onglet, 'success', "Abonné retiré de la newsletter.");
    }

    if ($action === 'envoyer') {
        $sujet = is_string($_POST['sujet'] ?? null) ? trim($_POST['sujet']) : '';
        $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
        if ($sujet === '' || $message === '' || mb_strlen($sujet) > 150 || mb_strlen($message) > 20000) {
            rediriger('newsletter', 'danger', "Le sujet doit contenir 1 à 150 caractères et le message 1 à 20 000 caractères.");
        }
        if (!$consentementDisponible) { rediriger('newsletter', 'warning', 'L’envoi est momentanément indisponible.'); }
        $q = $pdo->prepare('SELECT email FROM newsletter_consentement WHERE actif = 1 AND date_confirmation IS NOT NULL ORDER BY email');
        $q->execute();
        $abonnes = $q->fetchAll(PDO::FETCH_COLUMN);
        if (!$abonnes) {
            rediriger('newsletter', 'warning', "Aucun abonné confirmé.");
        }

        $resultat = envoyerEditionNewsletter($pdo, $abonnes, $sujet, $message);
        insertLog('ADMIN', 'Campagne newsletter : ' . $resultat['envoyes'] . ' envois, ' . $resultat['echecs'] . ' échecs.');
        rediriger('newsletter', $resultat['echecs'] ? 'warning' : 'success', $resultat['envoyes'] . ' message(s) envoyé(s), ' . $resultat['echecs'] . ' échec(s). Les envois réussis ne sont pas relancés automatiquement.');
    }
    rediriger('newsletter');
}

$abonnes = $pdo->query("
    SELECT 'membre' AS origine, id, pseudo, role, email, is_newsletter AS actif, date_newsletter AS depuis
    FROM user WHERE date_newsletter IS NOT NULL OR is_newsletter = 1
    UNION ALL
    SELECT 'externe', id_abonne, NULL, NULL, email, actif, COALESCE(date_desinscription, date_inscription)
    FROM newsletter_abonne
    ORDER BY depuis DESC")->fetchAll();
$actifs = array_filter($abonnes, fn($a) => (int) $a['actif'] === 1);
$nombreDestinataires = count(array_unique(array_map('mb_strtolower', array_column($actifs, 'email'))));
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
$preuves = [];
$nombreAReconfirmer = 0;
if ($consentementDisponible) {
    $q = $pdo->prepare('SELECT email, actif, date_confirmation, source FROM newsletter_consentement');
    $q->execute();
    $destinataires = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $preuve) {
        $preuves[mb_strtolower($preuve['email'])] = $preuve;
        if ((int) $preuve['actif'] === 1 && empty($preuve['date_confirmation'])) { $nombreAReconfirmer++; }
        if ((int) $preuve['actif'] === 1 && !empty($preuve['date_confirmation'])) { $destinataires[] = $preuve['email']; }
    }
    $nombreDestinataires = count($destinataires);
}
$sources = ['site' => 'Pied de page du site', 'profil' => 'Profil vérifié', 'admin' => 'Back-office', 'reprise' => 'Historique, à reconfirmer', 'confirmation' => 'Confirmation par email', 'lien_email' => 'Lien de désabonnement'];

$titrePage = 'Newsletter';
$pageActive = 'newsletter';
include __DIR__ . '/_haut.php';
?>

<div class="row g-4">
    <div class="col-xl-5">
        <form method="POST" class="carte p-4" data-confirmer="Envoyer cette newsletter à <?= $nombreDestinataires ?> abonné(s) ?">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="envoyer">
            <h2 class="h6 fw-bold"><i class="bi bi-send me-2 text-success"></i>Nouvelle campagne</h2>
            <p class="small text-muted">Un email individuel avec son lien de désabonnement est envoyé aux <strong><?= $nombreDestinataires ?></strong> abonnés confirmés.<?php if ($nombreAReconfirmer): ?> Les <?= $nombreAReconfirmer ?> abonnés historiques à reconfirmer ne le reçoivent pas et aucune relance ne leur est envoyée.<?php endif; ?></p>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="sujet">Sujet</label>
                <input type="text" id="sujet" name="sujet" class="form-control" maxlength="150" value="Des nouvelles fraîches de BIBLIOccaz ! 📚" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="8" maxlength="20000" required>Bonjour cher lecteur !

De nouveaux ouvrages viennent d'être ajoutés au catalogue par nos auteurs et notre communauté. N'attendez plus pour venir les découvrir !

L'équipe BIBLIOccaz</textarea>
            </div>
            <button class="btn btn-vert fw-bold" <?= $nombreDestinataires ? '' : 'disabled' ?>><i class="bi bi-rocket-takeoff me-1"></i> Envoyer</button>
        </form>
    </div>

    <div class="col-xl-7">
        <div class="row g-3 mb-3">
            <?php foreach ([
                ['envelope-check', 'Abonnés confirmés', $nombreDestinataires],
                ['envelope-exclamation', 'À reconfirmer', $nombreAReconfirmer],
                ['person-plus', 'Nouveaux (30 jours)', $nouveaux30],
                ['envelope-x', 'Désinscrits', count($desinscrits)],
            ] as [$icone, $libelle, $valeur]): ?>
                <div class="col-6 col-md-3">
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
                                        <?php $preuve = $preuves[mb_strtolower($a['email'])] ?? null; ?>
                                        <small class="text-muted"><?= !empty($preuve['date_confirmation']) ? 'Choix confirmé le ' . e(date('d/m/Y H:i', strtotime($preuve['date_confirmation']))) : '<span class="badge bg-light text-success border border-success">À reconfirmer</span> ne reçoit pas les campagnes' ?></small>
                                    </td>
                                    <td class="text-muted"><?= !empty($a['depuis']) ? e(date('d/m/Y H:i', strtotime($a['depuis']))) : 'Date non renseignée' ?></td>
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
