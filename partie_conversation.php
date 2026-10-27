<?php
$autre = autreParticipant($conversation, $idUser);
$conversationOuverte = $conversation['statut'] === 'ouverte';
$autreSuspendu = $autre && in_array($autre['statut'], ['refuse_temporaire', 'refuse_definitif'], true);
$dernierMessage = $messages ? (int) end($messages)['id_message'] : 0;
$idFil = 'fil-' . (int) $conversation['id_conversation'];
?>
<section class="conversation" id="conversation" aria-labelledby="titre-<?= $idFil ?>"
         data-conversation="<?= (int) $conversation['id_conversation'] ?>" data-dernier="<?= $dernierMessage ?>">
    <header class="conversation__entete">
        <?php if (!empty($lienRetourListe)): ?>
            <a href="messages.php" class="conversation__retour" aria-label="Retour à mes messages"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        <?php endif; ?>
        <?= $autre ? avatarMembre($autre['pseudo'], $autre['photo'], 'conversation__avatar') : '' ?>
        <div class="conversation__qui">
            <h2 class="conversation__titre" id="titre-<?= $idFil ?>"><?= $autre ? '@' . htmlspecialchars($autre['pseudo']) : 'Conversation' ?></h2>
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
            <?php
            $jour = date('Y-m-d', strtotime($message['date_envoi']));
            $estMoi = (int) $message['id_auteur'] === $idUser;
            ?>
            <?php if ($jour !== $jourPrecedent): ?>
                <p class="conversation__jour"><span><?= $jour === date('Y-m-d') ? "Aujourd'hui" : ($jour === date('Y-m-d', strtotime('-1 day')) ? 'Hier' : date('d/m/Y', strtotime($jour))) ?></span></p>
                <?php $jourPrecedent = $jour; ?>
            <?php endif; ?>

            <?php if ($message['est_systeme']): ?>
                <p class="message-systeme"><?= iconeDessin('clochette') ?> <?= htmlspecialchars($message['contenu']) ?> <time datetime="<?= date('c', strtotime($message['date_envoi'])) ?>"><?= date('H:i', strtotime($message['date_envoi'])) ?></time></p>
            <?php else: ?>
                <div class="message<?= $estMoi ? ' message--moi' : '' ?><?= $message['est_masque'] ? ' message--masque' : '' ?>" id="message-<?= (int) $message['id_message'] ?>">
                    <?php if (!$estMoi): ?>
                        <?= avatarMembre($message['auteur_pseudo'] ?? '?', $message['auteur_photo'] ?? null, 'message__avatar') ?>
                    <?php endif; ?>
                    <div class="message__corps">
                        <div class="message__bulle">
                            <?php if ($message['est_masque']): ?>
                                <i class="bi bi-eye-slash" aria-hidden="true"></i> Message masqué par l'équipe de modération.
                            <?php else: ?>
                                <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                            <?php endif; ?>
                        </div>
                        <div class="message__pied">
                            <time datetime="<?= date('c', strtotime($message['date_envoi'])) ?>"><?= date('H:i', strtotime($message['date_envoi'])) ?></time>
                            <?php if (!$estMoi && !$message['est_masque'] && $message['id_auteur']): ?>
                                <details class="message__signaler">
                                    <summary>Signaler</summary>
                                    <form action="message_action.php" method="POST" class="signalement-message">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="signaler">
                                        <input type="hidden" name="id_conversation" value="<?= (int) $conversation['id_conversation'] ?>">
                                        <input type="hidden" name="id_message" value="<?= (int) $message['id_message'] ?>">
                                        <input type="hidden" name="retour" value="<?= htmlspecialchars($retourConversation) ?>">
                                        <label for="motif-<?= (int) $message['id_message'] ?>" class="form-label">Pourquoi signalez-vous ce message ?</label>
                                        <select id="motif-<?= (int) $message['id_message'] ?>" name="motif" class="form-select form-select-sm" required>
                                            <option value="">Choisir un motif</option>
                                            <?php foreach (MOTIFS_SIGNALEMENT_MESSAGE as $cle => $libelle): ?>
                                                <option value="<?= $cle ?>"><?= htmlspecialchars($libelle) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="commentaire-<?= (int) $message['id_message'] ?>" class="visually-hidden">Précisions</label>
                                        <textarea id="commentaire-<?= (int) $message['id_message'] ?>" name="commentaire" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Précisions (facultatif)"></textarea>
                                        <button class="bouton bouton-danger bouton-petit">Envoyer le signalement</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <p class="conversation__nouveaux" hidden>
        <a href="<?= htmlspecialchars($retourConversation === 'objet' && $conversation['objet']['lien'] ? $conversation['objet']['lien'] . '#conversation' : 'messages.php?id=' . (int) $conversation['id_conversation'] . '#conversation') ?>">
            <i class="bi bi-arrow-down-circle" aria-hidden="true"></i> <span>Nouveau message</span> · afficher
        </a>
    </p>

    <?php if ($conversationOuverte && !$autreSuspendu): ?>
        <form action="message_action.php" method="POST" class="conversation__saisie">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="envoyer">
            <input type="hidden" name="id_conversation" value="<?= (int) $conversation['id_conversation'] ?>">
            <input type="hidden" name="retour" value="<?= htmlspecialchars($retourConversation) ?>">
            <label for="saisie-<?= $idFil ?>" class="visually-hidden">Votre message à @<?= htmlspecialchars($autre['pseudo'] ?? '') ?></label>
            <textarea id="saisie-<?= $idFil ?>" name="contenu" rows="2" maxlength="<?= MESSAGE_LONGUEUR_MAX ?>" required
                      placeholder="Écrivez à @<?= htmlspecialchars($autre['pseudo'] ?? '') ?>…" data-compteur="compteur-<?= $idFil ?>"></textarea>
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
