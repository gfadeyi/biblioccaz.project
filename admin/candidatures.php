<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../candidatures_equipe_lib.php';
require_once __DIR__ . '/../messagerie_equipe_lib.php';
exigerDroit('admin');

$disponible = candidaturesEquipeDisponibles($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('candidatures.php');
    $idCandidature = (int) ($_POST['id_candidature'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $message = is_string($_POST['message'] ?? null) ? $_POST['message'] : '';
    $pseudo = is_string($_POST['pseudo'] ?? null) ? trim($_POST['pseudo']) : '';
    $prevenir = !empty($_POST['prevenir']);

    if (!$disponible || !in_array($decision, ['accepter', 'refuser'], true)) {
        rediriger('candidatures.php');
    }

    try {
        $resultat = $decision === 'accepter'
            ? accepterCandidatureEquipe($pdo, $idCandidature, $pseudo, (int) $_SESSION['user_id'], $message)
            : refuserCandidatureEquipe($pdo, $idCandidature, (int) $_SESSION['user_id'], $message);
    } catch (DomainException $e) {
        $_SESSION['saisie_candidature'] = ['id' => $idCandidature, 'pseudo' => $pseudo, 'message' => mb_substr($message, 0, MESSAGE_DECISION_EQUIPE_MAX)];
        rediriger('candidatures.php#candidature-' . $idCandidature, 'danger', $e->getMessage());
    }
    unset($_SESSION['saisie_candidature']);

    $candidature = $resultat['candidature'];
    $libelleRole = mb_strtolower(ROLES_CANDIDATURE_EQUIPE[$candidature['role_vise']] ?? $candidature['role_vise']);
    if ($decision === 'accepter') {
        $compte = $resultat['compte'];
        $journal = $resultat['mode'] === 'creation'
            ? "Candidature acceptée : compte @{$compte['pseudo']} créé (rôle : {$compte['role']})"
            : "Candidature acceptée : @{$compte['pseudo']} devient {$compte['role']}";
        insertLog('ADMIN', $journal);
        $bienvenue = accueillirNouveauMembreEquipe($pdo, (int) $compte['id'], (int) $_SESSION['user_id'], $compte['pseudo'], $libelleRole);
        if (!$bienvenue) {
            insertLog('ERREUR', "Message de bienvenue non déposé pour @{$compte['pseudo']} dans la messagerie de l'équipe");
        }
        $mailEnvoye = $resultat['mode'] === 'promotion' && !$prevenir ? null : prevenirDecisionCandidatureEquipe($pdo, $resultat, true);
        $texte = $resultat['mode'] === 'creation'
            ? "Le compte @{$compte['pseudo']} ($libelleRole) est créé."
            : "@{$compte['pseudo']} est maintenant $libelleRole.";
        if ($mailEnvoye === true) {
            $texte .= $resultat['mode'] === 'promotion' ? ' Le mail de réponse est parti.' : " Une invitation a été envoyée à {$compte['email']} pour choisir son mot de passe.";
        } elseif ($mailEnvoye === false) {
            $texte .= " Le mail n'a pas pu partir : la personne peut utiliser « Mot de passe oublié » avec son adresse email.";
        }
        if ($bienvenue) {
            $texte .= " Un message de bienvenue l'attend dans la messagerie de l'équipe.";
        }
        rediriger('candidatures.php', $mailEnvoye === false ? 'warning' : 'success', $texte);
    }

    $nom = trim($candidature['prenom'] . ' ' . $candidature['nom']);
    insertLog('ADMIN', "Candidature de $nom ({$candidature['role_vise']}) refusée");
    $mailEnvoye = $prevenir ? prevenirDecisionCandidatureEquipe($pdo, $resultat, false) : null;
    $texte = "Candidature de $nom refusée.";
    if ($mailEnvoye !== null) {
        $texte .= $mailEnvoye ? ' Le mail de réponse est parti.' : " Le mail n'a pas pu partir.";
    }
    rediriger('candidatures.php', $mailEnvoye === false ? 'warning' : 'success', $texte);
}

$saisieCandidature = $_SESSION['saisie_candidature'] ?? null;
unset($_SESSION['saisie_candidature']);
$enAttente = $disponible ? candidaturesEquipeEnAttente($pdo) : [];
$historique = $disponible ? historiqueCandidaturesEquipe($pdo) : [];
$libellesStatutCompte = ['actif' => 'actif', 'en_attente_moderateur' => 'en attente de candidature', 'refuse_temporaire' => 'suspendu', 'refuse_definitif' => 'suspendu', 'valide_moderateur' => 'actif'];

$titrePage = 'Candidatures';
$pageActive = 'candidatures';
$sousTitrePage = "Les personnes qui souhaitent rejoindre l'équipe comme modérateur ou gestionnaire.";
include __DIR__ . '/_haut.php';
?>

<?php if (aLeDroit('candidatures_auteur')): ?>
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link active bg-success" href="candidatures.php" aria-current="page">Équipe</a></li>
        <li class="nav-item"><a class="nav-link text-success" href="candidatures_auteur.php">Auteurs<?php if (($compteurs['candidatures_auteur'] ?? 0) > 0): ?> <span class="badge rounded-pill bg-success ms-1"><?= (int) $compteurs['candidatures_auteur'] ?></span><?php endif; ?></a></li>
    </ul>
<?php endif; ?>

<?php if (!$disponible): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Importez <code>sql/maj_candidatures_equipe.sql</code> pour recevoir et traiter les candidatures.</div>
<?php elseif (!$enAttente): ?>
    <div class="carte p-5 text-center mb-4">
        <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
        <p class="text-muted mb-0 mt-2">Aucune candidature en attente.</p>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($enAttente as $c): ?>
        <?php
        $id = (int) $c['id_candidature'];
        $nomComplet = trim($c['prenom'] . ' ' . $c['nom']);
        $saisie = $saisieCandidature && (int) $saisieCandidature['id'] === $id ? $saisieCandidature : null;
        $pseudoPropose = $saisie['pseudo'] ?? $c['pseudo_souhaite'];
        $estMembre = $c['id_user'] !== null;
        $compteSuspendu = $estMembre && in_array($c['statut_compte'], ['refuse_temporaire', 'refuse_definitif'], true);
        ?>
        <div class="col-xl-6">
            <article class="carte p-4 h-100 d-flex flex-column" id="candidature-<?= $id ?>">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge rounded-pill bg-success mb-2"><?= e(ROLES_CANDIDATURE_EQUIPE[$c['role_vise']] ?? $c['role_vise']) ?></span>
                        <h2 class="h5 fw-bold mb-1"><?= e($nomComplet) ?></h2>
                        <p class="small text-muted mb-0 text-break">Pseudo souhaité : <strong>@<?= e($c['pseudo_souhaite']) ?></strong> · <?= e($c['email']) ?></p>
                    </div>
                    <div class="small text-muted">
                        Reçue le <?= date('d/m/Y à H:i', strtotime($c['date_candidature'])) ?>
                        <div class="mt-1">
                            <?php if ($estMembre): ?>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-person-check" aria-hidden="true"></i> Membre @<?= e($c['pseudo_compte']) ?> (<?= e($libellesStatutCompte[$c['statut_compte']] ?? $c['statut_compte']) ?>)</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-person" aria-hidden="true"></i> Visiteur, sans compte</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h3 class="small fw-bold text-uppercase text-muted mb-2">Motivations et informations</h3>
                <?php if ($c['motivations'] !== ''): ?>
                    <blockquote class="candidature-motivations mb-3"><?= nl2br(e($c['motivations'])) ?></blockquote>
                <?php else: ?>
                    <p class="small text-muted fst-italic mb-3">Non enregistrées (candidature envoyée avant la mise à jour).</p>
                <?php endif; ?>

                <?php if ($c['email_pris']): ?>
                    <div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Un compte existe déjà avec cette adresse : demandez à la personne de postuler depuis son compte, ou refusez cette candidature.</div>
                <?php endif; ?>
                <?php if ($compteSuspendu): ?>
                    <div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Ce compte est suspendu : il faut le réactiver avant d'accepter.</div>
                <?php endif; ?>
                <?php if ($estMembre && $c['role_compte'] === 'auteur'): ?>
                    <div class="alert alert-info small py-2"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Ce membre est auteur : en l'acceptant, son compte prend le rôle demandé à la place du rôle auteur.</div>
                <?php endif; ?>

                <form method="POST" class="mt-auto">
                    <?= champCsrf() ?>
                    <input type="hidden" name="id_candidature" value="<?= $id ?>">
                    <label for="pseudo-<?= $id ?>" class="form-label small fw-bold">Pseudo du compte</label>
                    <input type="text" id="pseudo-<?= $id ?>" name="pseudo" class="form-control form-control-sm<?= $c['pseudo_libre'] || $pseudoPropose !== $c['pseudo_souhaite'] ? '' : ' is-invalid' ?>" minlength="3" maxlength="50" value="<?= e($pseudoPropose) ?>" aria-describedby="aide-pseudo-<?= $id ?>" required>
                    <div id="aide-pseudo-<?= $id ?>" class="<?= $c['pseudo_libre'] ? 'form-text' : 'invalid-feedback' ?> mb-2">
                        <?= $c['pseudo_libre'] ? 'Modifiable avant d’accepter. Lettres, chiffres, point, tiret ou tiret bas.' : 'Ce pseudo est déjà pris : choisissez-en un autre avant d’accepter.' ?>
                    </div>
                    <label for="message-<?= $id ?>" class="form-label small fw-bold mt-2">Message au candidat <span class="fw-normal text-muted">(facultatif)</span></label>
                    <textarea id="message-<?= $id ?>" name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="<?= MESSAGE_DECISION_EQUIPE_MAX ?>" placeholder="Ajouté au mail de réponse"><?= e($saisie['message'] ?? '') ?></textarea>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="prevenir" value="1" id="prevenir-<?= $id ?>" checked>
                        <label class="form-check-label small" for="prevenir-<?= $id ?>">Prévenir le candidat par mail <span class="text-muted">(toujours envoyé quand il faut choisir un mot de passe)</span></label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button name="decision" value="accepter" class="btn btn-success fw-bold"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= $estMembre ? 'Accepter et donner le rôle' : 'Accepter et créer le compte' ?></button>
                        <button name="decision" value="refuser" class="btn btn-outline-danger" formnovalidate><i class="bi bi-x-lg" aria-hidden="true"></i> Refuser</button>
                    </div>
                    <p class="small text-muted mt-2 mb-0"><?= $estMembre ? 'Refuser ne change rien au compte du membre.' : 'Refuser ne crée aucun compte.' ?></p>
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
                    <tr><th>Candidat</th><th>Rôle</th><th>Décision</th><th>Par</th><th>Message envoyé</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $h): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e(trim($h['prenom'] . ' ' . $h['nom']) ?: '@' . $h['pseudo_souhaite']) ?></div>
                                <div class="small text-muted">@<?= e($h['pseudo_attribue'] ?? $h['pseudo_souhaite']) ?> · reçue le <?= date('d/m/Y', strtotime($h['date_candidature'])) ?></div>
                            </td>
                            <td class="small"><?= e(ROLES_CANDIDATURE_EQUIPE[$h['role_vise']] ?? $h['role_vise']) ?></td>
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
