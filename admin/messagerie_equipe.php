<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../messagerie_equipe_lib.php';
exigerDroit('equipe');

$moi = (int) $_SESSION['user_id'];
$disponible = messagerieEquipeDisponible($pdo);

if ($disponible && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $idConversation = max(0, (int) ($_POST['id_conversation'] ?? 0));
    exigerPost('messagerie_equipe.php' . ($idConversation ? '?c=' . $idConversation : ''));
    try {
        if ($action === 'envoyer') {
            envoyerMessageInterneEquipe($pdo, $idConversation, $moi, (string) ($_POST['contenu'] ?? ''));
            rediriger('messagerie_equipe.php?c=' . $idConversation . '#dernier');
        }
        if ($action === 'nouvelle') {
            $destinataires = is_array($_POST['destinataires'] ?? null) ? $_POST['destinataires'] : [];
            $contenu = normaliserMessageEquipe((string) ($_POST['contenu'] ?? ''));
            $destinataires = array_values(array_unique(array_filter(array_map('intval', $destinataires), fn($id) => $id > 0 && $id !== $moi)));
            if (!$destinataires) {
                $idConversation = rejoindreFilEquipe($pdo, $moi);
            } elseif (count($destinataires) === 1) {
                $idConversation = ouvrirConversationPriveeEquipe($pdo, $moi, $destinataires[0]);
            } else {
                $idConversation = creerGroupeEquipe($pdo, $moi, $destinataires, (string) ($_POST['titre'] ?? ''));
            }
            envoyerMessageInterneEquipe($pdo, $idConversation, $moi, $contenu);
            rediriger('messagerie_equipe.php?c=' . $idConversation . '#dernier', 'success', 'Message envoyé.');
        }
        if ($action === 'tout_lu') {
            marquerToutEquipeLu($pdo, $moi);
            rediriger('messagerie_equipe.php', 'success', 'Toutes les conversations sont marquées comme lues.');
        }
        rediriger('messagerie_equipe.php', 'warning', 'Action inconnue.');
    } catch (DomainException $erreur) {
        if ($action === 'nouvelle') {
            $_SESSION['brouillon_equipe'] = ['contenu' => mb_substr((string) ($_POST['contenu'] ?? ''), 0, LONGUEUR_MESSAGE_EQUIPE), 'titre' => mb_substr((string) ($_POST['titre'] ?? ''), 0, 120)];
            rediriger('messagerie_equipe.php?nouveau=1', 'warning', $erreur->getMessage());
        }
        $_SESSION['brouillon_equipe'] = ['contenu' => mb_substr((string) ($_POST['contenu'] ?? ''), 0, LONGUEUR_MESSAGE_EQUIPE)];
        rediriger('messagerie_equipe.php' . ($idConversation ? '?c=' . $idConversation : ''), 'warning', $erreur->getMessage());
    }
}

$brouillon = $_SESSION['brouillon_equipe'] ?? [];
unset($_SESSION['brouillon_equipe']);

$conversations = [];
$conversation = null;
$messages = [];
$participants = [];
$resultats = [];
$recherche = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$nouveau = isset($_GET['nouveau']) || isset($_GET['demande']);
$demande = null;
$destinatairePropose = max(0, (int) ($_GET['avec'] ?? 0));
$membres = [];

if ($disponible) {
    $conversations = conversationsEquipe($pdo, $moi);
    $idOuvert = max(0, (int) ($_GET['c'] ?? 0));
    if ($idOuvert) {
        $conversation = conversationEquipe($pdo, $idOuvert, $moi);
        if (!$conversation) {
            rediriger('messagerie_equipe.php', 'warning', 'Conversation introuvable.');
        }
        $messages = messagesConversationEquipe($pdo, $idOuvert);
        $participants = participantsConversationEquipe($pdo, $idOuvert);
        marquerConversationEquipeLue($pdo, $idOuvert, $moi);
        $conversations = conversationsEquipe($pdo, $moi);
    }
    if ($recherche !== '') {
        $resultats = rechercherMessagesEquipe($pdo, $moi, $recherche);
    }
    if ($nouveau) {
        $membres = membresEquipeActifs($pdo, $moi);
        $demande = demandeAvisEquipe((string) ($_GET['demande'] ?? ''), (int) ($_GET['id'] ?? 0));
        if ($demande && !aLeDroit($demande['droit'])) {
            $demande = null;
        }
    }
}

$idsConversations = array_column($conversations, 'nom', 'id_conversation');
$totalNonLus = array_sum(array_column($conversations, 'nb_non_lus'));
$panneauOuvert = $conversation || $nouveau || $recherche !== '';

$titrePage = 'Messagerie de l’équipe';
$sousTitrePage = 'Réservée aux administrateurs, gestionnaires et modérateurs. Les membres du site n’y ont pas accès.';
$pageActive = 'messagerie_equipe';
$actionsPage = $disponible ? [['messagerie_equipe.php?nouveau=1', 'pencil-square', 'Nouveau message', 'btn-vert']] : [];
include __DIR__ . '/_haut.php';
?>

<?php if (!$disponible): ?>
    <div class="alert alert-warning">Installez <code>sql/maj_messagerie_equipe.sql</code> pour activer la messagerie de l’équipe.</div>
<?php else: ?>
<div class="messagerie-equipe<?= $panneauOuvert ? ' panneau-ouvert' : '' ?>">
    <aside class="carte messagerie-equipe__liste" aria-label="Conversations">
        <form method="GET" class="carte-corps border-bottom" role="search">
            <label for="recherche-equipe" class="visually-hidden">Rechercher dans les messages</label>
            <div class="input-group input-group-sm">
                <input type="search" id="recherche-equipe" name="q" value="<?= e($recherche) ?>" class="form-control" placeholder="Rechercher un message ou un membre" maxlength="80">
                <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
            </div>
        </form>
        <div class="d-flex justify-content-between align-items-center px-3 pt-3 pb-1 small">
            <span class="text-muted"><?= count($conversations) ?> conversation<?= count($conversations) > 1 ? 's' : '' ?></span>
            <?php if ($totalNonLus > 0): ?>
                <form method="POST">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="tout_lu">
                    <button class="btn btn-link btn-sm p-0">Tout marquer comme lu</button>
                </form>
            <?php endif; ?>
        </div>
        <ul class="liste-conversations-equipe">
            <?php foreach ($conversations as $c): ?>
                <?php $actif = $conversation && (int) $conversation['id_conversation'] === (int) $c['id_conversation']; ?>
                <li>
                    <a href="messagerie_equipe.php?c=<?= (int) $c['id_conversation'] ?>#dernier" class="conversation-equipe<?= $actif ? ' actif' : '' ?><?= $c['nb_non_lus'] > 0 ? ' non-lu' : '' ?>"<?= $actif ? ' aria-current="true"' : '' ?>>
                        <span class="conversation-equipe__icone" aria-hidden="true"><i class="bi bi-<?= $c['type'] === 'equipe' ? 'people-fill' : ($c['type'] === 'groupe' ? 'people' : 'person') ?>"></i></span>
                        <span class="conversation-equipe__texte">
                            <strong><?= e($c['nom']) ?></strong>
                            <small><?= $c['dernier_contenu'] !== null ? e(mb_strimwidth((string) $c['dernier_contenu'], 0, 70, '…')) : 'Aucun message' ?></small>
                        </span>
                        <?php if ($c['nb_non_lus'] > 0): ?>
                            <span class="badge rounded-pill text-bg-success"><?= (int) $c['nb_non_lus'] ?><span class="visually-hidden"> non lu<?= $c['nb_non_lus'] > 1 ? 's' : '' ?></span></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <section class="carte messagerie-equipe__panneau" aria-live="polite">
        <?php if ($panneauOuvert): ?>
            <a href="messagerie_equipe.php" class="retour-conversations d-lg-none"><i class="bi bi-arrow-left" aria-hidden="true"></i> Conversations</a>
        <?php endif; ?>

        <?php if ($recherche !== ''): ?>
            <div class="carte-entete"><h2>Résultats pour « <?= e($recherche) ?> »</h2><span class="text-muted small"><?= count($resultats) ?> message<?= count($resultats) > 1 ? 's' : '' ?></span></div>
            <div class="carte-corps">
                <?php if (mb_strlen($recherche) < 2): ?>
                    <p class="text-muted mb-0">Saisissez au moins deux caractères.</p>
                <?php elseif (!$resultats): ?>
                    <p class="text-muted mb-0">Aucun message ne correspond.</p>
                <?php endif; ?>
                <?php foreach ($resultats as $r): ?>
                    <a href="messagerie_equipe.php?c=<?= (int) $r['id_conversation'] ?>#message-<?= (int) $r['id_message'] ?>" class="resultat-equipe">
                        <span class="small text-muted"><?= e($idsConversations[$r['id_conversation']] ?? 'Conversation') ?> · @<?= e($r['pseudo'] ?? 'ancien membre') ?> · <?= date('d/m/Y H:i', strtotime($r['date_envoi'])) ?></span>
                        <span><?= e(mb_strimwidth($r['contenu'], 0, 180, '…')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($nouveau): ?>
            <div class="carte-entete"><h2>Nouveau message</h2></div>
            <form method="POST" class="carte-corps">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="nouvelle">
                <fieldset class="mb-3">
                    <legend class="form-label small fw-bold">Destinataires</legend>
                    <p class="small text-muted mb-2">Sans destinataire coché, le message part dans le fil « Équipe », lu par toute l’équipe. Un destinataire : conversation privée. Plusieurs : groupe.</p>
                    <div class="destinataires-equipe">
                        <?php foreach ($membres as $membre): ?>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="destinataires[]" value="<?= (int) $membre['id'] ?>"<?= (int) $membre['id'] === $destinatairePropose ? ' checked' : '' ?>>
                                <span class="form-check-label">@<?= e($membre['pseudo']) ?> <small class="text-muted"><?= e(ROLES_LIBELLES[$membre['role']] ?? '') ?></small></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if (!$membres): ?><p class="small text-muted mb-0">Vous êtes seul dans l’équipe pour le moment.</p><?php endif; ?>
                    </div>
                </fieldset>
                <div class="mb-3">
                    <label for="titre-groupe" class="form-label small fw-bold">Titre du groupe <span class="fw-normal text-muted">(facultatif, plusieurs destinataires)</span></label>
                    <input type="text" id="titre-groupe" name="titre" class="form-control form-control-sm" maxlength="120" value="<?= e($brouillon['titre'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="contenu-nouveau" class="form-label small fw-bold">Message</label>
                    <textarea id="contenu-nouveau" name="contenu" class="form-control" rows="6" maxlength="<?= LONGUEUR_MESSAGE_EQUIPE ?>" required><?= e($brouillon['contenu'] ?? ($demande['texte'] ?? '')) ?></textarea>
                    <div class="form-text"><?= LONGUEUR_MESSAGE_EQUIPE ?> caractères maximum. Les liens vers les pages du back-office deviennent cliquables.</div>
                </div>
                <button class="btn btn-vert"><i class="bi bi-send" aria-hidden="true"></i> Envoyer</button>
            </form>

        <?php elseif ($conversation): ?>
            <?php $ecriture = peutEcrireConversationEquipe($pdo, $conversation, $moi); ?>
            <div class="carte-entete">
                <h2><?= e($conversation['nom']) ?></h2>
                <span class="text-muted small"><?= $conversation['type'] === 'equipe' ? 'Toute l’équipe' : e(implode(', ', array_map(fn($p) => '@' . $p['pseudo'] . ($p['actif'] ? '' : ' (ancien membre)'), $participants))) ?></span>
            </div>
            <div class="carte-corps fil-equipe">
                <?php if (!$messages): ?>
                    <p class="text-muted mb-0">Aucun message pour l’instant.</p>
                <?php endif; ?>
                <?php foreach ($messages as $index => $message): ?>
                    <article class="message-equipe<?= (int) $message['id_auteur'] === $moi ? ' message-equipe--moi' : '' ?>" id="<?= $index === count($messages) - 1 ? 'dernier' : 'message-' . (int) $message['id_message'] ?>">
                        <header>
                            <strong>@<?= e($message['pseudo'] ?? 'ancien membre') ?></strong>
                            <?php if (!empty($message['role'])): ?><span class="role-message"><?= e(ROLES_LIBELLES[$message['role']] ?? '') ?></span><?php endif; ?>
                            <time datetime="<?= e(date('c', strtotime($message['date_envoi']))) ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></time>
                        </header>
                        <div class="message-equipe__texte"><?= afficherMessageEquipe($message['contenu']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($ecriture): ?>
                <form method="POST" class="carte-corps border-top">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="envoyer">
                    <input type="hidden" name="id_conversation" value="<?= (int) $conversation['id_conversation'] ?>">
                    <label for="contenu-reponse" class="form-label small fw-bold">Votre message</label>
                    <textarea id="contenu-reponse" name="contenu" class="form-control mb-2" rows="3" maxlength="<?= LONGUEUR_MESSAGE_EQUIPE ?>" required><?= e($brouillon['contenu'] ?? '') ?></textarea>
                    <button class="btn btn-vert btn-sm"><i class="bi bi-send" aria-hidden="true"></i> Envoyer</button>
                </form>
            <?php else: ?>
                <p class="carte-corps border-top text-muted small mb-0"><i class="bi bi-lock" aria-hidden="true"></i> Votre interlocuteur ne fait plus partie de l’équipe : conversation en lecture seule.</p>
            <?php endif; ?>

        <?php else: ?>
            <div class="etat-vide">
                <div class="icone-vide"><i class="bi bi-chat-left-text"></i></div>
                <strong>Choisissez une conversation</strong>
                Le fil « Équipe » est partagé par toute l’équipe ; les conversations privées et les groupes ne sont lus que par leurs participants.
            </div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
