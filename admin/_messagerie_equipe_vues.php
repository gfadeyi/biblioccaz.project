<?php
if (!function_exists('estEquipe')) {
    http_response_code(404);
    exit();
}

function vueMessageEquipe(array $message, int $moi, bool $dernier = false): string
{
    ob_start();
    ?>
    <article class="message-equipe<?= (int) $message['id_auteur'] === $moi ? ' message-equipe--moi' : '' ?>" id="<?= $dernier ? 'dernier' : 'message-' . (int) $message['id_message'] ?>" data-message="<?= (int) $message['id_message'] ?>">
        <header>
            <strong>@<?= e($message['pseudo'] ?? 'ancien membre') ?></strong>
            <?php if (!empty($message['role'])): ?><span class="role-message"><?= e(ROLES_LIBELLES[$message['role']] ?? '') ?></span><?php endif; ?>
            <time datetime="<?= e(date('c', strtotime($message['date_envoi']))) ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></time>
        </header>
        <div class="message-equipe__texte"><?= afficherMessageEquipe($message['contenu']) ?></div>
    </article>
    <?php
    return trim(ob_get_clean());
}

function vueListeConversationsEquipe(array $conversations, ?int $idActif): string
{
    ob_start();
    foreach ($conversations as $c):
        $actif = $idActif !== null && (int) $c['id_conversation'] === $idActif;
        ?>
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
        <?php
    endforeach;
    return trim(ob_get_clean());
}
