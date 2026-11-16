<?php
$autre = autreParticipant($conversation, $idUser);
$conversationOuverte = $conversation['statut'] === 'ouverte';
$autreSuspendu = $autre && in_array($autre['statut'], ['refuse_temporaire', 'refuse_definitif'], true);
$dernierMessage = $messages ? (int) end($messages)['id_message'] : 0;
$idFil = 'fil-' . (int) $conversation['id_conversation'];
$brouillon = '';
if (($_SESSION['brouillon_message'][0] ?? null) === (int) $conversation['id_conversation']) {
    $brouillon = (string) $_SESSION['brouillon_message'][1];
    unset($_SESSION['brouillon_message']);
}
?>
<section class="conversation" id="conversation" aria-labelledby="titre-<?= $idFil ?>"
         data-conversation="<?= (int) $conversation['id_conversation'] ?>" data-dernier="<?= $dernierMessage ?>"
         data-membre="<?= (int) $idUser ?>" data-jour="<?= $messages ? date('Y-m-d', strtotime(end($messages)['date_envoi'])) : '' ?>" data-envoi="<?= ($flashConversation[0] ?? '') === 'envoye' ? 'reussi' : '' ?>">
    <header class="conversation__entete">
        <?php if (!empty($lienRetourListe)): ?>
            <a href="messages" class="conversation__retour" aria-label="Retour à mes messages"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        <?php endif; ?>
        <?= $autre ? avatarMembre($autre['pseudo'], $autre['photo'], 'conversation__avatar') : '' ?>
        <div class="conversation__qui">
            <h2 class="conversation__titre" id="titre-<?= $idFil ?>"><?= $autre ? '@' . htmlspecialchars($autre['pseudo']) : ($conversation['type_objet'] === 'emprunt' ? 'Équipe BIBLIOccaz' : 'Conversation') ?></h2>
            <p class="conversation__objet">
                <span class="conversation__type"><?= iconeDessin(TYPES_CONVERSATION[$conversation['type_objet']][1] ?? 'bulle') ?> <?= htmlspecialchars(TYPES_CONVERSATION[$conversation['type_objet']][0] ?? '') ?></span>
                <?php if ($conversation['objet']['lien'] && !empty($lienVersObjet)): ?>
                    <a href="<?= htmlspecialchars($conversation['objet']['lien']) ?>"><?= htmlspecialchars($conversation['objet']['titre']) ?></a>
                <?php else: ?>
                    <?= htmlspecialchars($conversation['objet']['titre']) ?>
                <?php endif; ?>
            </p>
        </div>
        <?= pastilleStatutConversation($conversation['statut']) ?>
    </header>

    <?php if (!empty($flashConversation) && $flashConversation[0] !== 'envoye'): ?>
        <div class="alert alert-<?= htmlspecialchars($flashConversation[0]) ?> conversation__alerte" role="status"><?= htmlspecialchars($flashConversation[1]) ?></div>
    <?php endif; ?>

    <div class="conversation__fil" id="<?= $idFil ?>" tabindex="0" aria-label="Messages de la conversation">
        <?php $jourPrecedent = ''; ?>
        <?php foreach ($messages as $message): ?>
            <?php $jour = date('Y-m-d', strtotime($message['date_envoi'])); ?>
            <?php if ($jour !== $jourPrecedent): ?>
                <p class="conversation__jour" data-jour="<?= $jour ?>"><span><?= libelleJourMessage($message['date_envoi']) ?></span></p>
                <?php $jourPrecedent = $jour; ?>
            <?php endif; ?>
            <?= htmlMessageConversation($message, $conversation, $idUser, $csrf, $retourConversation) ?>
        <?php endforeach; ?>
    </div>

    <p class="conversation__ecrit" aria-live="polite"><span class="conversation__ecrit-texte" hidden><span class="conversation__points" aria-hidden="true"><span></span><span></span><span></span></span> <?= $autre ? '@' . htmlspecialchars($autre['pseudo']) : 'Votre correspondant' ?> est en train d’écrire…</span></p>

    <div role="status">
        <p class="conversation__nouveaux" hidden>
            <a href="<?= htmlspecialchars($retourConversation === 'objet' && $conversation['objet']['lien'] ? $conversation['objet']['lien'] . '#conversation' : 'messages?id=' . (int) $conversation['id_conversation'] . '#conversation') ?>">
                <i class="bi bi-arrow-down-circle" aria-hidden="true"></i> <span>Nouveau message</span> · afficher
            </a>
        </p>
        <p class="conversation__erreur-envoi" hidden></p>
        <p class="conversation__connexion" hidden>
            <i class="bi bi-wifi-off" aria-hidden="true"></i> Connexion au serveur interrompue. Les nouveaux messages s'afficheront dès son retour.
        </p>
    </div>

    <?php if ($conversationOuverte && !$autreSuspendu): ?>
        <form action="message_action" method="POST" class="conversation__saisie">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="envoyer">
            <input type="hidden" name="id_conversation" value="<?= (int) $conversation['id_conversation'] ?>">
            <input type="hidden" name="retour" value="<?= htmlspecialchars($retourConversation) ?>">
            <label for="saisie-<?= $idFil ?>" class="visually-hidden">Votre message à <?= $autre ? '@' . htmlspecialchars($autre['pseudo']) : "l'équipe" ?></label>
            <textarea id="saisie-<?= $idFil ?>" name="contenu" rows="2" maxlength="<?= MESSAGE_LONGUEUR_MAX ?>" required
                      placeholder="Écrivez à <?= $autre ? '@' . htmlspecialchars($autre['pseudo']) : "l'équipe BIBLIOccaz" ?>…" data-compteur="compteur-<?= $idFil ?>"><?= htmlspecialchars($brouillon) ?></textarea>
            <div class="conversation__envoi">
                <span class="conversation__compteur" id="compteur-<?= $idFil ?>" aria-live="polite"></span>
                <button class="bouton bouton-principal bouton-petit"><i class="bi bi-send" aria-hidden="true"></i> Envoyer</button>
            </div>
        </form>
        <p class="conversation__rappel"><i class="bi bi-shield-lock" aria-hidden="true"></i> Votre adresse email n'est jamais communiquée. Ne partagez pas de coordonnées bancaires.</p>
    <?php else: ?>
        <p class="conversation__fermee">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <?= $autreSuspendu && $conversationOuverte ? "Ce compte est suspendu : vous ne pouvez plus lui écrire." : "Cette conversation est terminée : elle reste consultable, mais on ne peut plus y répondre." ?>
        </p>
    <?php endif; ?>
</section>
