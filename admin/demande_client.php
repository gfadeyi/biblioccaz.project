<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../service_client_lib.php';
require_once __DIR__ . '/../messagerie_equipe_lib.php';
exigerDroit('equipe');

$idDemande = (int) ($_GET['id'] ?? 0);
$lienDemande = "demande_client?id=$idDemande";
$idMoi = (int) $_SESSION['user_id'];
if (!serviceClientDisponible($pdo)) {
    rediriger('service_client', 'warning', 'Le service client n’est pas installé.');
}

$roleMoi = (string) $_SESSION['role'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($lienDemande);
    $texte = fn(string $cle) => is_string($_POST[$cle] ?? null) ? $_POST[$cle] : '';
    $action = $texte('action');
    $demandeVisee = demandeClient($pdo, $idDemande);
    if ($demandeVisee && in_array($action, ['repondre', 'prendre', 'resolue', 'en_cours'], true) && !peutTraiterDemandeClient($pdo, $demandeVisee, $idMoi, $roleMoi)) {
        refuserAcces('La catégorie « ' . (SERVICE_CATEGORIES[$demandeVisee['categorie']] ?? 'Autre') . ' » relève d’un autre pôle de l’équipe. Vous pouvez lire la demande et la confier à un collègue.', $lienDemande);
    }
    if ($action === 'repondre') {
        [$type, $message] = repondreDemandeEquipe($pdo, $idDemande, $idMoi, $texte('message'), isset($_POST['resoudre']), $texte('date_maj'));
        if ($type !== 'success') {
            $_SESSION['brouillon_demande_client'] = [$idDemande, mb_substr($texte('message'), 0, SERVICE_MESSAGE_MAX)];
        }
    } elseif ($action === 'attribuer') {
        [$type, $message] = attribuerDemandeClient($pdo, $idDemande, $idMoi, (int) ($_POST['id_assigne'] ?? 0), $texte('note'));
    } elseif ($action === 'prendre') {
        [$type, $message] = attribuerDemandeClient($pdo, $idDemande, $idMoi, $idMoi, '');
    } elseif (in_array($action, ['resolue', 'en_cours'], true)) {
        [$type, $message] = changerStatutDemandeEquipe($pdo, $idDemande, $idMoi, $action);
    } else {
        [$type, $message] = ['warning', 'Action inconnue.'];
    }
    rediriger($lienDemande . '#fil', $type, $message);
}

$demande = demandeClient($pdo, $idDemande);
if (!$demande) {
    rediriger('service_client', 'warning', "Cette demande n'existe pas.");
}
if ((int) $demande['non_lu_equipe']) {
    marquerDemandeLue($pdo, $idDemande, 'equipe');
}
$messages = messagesDemandeClient($pdo, $idDemande);
$collegues = membresEquipeActifs($pdo, $idMoi);
$brouillonSession = $_SESSION['brouillon_demande_client'] ?? null;
$brouillon = is_array($brouillonSession) && $brouillonSession[0] === $idDemande ? (string) $brouillonSession[1] : '';
unset($_SESSION['brouillon_demande_client']);
$commande = null;
if ($demande['id_commande']) {
    $stmt = $pdo->prepare("SELECT id_commande, date_commande, total, statut FROM commande WHERE id_commande = ? AND id_user = ?");
    $stmt->execute([(int) $demande['id_commande'], (int) $demande['id_user']]);
    $commande = $stmt->fetch() ?: null;
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client WHERE id_user = ? AND id_demande <> ?");
$stmt->execute([(int) $demande['id_user'], $idDemande]);
$autresDemandes = (int) $stmt->fetchColumn();
$close = $demande['statut'] === 'resolue';
$peutTraiter = peutTraiterDemandeClient($pdo, $demande, $idMoi, $roleMoi);

$titrePage = $demande['sujet'];
$sousTitrePage = $demande['reference'] . ' · ' . (SERVICE_CATEGORIES[$demande['categorie']] ?? 'Autre') . ' · @' . $demande['pseudo'];
$pageActive = 'service_client';
$filAriane = [[null, 'Ventes'], ['service_client', 'Service client']];
$actionsPage = [['service_client', 'arrow-left', 'Toutes les demandes', 'btn-outline-secondary'], actionDemandeAvisEquipe('demande_client', $idDemande)];
$scriptsPage = '<script src="' . e(urlStatique('javascript/reponses_types.js', '../')) . '" defer></script>';
include __DIR__ . '/_haut.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="carte">
            <div class="carte-entete d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 fw-bold mb-0">Échanges</h2>
                <span class="etiquette<?= $close ? ' etiquette-neutre' : ($demande['statut'] === 'nouvelle' ? ' etiquette-alerte' : '') ?>"><?= e(libelleStatutDemande($demande['statut'])) ?></span>
            </div>
            <div class="carte-corps">
                <ol class="fil-demande" id="fil">
                    <?php foreach ($messages as $message): ?>
                        <?php $date = date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                        <?php if ($message['cote'] === 'systeme'): ?>
                            <li class="fil-demande__systeme"><?= e($message['contenu']) ?> · <?= $date ?></li>
                        <?php else: ?>
                            <li class="fil-demande__message fil-demande__message--<?= e($message['cote']) ?>">
                                <span class="fil-demande__auteur">
                                    <?php if ($message['cote'] === 'interne'): ?><i class="bi bi-lock" aria-hidden="true"></i> Note interne (invisible du client) · <?php endif; ?>
                                    <?= $message['cote'] === 'client' ? 'Client' : 'Équipe' ?> @<?= e((string) ($message['pseudo'] ?? 'compte supprimé')) ?> · <?= $date ?>
                                </span>
                                <p class="mb-0"><?= nl2br(e($message['contenu'])) ?></p>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>

                <?php if (!$peutTraiter): ?>
                    <div class="alert alert-info mt-3 mb-0" data-sav-lecture-seule><i class="bi bi-eye me-1" aria-hidden="true"></i> Lecture seule : la catégorie « <?= e(SERVICE_CATEGORIES[$demande['categorie']] ?? 'Autre') ?> » est traitée par un autre pôle de l’équipe. Confiez la demande au collègue concerné ou demandez l’avis de l’équipe.</div>
                <?php elseif ($close): ?>
                    <p class="alert alert-info mt-3 mb-0" role="status">Demande close : le fil est en lecture seule. Si une correction est nécessaire, utilisez d’abord « Rouvrir la demande » dans Traitement.</p>
                <?php else: ?>
                <form method="POST" class="mt-3 pt-3 border-top">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="repondre">
                    <input type="hidden" name="date_maj" value="<?= e((string) $demande['date_maj']) ?>">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-1">
                        <label for="reponse-equipe" class="form-label fw-bold small mb-0">Répondre au client</label>
                        <div class="reponses-types">
                            <label for="reponse-type" class="visually-hidden">Insérer une réponse type</label>
                            <select id="reponse-type" class="form-select form-select-sm" data-reponses-types>
                                <option value="">Réponse type…</option>
                                <?php foreach (SERVICE_REPONSES_TYPES as $cle => [$libelle, $texte]): ?>
                                    <option value="<?= e($cle) ?>" data-texte="<?= e($texte) ?>"<?= $cle === 'cloture' ? ' data-resoudre="1"' : '' ?>><?= e($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <textarea id="reponse-equipe" name="message" class="form-control mb-2" rows="5" maxlength="<?= SERVICE_MESSAGE_MAX ?>" required><?= e($brouillon) ?></textarea>
                    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="reponse-resoudre" name="resoudre" value="1">
                            <label class="form-check-label small" for="reponse-resoudre">Résoudre et fermer la demande</label>
                        </div>
                        <button type="submit" class="btn btn-vert btn-sm"><i class="bi bi-send" aria-hidden="true"></i> Envoyer la réponse</button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Le client reçoit une notification et un mail. Pour une question à un collègue, utilisez plutôt « Demander l’avis de l’équipe » ou « Confier ».</p>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="carte mb-4">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Traitement</h2></div>
            <div class="carte-corps">
                <p class="small mb-3">Responsable : <strong><?= $demande['pseudo_assigne'] ? '@' . e($demande['pseudo_assigne']) : 'personne' ?></strong></p>
                <?php if ((int) $demande['id_assigne'] !== $idMoi && $peutTraiter): ?>
                    <form method="POST" class="mb-3">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="prendre">
                        <button type="submit" class="btn btn-outline-success btn-sm w-100"><i class="bi bi-person-check" aria-hidden="true"></i> Prendre en charge</button>
                    </form>
                <?php endif; ?>
                <?php if ($collegues): ?>
                    <form method="POST" class="mb-3">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="attribuer">
                        <label for="attribuer-collegue" class="form-label small fw-bold">Confier à un collègue</label>
                        <select id="attribuer-collegue" name="id_assigne" class="form-select form-select-sm mb-2" required>
                            <option value="" disabled selected>Choisir</option>
                            <?php foreach ($collegues as $collegue): ?>
                                <?php $colleguePeutTraiter = rolePossedeDroit($pdo, (string) $collegue['role'], droitCategorieService((string) $demande['categorie']), DROITS); ?>
                                <option value="<?= (int) $collegue['id'] ?>"<?= (int) $collegue['id'] === (int) $demande['id_assigne'] ? ' disabled' : '' ?>>@<?= e($collegue['pseudo']) ?> (<?= e(ROLES_LIBELLES[$collegue['role']] ?? $collegue['role']) ?><?= $colleguePeutTraiter ? ', pôle concerné' : '' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <label for="attribuer-note" class="visually-hidden">Note pour le collègue</label>
                        <textarea id="attribuer-note" name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="500" placeholder="Note interne (facultatif)"></textarea>
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Confier et prévenir dans la messagerie</button>
                    </form>
                <?php endif; ?>
                <?php if ($peutTraiter): ?>
                <form method="POST"<?= $close ? '' : ' data-confirmer="Marquer cette demande comme résolue ? Le client sera prévenu par notification." data-bouton-confirmer="Résoudre"' ?>>
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="<?= $close ? 'en_cours' : 'resolue' ?>">
                    <button type="submit" class="btn <?= $close ? 'btn-outline-secondary' : 'btn-vert' ?> btn-sm w-100"><i class="bi bi-<?= $close ? 'arrow-counterclockwise' : 'check2-circle' ?>" aria-hidden="true"></i> <?= $close ? 'Rouvrir la demande' : 'Résoudre sans répondre' ?></button>
                </form>
                <?php endif; ?>
            </div>
        </section>
        <section class="carte">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Client</h2></div>
            <div class="carte-corps small">
                <p class="mb-1 fw-bold">@<?= e($demande['pseudo']) ?></p>
                <p class="mb-2"><a href="mailto:<?= e($demande['email']) ?>"><?= e($demande['email']) ?></a></p>
                <p class="mb-2">Ouverte le <?= date('d/m/Y à H:i', strtotime($demande['date_creation'])) ?></p>
                <?php if ($commande): ?>
                    <p class="mb-2">Commande <a href="commande?id=<?= (int) $commande['id_commande'] ?>">n° <?= (int) $commande['id_commande'] ?></a> du <?= date('d/m/Y', strtotime($commande['date_commande'])) ?> · <?= formaterPrix((float) $commande['total']) ?><?= $commande['statut'] === 'annulee' ? ' (annulée)' : '' ?></p>
                <?php endif; ?>
                <?php if ($autresDemandes): ?>
                    <p class="mb-0"><a href="service_client?<?= e(http_build_query(['statut' => 'toutes', 'q' => $demande['pseudo']])) ?>"><?= $autresDemandes ?> autre<?= $autresDemandes > 1 ? 's' : '' ?> demande<?= $autresDemandes > 1 ? 's' : '' ?></a></p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
