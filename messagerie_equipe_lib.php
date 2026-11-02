<?php

const ROLES_MESSAGERIE_EQUIPE = ['admin', 'gestionnaire', 'moderateur'];
const LONGUEUR_MESSAGE_EQUIPE = 2000;
const ENVOIS_EQUIPE_PAR_HEURE = 60;
const MEMBRES_GROUPE_EQUIPE_MAX = 12;
const DEMANDES_AVIS_EQUIPE = [
    'livre'        => ['moderation_livres.php', 'la fiche livre', 'moderation'],
    'annonce'      => ['annonce.php?id=', "l'annonce", 'moderation'],
    'don'          => ['don.php?id=', 'le don', 'moderation'],
    'avis'         => ['moderation_avis.php', "l'avis signalé", 'moderation'],
    'conversation' => ['conversation.php?id=', 'la conversation', 'moderation'],
    'vente'        => ['ventes.php', 'la vente entre lecteurs', 'moderation'],
    'evenement'    => ['evenement.php?id=', "la proposition d'événement", 'evenements'],
];
const PAGES_LIENS_EQUIPE = ['moderation_livres.php', 'annonce.php', 'don.php', 'moderation_avis.php', 'conversation.php', 'ventes.php', 'evenement.php', 'commande.php', 'emprunts.php', 'livre.php'];

function messagerieEquipeDisponible(PDO $pdo): bool
{
    static $disponible = null;
    if ($disponible === null) {
        try {
            $pdo->query('SELECT 1 FROM equipe_conversation LIMIT 1');
            $pdo->query('SELECT 1 FROM equipe_participant LIMIT 1');
            $pdo->query('SELECT 1 FROM equipe_message LIMIT 1');
            $disponible = true;
        } catch (PDOException $e) {
            $disponible = false;
        }
    }
    return $disponible;
}

function conditionMembreEquipeActif(string $alias = 'u'): string
{
    return "$alias.role IN ('" . implode("', '", ROLES_MESSAGERIE_EQUIPE) . "') AND $alias.statut IN ('actif', 'valide_moderateur') AND $alias.email_verifie = 1";
}

function membresEquipeActifs(PDO $pdo, int $sauf = 0): array
{
    $stmt = $pdo->prepare('SELECT u.id, u.pseudo, u.prenom, u.nom, u.role FROM user u WHERE ' . conditionMembreEquipeActif() . " AND u.id <> ? ORDER BY FIELD(u.role, 'admin', 'gestionnaire', 'moderateur'), u.pseudo");
    $stmt->execute([$sauf]);
    return $stmt->fetchAll();
}

function estMembreEquipeActif(PDO $pdo, int $idUser): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM user u WHERE u.id = ? AND ' . conditionMembreEquipeActif());
    $stmt->execute([$idUser]);
    return (bool) $stmt->fetchColumn();
}

function idFilEquipe(PDO $pdo): int
{
    $pdo->exec("INSERT IGNORE INTO equipe_conversation (type, titre, cle_unique) VALUES ('equipe', 'Équipe', 'equipe')");
    return (int) $pdo->query("SELECT id_conversation FROM equipe_conversation WHERE cle_unique = 'equipe'")->fetchColumn();
}

function rejoindreFilEquipe(PDO $pdo, int $idUser): int
{
    $idFil = idFilEquipe($pdo);
    $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user, id_dernier_lu)
                   SELECT ?, ?, COALESCE(MAX(id_message), 0) FROM equipe_message WHERE id_conversation = ?')
        ->execute([$idFil, $idUser, $idFil]);
    return $idFil;
}

function nombreNonLusEquipe(PDO $pdo, int $idUser): int
{
    if (!messagerieEquipeDisponible($pdo)) {
        return 0;
    }
    try {
        rejoindreFilEquipe($pdo, $idUser);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipe_participant p
                               INNER JOIN equipe_message m ON m.id_conversation = p.id_conversation AND m.id_message > p.id_dernier_lu
                               WHERE p.id_user = ? AND (m.id_auteur IS NULL OR m.id_auteur <> ?)');
        $stmt->execute([$idUser, $idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function conversationsEquipe(PDO $pdo, int $idUser): array
{
    rejoindreFilEquipe($pdo, $idUser);
    $stmt = $pdo->prepare("
        SELECT c.id_conversation, c.type, c.titre, c.date_creation, c.date_dernier_message,
               (SELECT COUNT(*) FROM equipe_message m WHERE m.id_conversation = c.id_conversation AND m.id_message > p.id_dernier_lu
                  AND (m.id_auteur IS NULL OR m.id_auteur <> ?)) AS nb_non_lus,
               (SELECT m.contenu FROM equipe_message m WHERE m.id_conversation = c.id_conversation ORDER BY m.id_message DESC LIMIT 1) AS dernier_contenu,
               (SELECT GROUP_CONCAT(u.pseudo ORDER BY u.pseudo SEPARATOR ', ') FROM equipe_participant autre INNER JOIN user u ON u.id = autre.id_user
                 WHERE autre.id_conversation = c.id_conversation AND autre.id_user <> ?) AS autres
        FROM equipe_conversation c
        INNER JOIN equipe_participant p ON p.id_conversation = c.id_conversation AND p.id_user = ?
        ORDER BY c.type = 'equipe' DESC, COALESCE(c.date_dernier_message, c.date_creation) DESC");
    $stmt->execute([$idUser, $idUser, $idUser]);
    return array_map(fn($c) => $c + ['nom' => nomConversationEquipe($c)], $stmt->fetchAll());
}

function nomConversationEquipe(array $conversation): string
{
    if ($conversation['type'] === 'equipe') {
        return 'Équipe';
    }
    if ($conversation['type'] === 'groupe' && trim((string) $conversation['titre']) !== '') {
        return (string) $conversation['titre'];
    }
    return (string) ($conversation['autres'] ?: 'Conversation');
}

function conversationEquipe(PDO $pdo, int $idConversation, int $idUser): ?array
{
    foreach (conversationsEquipe($pdo, $idUser) as $conversation) {
        if ((int) $conversation['id_conversation'] === $idConversation) {
            return $conversation;
        }
    }
    return null;
}

function participantsConversationEquipe(PDO $pdo, int $idConversation): array
{
    $stmt = $pdo->prepare('SELECT u.id, u.pseudo, u.role, (' . conditionMembreEquipeActif() . ') AS actif
                           FROM equipe_participant p INNER JOIN user u ON u.id = p.id_user
                           WHERE p.id_conversation = ? ORDER BY u.pseudo');
    $stmt->execute([$idConversation]);
    return $stmt->fetchAll();
}

function messagesConversationEquipe(PDO $pdo, int $idConversation, int $limite = 200): array
{
    $stmt = $pdo->prepare('SELECT * FROM (
                               SELECT m.id_message, m.id_auteur, m.contenu, m.date_envoi, u.pseudo, u.role
                               FROM equipe_message m LEFT JOIN user u ON u.id = m.id_auteur
                               WHERE m.id_conversation = ? ORDER BY m.id_message DESC LIMIT ' . max(1, $limite) . '
                           ) derniers ORDER BY id_message');
    $stmt->execute([$idConversation]);
    return $stmt->fetchAll();
}

function marquerConversationEquipeLue(PDO $pdo, int $idConversation, int $idUser): void
{
    $pdo->prepare('UPDATE equipe_participant p
                   SET p.id_dernier_lu = GREATEST(p.id_dernier_lu, (SELECT COALESCE(MAX(m.id_message), 0) FROM equipe_message m WHERE m.id_conversation = ?))
                   WHERE p.id_conversation = ? AND p.id_user = ?')
        ->execute([$idConversation, $idConversation, $idUser]);
}

function marquerToutEquipeLu(PDO $pdo, int $idUser): void
{
    $pdo->prepare('UPDATE equipe_participant p
                   SET p.id_dernier_lu = GREATEST(p.id_dernier_lu, (SELECT COALESCE(MAX(m.id_message), 0) FROM equipe_message m WHERE m.id_conversation = p.id_conversation))
                   WHERE p.id_user = ?')
        ->execute([$idUser]);
}

function ouvrirConversationPriveeEquipe(PDO $pdo, int $idUser, int $idAutre): int
{
    if ($idAutre === $idUser || !estMembreEquipeActif($pdo, $idAutre)) {
        throw new DomainException('Ce destinataire ne fait pas partie de l’équipe.');
    }
    $cle = 'privee-' . min($idUser, $idAutre) . '-' . max($idUser, $idAutre);
    $pdo->prepare("INSERT IGNORE INTO equipe_conversation (type, cle_unique, id_createur) VALUES ('privee', ?, ?)")->execute([$cle, $idUser]);
    $stmt = $pdo->prepare('SELECT id_conversation FROM equipe_conversation WHERE cle_unique = ?');
    $stmt->execute([$cle]);
    $idConversation = (int) $stmt->fetchColumn();
    $ajout = $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user) VALUES (?, ?)');
    $ajout->execute([$idConversation, $idUser]);
    $ajout->execute([$idConversation, $idAutre]);
    return $idConversation;
}

function creerGroupeEquipe(PDO $pdo, int $idUser, array $membres, string $titre): int
{
    $membres = array_values(array_unique(array_filter(array_map('intval', $membres), fn($id) => $id > 0 && $id !== $idUser)));
    if (count($membres) < 2 || count($membres) > MEMBRES_GROUPE_EQUIPE_MAX) {
        throw new DomainException('Un groupe réunit de 2 à ' . MEMBRES_GROUPE_EQUIPE_MAX . ' autres membres de l’équipe.');
    }
    foreach ($membres as $idMembre) {
        if (!estMembreEquipeActif($pdo, $idMembre)) {
            throw new DomainException('Un des destinataires ne fait pas partie de l’équipe.');
        }
    }
    $titre = trim(preg_replace('/\s+/u', ' ', $titre));
    if (mb_strlen($titre) > 120) {
        throw new DomainException('Le titre du groupe dépasse 120 caractères.');
    }
    $pdo->prepare("INSERT INTO equipe_conversation (type, titre, id_createur) VALUES ('groupe', ?, ?)")->execute([$titre !== '' ? $titre : null, $idUser]);
    $idConversation = (int) $pdo->lastInsertId();
    $ajout = $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user) VALUES (?, ?)');
    foreach ([$idUser, ...$membres] as $idMembre) {
        $ajout->execute([$idConversation, $idMembre]);
    }
    return $idConversation;
}

function normaliserMessageEquipe(string $contenu): string
{
    $contenu = trim(str_replace(["\r\n", "\r"], "\n", $contenu));
    $contenu = preg_replace("/\n{3,}/", "\n\n", $contenu);
    if ($contenu === '' || !mb_check_encoding($contenu, 'UTF-8')) {
        throw new DomainException('Le message est vide.');
    }
    if (mb_strlen($contenu) > LONGUEUR_MESSAGE_EQUIPE) {
        throw new DomainException('Le message dépasse ' . LONGUEUR_MESSAGE_EQUIPE . ' caractères.');
    }
    return $contenu;
}

function peutEcrireConversationEquipe(PDO $pdo, array $conversation, int $idUser): bool
{
    if ($conversation['type'] !== 'privee') {
        return true;
    }
    foreach (participantsConversationEquipe($pdo, (int) $conversation['id_conversation']) as $participant) {
        if ((int) $participant['id'] !== $idUser && !$participant['actif']) {
            return false;
        }
    }
    return true;
}

function envoyerMessageInterneEquipe(PDO $pdo, int $idConversation, int $idUser, string $contenu): int
{
    $contenu = normaliserMessageEquipe($contenu);
    $conversation = conversationEquipe($pdo, $idConversation, $idUser);
    if (!$conversation) {
        throw new DomainException('Conversation introuvable.');
    }
    if (!peutEcrireConversationEquipe($pdo, $conversation, $idUser)) {
        throw new DomainException('Votre interlocuteur ne fait plus partie de l’équipe : la conversation est en lecture seule.');
    }
    if ($conversation['type'] === 'equipe') {
        $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user, id_dernier_lu)
                       SELECT ?, u.id, (SELECT COALESCE(MAX(m.id_message), 0) FROM equipe_message m WHERE m.id_conversation = ?)
                       FROM user u WHERE ' . conditionMembreEquipeActif())
            ->execute([$idConversation, $idConversation]);
    }
    $pdo->beginTransaction();
    try {
        $verrou = $pdo->prepare('SELECT id FROM user WHERE id = ? FOR UPDATE');
        $verrou->execute([$idUser]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipe_message WHERE id_auteur = ? AND date_envoi > NOW() - INTERVAL 1 HOUR');
        $stmt->execute([$idUser]);
        if ((int) $stmt->fetchColumn() >= ENVOIS_EQUIPE_PAR_HEURE) {
            throw new DomainException('Vous avez envoyé beaucoup de messages en une heure. Réessayez un peu plus tard.');
        }
        $pdo->prepare('INSERT INTO equipe_message (id_conversation, id_auteur, contenu) VALUES (?, ?, ?)')->execute([$idConversation, $idUser, $contenu]);
        $idMessage = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE equipe_conversation SET date_dernier_message = NOW() WHERE id_conversation = ?')->execute([$idConversation]);
        $pdo->prepare('UPDATE equipe_participant SET id_dernier_lu = GREATEST(id_dernier_lu, ?) WHERE id_conversation = ? AND id_user = ?')->execute([$idMessage, $idConversation, $idUser]);
        $pdo->commit();
        return $idMessage;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function rechercherMessagesEquipe(PDO $pdo, int $idUser, string $terme): array
{
    $terme = trim($terme);
    if (mb_strlen($terme) < 2) {
        return [];
    }
    $motif = '%' . addcslashes(mb_substr($terme, 0, 80), '%_\\') . '%';
    $stmt = $pdo->prepare('SELECT m.id_message, m.id_conversation, m.contenu, m.date_envoi, u.pseudo
                           FROM equipe_message m
                           INNER JOIN equipe_participant p ON p.id_conversation = m.id_conversation AND p.id_user = ?
                           LEFT JOIN user u ON u.id = m.id_auteur
                           WHERE m.contenu LIKE ? OR u.pseudo LIKE ?
                           ORDER BY m.id_message DESC LIMIT 50');
    $stmt->execute([$idUser, $motif, $motif]);
    return $stmt->fetchAll();
}

function demandeAvisEquipe(string $type, int $id): ?array
{
    if (!isset(DEMANDES_AVIS_EQUIPE[$type]) || $id <= 0) {
        return null;
    }
    [$page, $libelle, $droit] = DEMANDES_AVIS_EQUIPE[$type];
    $lien = str_ends_with($page, '=') ? $page . $id : $page;
    return [
        'droit' => $droit,
        'lien' => $lien,
        'texte' => "Bonjour, j'aimerais votre avis sur $libelle n° $id avant de trancher : $lien",
    ];
}

function actionDemandeAvisEquipe(string $type, int $id): array
{
    return ['messagerie_equipe.php?' . http_build_query(['demande' => $type, 'id' => $id]), 'people', 'Demander l’avis de l’équipe', 'btn-outline-secondary'];
}

function afficherMessageEquipe(string $contenu): string
{
    $html = nl2br(htmlspecialchars($contenu, ENT_QUOTES), false);
    $pages = implode('|', array_map(fn($page) => preg_quote($page, '~'), PAGES_LIENS_EQUIPE));
    return preg_replace('~(?<![\w/.])((?:' . $pages . ')(?:\?[a-z_]+=\d+)?)(?![\w/])~', '<a href="$1">$1</a>', $html);
}

function accueillirNouveauMembreEquipe(PDO $pdo, int $idNouveau, int $idAuteur, string $pseudo, string $libelleRole): bool
{
    if (!messagerieEquipeDisponible($pdo) || $idNouveau === $idAuteur) {
        return false;
    }
    $pdo->beginTransaction();
    try {
        $cle = 'privee-' . min($idNouveau, $idAuteur) . '-' . max($idNouveau, $idAuteur);
        $pdo->prepare("INSERT IGNORE INTO equipe_conversation (type, cle_unique, id_createur) VALUES ('privee', ?, ?)")->execute([$cle, $idAuteur]);
        $stmt = $pdo->prepare('SELECT id_conversation FROM equipe_conversation WHERE cle_unique = ?');
        $stmt->execute([$cle]);
        $idPrivee = (int) $stmt->fetchColumn();
        $idFil = idFilEquipe($pdo);
        $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user, id_dernier_lu)
                       SELECT ?, u.id, (SELECT COALESCE(MAX(m.id_message), 0) FROM equipe_message m WHERE m.id_conversation = ?)
                       FROM user u WHERE u.id = ? OR (' . conditionMembreEquipeActif() . ')')
            ->execute([$idFil, $idFil, $idNouveau]);
        $ajout = $pdo->prepare('INSERT IGNORE INTO equipe_participant (id_conversation, id_user) VALUES (?, ?)');
        $ajout->execute([$idPrivee, $idAuteur]);
        $ajout->execute([$idPrivee, $idNouveau]);
        $messages = [
            $idPrivee => "Bienvenue dans l'équipe BIBLIOccaz, @$pseudo ! Votre compte a maintenant le rôle de $libelleRole.\n\nVotre tableau de bord rassemble ce qui vous attend, et le fil « Équipe » sert à échanger avec tout le monde. Une question pour démarrer ? Répondez-moi directement ici.\n\n(Message automatique)",
            $idFil => "Bienvenue à @$pseudo, qui rejoint l'équipe comme $libelleRole !\n\n(Message automatique)",
        ];
        $envoi = $pdo->prepare('INSERT INTO equipe_message (id_conversation, id_auteur, contenu) VALUES (?, ?, ?)');
        $date = $pdo->prepare('UPDATE equipe_conversation SET date_dernier_message = NOW() WHERE id_conversation = ?');
        $lu = $pdo->prepare('UPDATE equipe_participant SET id_dernier_lu = GREATEST(id_dernier_lu, ?) WHERE id_conversation = ? AND id_user = ?');
        foreach ($messages as $idConversation => $contenu) {
            $envoi->execute([$idConversation, $idAuteur, $contenu]);
            $idMessage = (int) $pdo->lastInsertId();
            $date->execute([$idConversation]);
            $lu->execute([$idMessage, $idConversation, $idAuteur]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}
