<?php
require_once __DIR__ . '/notifications_lib.php';

const MESSAGE_LONGUEUR_MAX = 1000;
const MESSAGES_PAR_HEURE_MAX = 30;
const MAIL_MESSAGE_INTERVALLE_MINUTES = 15;
const TYPES_CONVERSATION = [
    'vente'   => ['Vente', 'etiquette'],
    'don'     => ['Don', 'carton'],
    'emprunt' => ['Emprunt', 'livre-ouvert'],
];
const STATUTS_CONVERSATION = [
    'ouverte' => ['Ouverte', ''],
    'fermee'  => ['Terminée', 'neutre'],
    'close'   => ["Close par l'équipe", 'refuse'],
];
const ROLES_PARTICIPANT = [
    'donneur'      => 'Donneur',
    'beneficiaire' => 'Bénéficiaire',
    'vendeur'      => 'Vendeur',
    'acheteur'     => 'Acheteur',
    'emprunteur'   => 'Emprunteur',
];
const MOTIFS_SIGNALEMENT_MESSAGE = [
    'insulte'     => 'Propos insultants ou agressifs',
    'harcelement' => 'Harcèlement ou insistance',
    'arnaque'     => 'Tentative d\'arnaque ou paiement suspect',
    'coordonnees' => 'Demande de coordonnées hors du site',
    'autre'       => 'Autre raison',
];

function ouvrirConversation(PDO $pdo, string $type, int $idObjet, array $participants, string $messageSysteme): int
{
    $pdo->prepare("INSERT INTO conversation (type_objet, id_objet, date_dernier_message) VALUES (?, ?, NOW())")->execute([$type, $idObjet]);
    $idConversation = (int) $pdo->lastInsertId();
    $ajout = $pdo->prepare("INSERT INTO conversation_participant (id_conversation, id_user, role_participant) VALUES (?, ?, ?)");
    foreach ($participants as $idUser => $role) {
        $ajout->execute([$idConversation, (int) $idUser, $role]);
    }
    ajouterMessageSysteme($pdo, $idConversation, $messageSysteme);
    return $idConversation;
}

function ajouterMessageSysteme(PDO $pdo, int $idConversation, string $texte): void
{
    $pdo->prepare("INSERT INTO conversation_message (id_conversation, id_auteur, contenu, est_systeme) VALUES (?, NULL, ?, 1)")
        ->execute([$idConversation, mb_substr($texte, 0, MESSAGE_LONGUEUR_MAX)]);
    $pdo->prepare("UPDATE conversation SET date_dernier_message = NOW() WHERE id_conversation = ?")->execute([$idConversation]);
}

function conversationOuverte(PDO $pdo, string $type, int $idObjet): ?int
{
    $stmt = $pdo->prepare("SELECT id_conversation FROM conversation WHERE type_objet = ? AND id_objet = ? AND statut = 'ouverte' ORDER BY id_conversation DESC LIMIT 1");
    $stmt->execute([$type, $idObjet]);
    return ($id = $stmt->fetchColumn()) ? (int) $id : null;
}

function fermerConversationsObjet(PDO $pdo, string $type, int $idObjet, string $messageSysteme, string $statut = 'fermee'): void
{
    try {
        $stmt = $pdo->prepare("SELECT id_conversation FROM conversation WHERE type_objet = ? AND id_objet = ? AND statut = 'ouverte'");
        $stmt->execute([$type, $idObjet]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idConversation) {
            fermerConversation($pdo, (int) $idConversation, $messageSysteme, $statut);
        }
    } catch (PDOException $e) {
    }
}

function fermerConversation(PDO $pdo, int $idConversation, string $messageSysteme, string $statut = 'fermee', ?string $motif = null): bool
{
    $maj = $pdo->prepare("UPDATE conversation SET statut = ?, motif_fermeture = ?, date_fermeture = NOW() WHERE id_conversation = ? AND statut = 'ouverte'");
    $maj->execute([$statut, $motif, $idConversation]);
    if (!$maj->rowCount()) {
        return false;
    }
    ajouterMessageSysteme($pdo, $idConversation, $messageSysteme);
    return true;
}

function conversationParId(PDO $pdo, int $idConversation): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM conversation WHERE id_conversation = ?");
    $stmt->execute([$idConversation]);
    $conversation = $stmt->fetch();
    if (!$conversation) {
        return null;
    }
    $stmt = $pdo->prepare("
        SELECT p.id_user, p.role_participant, p.date_derniere_lecture, u.pseudo, u.photo, u.statut
        FROM conversation_participant p INNER JOIN `user` u ON u.id = p.id_user
        WHERE p.id_conversation = ? ORDER BY p.role_participant");
    $stmt->execute([$idConversation]);
    $conversation['participants'] = [];
    foreach ($stmt->fetchAll() as $participant) {
        $conversation['participants'][(int) $participant['id_user']] = $participant;
    }
    $conversation['objet'] = infosObjetConversation($pdo, $conversation['type_objet'], (int) $conversation['id_objet']);
    return $conversation;
}

function infosObjetConversation(PDO $pdo, string $type, int $idObjet): array
{
    $infos = ['titre' => 'Transaction', 'image' => 'img/default.png', 'lien' => null, 'libelle_statut' => ''];
    if ($type === 'don') {
        $stmt = $pdo->prepare("SELECT titre, photo, statut FROM don WHERE id_don = ?");
        $stmt->execute([$idObjet]);
        if ($don = $stmt->fetch()) {
            $infos = ['titre' => $don['titre'], 'image' => $don['photo'] ? 'img/dons/' . rawurlencode($don['photo']) : 'img/default.png',
                      'lien' => 'don_detail.php?id=' . $idObjet, 'libelle_statut' => ''];
        }
    }
    if ($type === 'vente') {
        $stmt = $pdo->prepare("
            SELECT l.titre, l.couverture, (SELECT p.fichier FROM exemplaire_photo p WHERE p.id_exemplaire = d.id_annonce ORDER BY p.ordre, p.id_photo LIMIT 1) AS photo
            FROM demande_achat d INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce INNER JOIN livre l ON l.id_livre = e.id_livre
            WHERE d.id_demande = ?");
        $stmt->execute([$idObjet]);
        if ($vente = $stmt->fetch()) {
            $infos = ['titre' => $vente['titre'], 'image' => $vente['photo'] ? 'img/annonces/' . rawurlencode($vente['photo']) : urlImage($vente['couverture']),
                      'lien' => 'transaction.php?id=' . $idObjet, 'libelle_statut' => ''];
        }
    }
    return $infos;
}

function estParticipant(array $conversation, int $idUser): bool
{
    return isset($conversation['participants'][$idUser]);
}

function autreParticipant(array $conversation, int $idUser): ?array
{
    foreach ($conversation['participants'] as $id => $participant) {
        if ($id !== $idUser) {
            return $participant;
        }
    }
    return null;
}

function messagesConversation(PDO $pdo, int $idConversation): array
{
    $stmt = $pdo->prepare("
        SELECT m.*, u.pseudo AS auteur_pseudo, u.photo AS auteur_photo
        FROM conversation_message m LEFT JOIN `user` u ON u.id = m.id_auteur
        WHERE m.id_conversation = ? ORDER BY m.date_envoi, m.id_message");
    $stmt->execute([$idConversation]);
    return $stmt->fetchAll();
}

function marquerConversationLue(PDO $pdo, int $idConversation, int $idUser): void
{
    $pdo->prepare("UPDATE conversation_participant SET date_derniere_lecture = NOW() WHERE id_conversation = ? AND id_user = ?")
        ->execute([$idConversation, $idUser]);
}

function conditionNonLu(string $aliasMessage = 'm', string $aliasParticipant = 'p'): string
{
    return "$aliasMessage.est_masque = 0 AND ($aliasMessage.id_auteur IS NULL OR $aliasMessage.id_auteur <> $aliasParticipant.id_user)
            AND ($aliasParticipant.date_derniere_lecture IS NULL OR $aliasMessage.date_envoi > $aliasParticipant.date_derniere_lecture)";
}

function nombreMessagesNonLus(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM conversation_participant p
            INNER JOIN conversation_message m ON m.id_conversation = p.id_conversation
            WHERE p.id_user = ? AND " . conditionNonLu());
        $stmt->execute([$idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function conversationsUtilisateur(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare("
        SELECT c.*, p.role_participant,
               autre.id AS autre_id, autre.pseudo AS autre_pseudo, autre.photo AS autre_photo,
               (SELECT COUNT(*) FROM conversation_message m WHERE m.id_conversation = c.id_conversation AND " . conditionNonLu() . ") AS non_lus,
               (SELECT m2.contenu FROM conversation_message m2 WHERE m2.id_conversation = c.id_conversation ORDER BY m2.date_envoi DESC, m2.id_message DESC LIMIT 1) AS dernier_contenu,
               (SELECT m3.est_masque FROM conversation_message m3 WHERE m3.id_conversation = c.id_conversation ORDER BY m3.date_envoi DESC, m3.id_message DESC LIMIT 1) AS dernier_masque,
               (SELECT m4.id_auteur FROM conversation_message m4 WHERE m4.id_conversation = c.id_conversation ORDER BY m4.date_envoi DESC, m4.id_message DESC LIMIT 1) AS dernier_auteur
        FROM conversation_participant p
        INNER JOIN conversation c ON c.id_conversation = p.id_conversation
        LEFT JOIN conversation_participant pa ON pa.id_conversation = c.id_conversation AND pa.id_user <> p.id_user
        LEFT JOIN `user` autre ON autre.id = pa.id_user
        WHERE p.id_user = ?
        ORDER BY (c.statut = 'ouverte') DESC, c.date_dernier_message DESC");
    $stmt->execute([$idUser]);
    $conversations = $stmt->fetchAll();
    foreach ($conversations as &$conversation) {
        $conversation['objet'] = infosObjetConversation($pdo, $conversation['type_objet'], (int) $conversation['id_objet']);
    }
    return $conversations;
}

function derniereConversationObjet(PDO $pdo, string $type, int $idObjet, int $idUser): ?array
{
    try {
        $stmt = $pdo->prepare("
            SELECT c.id_conversation FROM conversation c
            INNER JOIN conversation_participant p ON p.id_conversation = c.id_conversation AND p.id_user = ?
            WHERE c.type_objet = ? AND c.id_objet = ? ORDER BY (c.statut = 'ouverte') DESC, c.id_conversation DESC LIMIT 1");
        $stmt->execute([$idUser, $type, $idObjet]);
        $id = $stmt->fetchColumn();
        return $id ? conversationParId($pdo, (int) $id) : null;
    } catch (PDOException $e) {
        return null;
    }
}

function envoyerMessage(PDO $pdo, int $idConversation, int $idAuteur, string $texte): array
{
    $texte = trim(preg_replace("/\r\n?/", "\n", $texte));
    $texte = preg_replace("/\n{3,}/", "\n\n", $texte);
    if ($texte === '') {
        return ['warning', "Votre message est vide."];
    }
    if (mb_strlen($texte) > MESSAGE_LONGUEUR_MAX) {
        return ['warning', "Votre message dépasse " . MESSAGE_LONGUEUR_MAX . " caractères."];
    }
    $conversation = conversationParId($pdo, $idConversation);
    if (!$conversation || !estParticipant($conversation, $idAuteur)) {
        return ['danger', "Conversation introuvable."];
    }
    if ($conversation['statut'] !== 'ouverte') {
        return ['warning', "Cette conversation est terminée : elle reste lisible, mais on ne peut plus y écrire."];
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversation_message WHERE id_auteur = ? AND date_envoi > NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$idAuteur]);
    if ((int) $stmt->fetchColumn() >= MESSAGES_PAR_HEURE_MAX) {
        return ['warning', "Vous avez envoyé beaucoup de messages en peu de temps. Patientez un peu avant d'écrire de nouveau."];
    }

    $pdo->prepare("INSERT INTO conversation_message (id_conversation, id_auteur, contenu) VALUES (?, ?, ?)")->execute([$idConversation, $idAuteur, $texte]);
    $pdo->prepare("UPDATE conversation SET date_dernier_message = NOW() WHERE id_conversation = ?")->execute([$idConversation]);
    marquerConversationLue($pdo, $idConversation, $idAuteur);
    prevenirParticipants($pdo, $conversation, $idAuteur, $texte);
    return ['success', "Message envoyé."];
}

function prevenirParticipants(PDO $pdo, array $conversation, int $idAuteur, string $texte): void
{
    $pseudoAuteur = $conversation['participants'][$idAuteur]['pseudo'];
    $extrait = mb_strlen($texte) > 300 ? mb_substr($texte, 0, 300) . '…' : $texte;
    $reservation = $pdo->prepare("
        UPDATE conversation_participant SET date_dernier_mail = NOW()
        WHERE id_conversation = ? AND id_user = ?
          AND (date_dernier_mail IS NULL OR date_dernier_mail < NOW() - INTERVAL " . MAIL_MESSAGE_INTERVALLE_MINUTES . " MINUTE)");
    $email = $pdo->prepare("SELECT email FROM `user` WHERE id = ?");
    foreach ($conversation['participants'] as $idDestinataire => $destinataire) {
        if ($idDestinataire === $idAuteur || in_array($destinataire['statut'], ['refuse_temporaire', 'refuse_definitif'], true)) {
            continue;
        }
        $reservation->execute([$conversation['id_conversation'], $idDestinataire]);
        if (!$reservation->rowCount()) {
            continue;
        }
        $email->execute([$idDestinataire]);
        notifierParMail((string) $email->fetchColumn(), "Nouveau message de @$pseudoAuteur",
            "Bonjour {$destinataire['pseudo']},\n\n@$pseudoAuteur vous a écrit à propos de « {$conversation['objet']['titre']} » :\n\n« $extrait »\n\nRépondez directement sur BIBLIOccaz : votre adresse email n'est jamais communiquée.",
            SITE_URL . '/messages.php?id=' . $conversation['id_conversation'], "Répondre");
    }
}

function signalerMessage(PDO $pdo, int $idMessage, int $idUser, string $motif, string $commentaire): array
{
    if (!isset(MOTIFS_SIGNALEMENT_MESSAGE[$motif])) {
        return ['warning', "Choisissez le motif du signalement."];
    }
    $stmt = $pdo->prepare("SELECT id_conversation, id_auteur, est_systeme FROM conversation_message WHERE id_message = ?");
    $stmt->execute([$idMessage]);
    $message = $stmt->fetch();
    $conversation = $message ? conversationParId($pdo, (int) $message['id_conversation']) : null;
    if (!$conversation || !estParticipant($conversation, $idUser) || $message['est_systeme'] || (int) $message['id_auteur'] === $idUser) {
        return ['danger', "Ce message ne peut pas être signalé."];
    }
    $ajout = $pdo->prepare("INSERT IGNORE INTO message_signalement (id_message, id_user, motif, commentaire) VALUES (?, ?, ?, ?)");
    $ajout->execute([$idMessage, $idUser, $motif, mb_substr(trim($commentaire), 0, 500) ?: null]);
    if (!$ajout->rowCount()) {
        return ['info', "Vous avez déjà signalé ce message : l'équipe va l'examiner."];
    }
    insertLog('MESSAGE', "Message #$idMessage signalé ($motif)");
    return ['success', "Merci : l'équipe va examiner ce message. L'autre personne n'est pas prévenue."];
}

function nombreSignalementsMessages(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(DISTINCT id_message) FROM message_signalement WHERE traite = 0")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function dateMessage(string $date): string
{
    $horodatage = strtotime($date);
    if (date('Y-m-d', $horodatage) === date('Y-m-d')) {
        return date('H:i', $horodatage);
    }
    if (date('Y-m-d', $horodatage) === date('Y-m-d', strtotime('-1 day'))) {
        return 'hier, ' . date('H:i', $horodatage);
    }
    return date('d/m', $horodatage) . (date('Y', $horodatage) !== date('Y') ? '/' . date('Y', $horodatage) : '') . ', ' . date('H:i', $horodatage);
}

function pastilleStatutConversation(string $statut): string
{
    [$libelle, $classe] = STATUTS_CONVERSATION[$statut] ?? [$statut, 'neutre'];
    return '<span class="pastille-etat ' . $classe . '">' . htmlspecialchars($libelle) . '</span>';
}
