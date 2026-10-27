<?php
require_once __DIR__ . '/annonces_lib.php';
require_once __DIR__ . '/messagerie_lib.php';
require_once __DIR__ . '/points_lib.php';

const DEMANDES_EN_ATTENTE_MAX = 5;
const DEMANDE_EXPIRATION_JOURS = 7;
const VENTE_RETARD_JOURS = 14;
const POINTS_VENTE = 10;
const VENTES_PAYEES_PAR_MOIS = 5;
const CONFIRMATION_JOURS = 7;
const STATUTS_DEMANDE = [
    'en_attente'      => ['En attente de réponse', 'attente', 'hourglass-split'],
    'en_file'         => ["En file d'attente", 'attente', 'people'],
    'acceptee'        => ['Acceptée, échange à faire', '', 'hand-thumbs-up'],
    'validee'         => ['Vente conclue', '', 'bag-check'],
    'refusee'         => ['Refusée', 'refuse', 'x-circle'],
    'annulee'         => ['Annulée', 'neutre', 'arrow-counterclockwise'],
    'expiree'         => ['Expirée sans réponse', 'neutre', 'clock-history'],
    'annulee_equipe'  => ["Annulée par l'équipe", 'refuse', 'shield-x'],
];
const STATUTS_DEMANDE_ACTIFS = ['en_attente', 'en_file', 'acceptee'];

function pastilleStatutDemande(array $demande): string
{
    [$libelle, $classe, $icone] = receptionAConfirmer($demande)
        ? ['Vente conclue · réception à confirmer', 'attente', 'hourglass-split']
        : STATUTS_DEMANDE[$demande['statut']] ?? [$demande['statut'], 'neutre', 'question-circle'];
    $html = '<span class="pastille-etat ' . $classe . '"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> ' . htmlspecialchars($libelle) . '</span>';
    if (venteEnRetard($demande)) {
        $html .= ' <span class="pastille-etat refuse"><i class="bi bi-alarm" aria-hidden="true"></i> En retard</span>';
    }
    if ((int) ($demande['en_litige'] ?? 0)) {
        $html .= ' <span class="pastille-etat refuse"><i class="bi bi-flag" aria-hidden="true"></i> Litige signalé</span>';
    }
    return $html;
}

function venteEnRetard(array $demande): bool
{
    return $demande['statut'] === 'acceptee' && $demande['date_acceptation']
        && strtotime($demande['date_acceptation']) < strtotime('-' . VENTE_RETARD_JOURS . ' days');
}

function demandeParId(PDO $pdo, int $idDemande, bool $verrouiller = false): ?array
{
    $stmt = $pdo->prepare("
        SELECT d.*, e.etat, e.statut_annonce, e.description AS description_annonce, e.ville_remise, e.mode_remise AS mode_annonce, e.id_livre,
               l.titre, l.auteur, l.couverture,
               a.pseudo AS acheteur_pseudo, a.email AS acheteur_email, a.prenom AS acheteur_prenom, a.nom AS acheteur_nom,
               v.pseudo AS vendeur_pseudo, v.email AS vendeur_email, v.prenom AS vendeur_prenom, v.nom AS vendeur_nom
        FROM demande_achat d
        INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce
        INNER JOIN livre l ON l.id_livre = e.id_livre
        INNER JOIN `user` a ON a.id = d.id_acheteur
        INNER JOIN `user` v ON v.id = d.id_vendeur
        WHERE d.id_demande = ?" . ($verrouiller ? " FOR UPDATE" : ''));
    $stmt->execute([$idDemande]);
    return $stmt->fetch() ?: null;
}

function demandeActiveAcheteur(PDO $pdo, int $idAnnonce, int $idAcheteur): ?array
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM demande_achat WHERE id_annonce = ? AND id_acheteur = ? AND statut IN ('en_attente', 'en_file', 'acceptee', 'validee') ORDER BY id_demande DESC LIMIT 1");
        $stmt->execute([$idAnnonce, $idAcheteur]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function nombreDemandesAnnonce(PDO $pdo, int $idAnnonce): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_achat WHERE id_annonce = ? AND statut IN ('en_attente', 'en_file', 'acceptee')");
        $stmt->execute([$idAnnonce]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function expirerDemandes(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("SELECT id_demande FROM demande_achat WHERE statut = 'en_attente' AND date_attente < NOW() - INTERVAL " . DEMANDE_EXPIRATION_JOURS . " DAY");
        $stmt->execute();
        $maj = $pdo->prepare("UPDATE demande_achat SET statut = 'expiree', date_fin = NOW() WHERE id_demande = ? AND statut = 'en_attente'");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idDemande) {
            $maj->execute([$idDemande]);
            if ($maj->rowCount()) {
                fermerConversationsObjet($pdo, 'vente', (int) $idDemande, "Demande expirée : le vendeur n'a pas répondu en " . DEMANDE_EXPIRATION_JOURS . " jours.");
            }
        }
    } catch (PDOException $e) {
    }
    confirmerVentesEchues($pdo);
}

function raisonInterdictionAchat(PDO $pdo, int $idUser, array $annonce): string
{
    $stmt = $pdo->prepare("SELECT role, statut, email_verifie FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $membre = $stmt->fetch();
    if (!$membre) {
        return "Compte introuvable.";
    }
    if (!in_array($membre['role'], ROLES_VENDEURS, true)) {
        return "Les achats entre lecteurs sont réservés aux comptes lecteurs et auteurs.";
    }
    if (in_array($membre['statut'], STATUTS_COMPTE_SUSPENDU, true)) {
        return "Votre compte est suspendu : vous ne pouvez pas acheter entre lecteurs.";
    }
    if (!(int) $membre['email_verifie']) {
        return "Confirmez d'abord votre adresse email pour pouvoir acheter à un lecteur.";
    }
    if ((int) $annonce['id_proprietaire'] === $idUser) {
        return "C'est votre propre annonce.";
    }
    return '';
}

function libelleRemiseDemande(array $demande): string
{
    return libelleModeRemise($demande['mode_remise'], $demande['mode_remise'] === 'envoi' ? null : ($demande['ville_remise'] ?? null));
}

function creerDemande(PDO $pdo, int $idAnnonce, int $idAcheteur, string $modeRemise, string $message): array
{
    $message = trim(preg_replace("/\r\n?/", "\n", $message));
    if (mb_strlen($message) > MESSAGE_LONGUEUR_MAX) {
        return ['warning', "Votre message dépasse " . MESSAGE_LONGUEUR_MAX . " caractères.", null];
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, l.titre, v.statut AS vendeur_statut, v.email AS vendeur_email, v.pseudo AS vendeur_pseudo
            FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre INNER JOIN `user` v ON v.id = e.id_proprietaire
            WHERE e.id_exemplaire = ? AND e.id_proprietaire IS NOT NULL AND e.type_offre = 'vente' FOR UPDATE");
        $stmt->execute([$idAnnonce]);
        $annonce = $stmt->fetch();
        if (!$annonce || !in_array($annonce['statut_annonce'], ['publiee', 'reservee'], true) || in_array($annonce['vendeur_statut'], STATUTS_COMPTE_SUSPENDU, true)) {
            $pdo->rollBack();
            return ['warning', "Cette annonce n'est plus disponible.", null];
        }
        $raison = raisonInterdictionAchat($pdo, $idAcheteur, $annonce);
        if ($raison !== '') {
            $pdo->rollBack();
            return ['warning', $raison, null];
        }
        if (demandeActiveAcheteur($pdo, $idAnnonce, $idAcheteur)) {
            $pdo->rollBack();
            return ['info', "Vous avez déjà une demande en cours pour cette annonce.", null];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_achat WHERE id_acheteur = ? AND statut IN ('en_attente', 'en_file')");
        $stmt->execute([$idAcheteur]);
        if ((int) $stmt->fetchColumn() >= DEMANDES_EN_ATTENTE_MAX) {
            $pdo->rollBack();
            return ['warning', "Vous avez déjà " . DEMANDES_EN_ATTENTE_MAX . " demandes en attente : patientez qu'un vendeur réponde ou annulez-en une.", null];
        }
        $modeRemise = $annonce['mode_remise'] === 'les_deux' ? $modeRemise : $annonce['mode_remise'];
        if (!in_array($modeRemise, ['main_propre', 'envoi'], true)) {
            $pdo->rollBack();
            return ['warning', "Choisissez la remise en main propre ou l'envoi postal.", null];
        }
        $statut = $annonce['statut_annonce'] === 'publiee' ? 'en_attente' : 'en_file';
        $pdo->prepare("INSERT INTO demande_achat (id_annonce, id_acheteur, id_vendeur, prix, mode_remise, statut) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$idAnnonce, $idAcheteur, (int) $annonce['id_proprietaire'], $annonce['prix'], $modeRemise, $statut]);
        $idDemande = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT pseudo FROM `user` WHERE id = ?");
        $stmt->execute([$idAcheteur]);
        $pseudoAcheteur = (string) $stmt->fetchColumn();
        $idConversation = ouvrirConversation($pdo, 'vente', $idDemande, [(int) $annonce['id_proprietaire'] => 'vendeur', $idAcheteur => 'acheteur'],
            "@$pseudoAcheteur souhaite acheter ce livre à " . formaterPrix($annonce['prix']) . " (" . mb_strtolower(MODES_REMISE[$modeRemise][0]) . ")."
            . ($statut === 'en_file' ? " L'annonce est déjà réservée : la demande est en file d'attente." : ''));
        if ($message !== '') {
            $pdo->prepare("INSERT INTO conversation_message (id_conversation, id_auteur, contenu) VALUES (?, ?, ?)")->execute([$idConversation, $idAcheteur, $message]);
        }
        marquerConversationLue($pdo, $idConversation, $idAcheteur);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Demande d'achat de l'annonce #$idAnnonce non créée : " . $e->getMessage());
        return ['danger', "La demande n'a pas pu être envoyée. Merci de réessayer.", null];
    }

    insertLog('VENTE', "Demande d'achat #$idDemande pour l'annonce #$idAnnonce");
    notifierParMail($annonce['vendeur_email'], "Nouvelle demande d'achat",
        "Bonjour {$annonce['vendeur_pseudo']},\n\n@$pseudoAcheteur souhaite acheter « {$annonce['titre']} » à " . formaterPrix($annonce['prix']) . ".\n\n"
        . "Acceptez ou refusez la demande depuis BIBLIOccaz, sous " . DEMANDE_EXPIRATION_JOURS . " jours. Vous pouvez aussi lui écrire dans la conversation.",
        SITE_URL . "/transaction.php?id=$idDemande", "Voir la demande");
    return ['success', $statut === 'en_file'
        ? "Demande envoyée. L'annonce est déjà réservée : vous êtes en file d'attente et serez prévenu si elle se libère."
        : "Demande envoyée au vendeur. Il a " . DEMANDE_EXPIRATION_JOURS . " jours pour répondre.", $idDemande];
}

function verrouillerAnnonce(PDO $pdo, int $idAnnonce): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM exemplaire WHERE id_exemplaire = ? FOR UPDATE");
    $stmt->execute([$idAnnonce]);
    return $stmt->fetch() ?: null;
}

function accepterDemande(PDO $pdo, int $idDemande, int $idVendeur): array
{
    $pdo->beginTransaction();
    $demande = demandeParId($pdo, $idDemande);
    $annonce = $demande ? verrouillerAnnonce($pdo, (int) $demande['id_annonce']) : null;
    $demande = $demande ? demandeParId($pdo, $idDemande, true) : null;
    if (!$demande || (int) $demande['id_vendeur'] !== $idVendeur || $demande['statut'] !== 'en_attente' || !$annonce || $annonce['statut_annonce'] !== 'publiee') {
        $pdo->rollBack();
        return ['warning', "Cette demande ne peut plus être acceptée."];
    }
    $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'reservee' WHERE id_exemplaire = ?")->execute([$demande['id_annonce']]);
    $pdo->prepare("UPDATE demande_achat SET statut = 'acceptee', date_acceptation = NOW() WHERE id_demande = ?")->execute([$idDemande]);
    $stmt = $pdo->prepare("
        SELECT d.id_demande, a.email, a.pseudo FROM demande_achat d INNER JOIN `user` a ON a.id = d.id_acheteur
        WHERE d.id_annonce = ? AND d.statut = 'en_attente' AND d.id_demande <> ?");
    $stmt->execute([$demande['id_annonce'], $idDemande]);
    $autres = $stmt->fetchAll();
    $pdo->prepare("UPDATE demande_achat SET statut = 'en_file' WHERE id_annonce = ? AND statut = 'en_attente' AND id_demande <> ?")->execute([$demande['id_annonce'], $idDemande]);
    $idConversation = conversationOuverte($pdo, 'vente', $idDemande);
    if ($idConversation) {
        ajouterMessageSysteme($pdo, $idConversation, "@{$demande['vendeur_pseudo']} a accepté la demande. Convenez ici de l'échange ; une fois le livre remis, le vendeur indique la vente conclue.");
    }
    foreach ($autres as $autre) {
        if ($idAutre = conversationOuverte($pdo, 'vente', (int) $autre['id_demande'])) {
            ajouterMessageSysteme($pdo, $idAutre, "Le vendeur a accepté une autre demande. La vôtre passe en file d'attente : si la vente ne se fait pas, elle redeviendra prioritaire.");
        }
    }
    $pdo->commit();

    insertLog('VENTE', "Demande #$idDemande acceptée (annonce #{$demande['id_annonce']} réservée)");
    notifierParMail($demande['acheteur_email'], "Votre demande est acceptée",
        "Bonjour {$demande['acheteur_pseudo']},\n\n@{$demande['vendeur_pseudo']} a accepté votre demande pour « {$demande['titre']} » (" . formaterPrix($demande['prix']) . ").\n\n"
        . "Convenez de l'échange dans la conversation. Le paiement se fait directement au vendeur ; une fois le livre remis, le vendeur indiquera la vente conclue et vous aurez " . CONFIRMATION_JOURS . " jours pour confirmer la réception.",
        SITE_URL . "/transaction.php?id=$idDemande", "Organiser l'échange");
    foreach ($autres as $autre) {
        notifierParMail($autre['email'], "Votre demande est en file d'attente",
            "Bonjour {$autre['pseudo']},\n\nLe vendeur de « {$demande['titre']} » a accepté une autre demande. La vôtre reste en file d'attente : nous vous prévenons si le livre redevient disponible.",
            SITE_URL . "/transaction.php?id={$autre['id_demande']}", "Voir ma demande");
    }
    return ['success', "Demande acceptée : l'annonce est réservée pour @{$demande['acheteur_pseudo']}." . ($autres ? ' ' . count($autres) . ' autre' . (count($autres) > 1 ? 's demandes passent' : ' demande passe') . " en file d'attente." : '')];
}

function refuserDemande(PDO $pdo, int $idDemande, int $idVendeur, string $motif): array
{
    $motif = mb_substr(trim($motif), 0, 500);
    $maj = $pdo->prepare("UPDATE demande_achat SET statut = 'refusee', motif = ?, date_fin = NOW() WHERE id_demande = ? AND id_vendeur = ? AND statut IN ('en_attente', 'en_file')");
    $maj->execute([$motif ?: null, $idDemande, $idVendeur]);
    if (!$maj->rowCount()) {
        return ['warning', "Cette demande ne peut plus être refusée."];
    }
    $demande = demandeParId($pdo, $idDemande);
    fermerConversationsObjet($pdo, 'vente', $idDemande, "Le vendeur a refusé la demande" . ($motif !== '' ? " : $motif" : '.'));
    insertLog('VENTE', "Demande #$idDemande refusée par le vendeur");
    notifierParMail($demande['acheteur_email'], "Votre demande n'a pas été retenue",
        "Bonjour {$demande['acheteur_pseudo']},\n\n@{$demande['vendeur_pseudo']} n'a pas retenu votre demande pour « {$demande['titre']} »." . ($motif !== '' ? "\n\nSon message : $motif" : '')
        . "\n\nD'autres lecteurs proposent peut-être ce livre : jetez un œil aux autres offres.",
        SITE_URL . "/detail_livre.php?id={$demande['id_livre']}#offres", "Voir les autres offres");
    return ['success', "Demande refusée. L'acheteur est prévenu."];
}

function remettreAnnonceEnVente(PDO $pdo, array $demande): array
{
    $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'publiee' WHERE id_exemplaire = ? AND statut_annonce = 'reservee'")->execute([$demande['id_annonce']]);
    $stmt = $pdo->prepare("
        SELECT d.id_demande, a.email, a.pseudo FROM demande_achat d INNER JOIN `user` a ON a.id = d.id_acheteur
        WHERE d.id_annonce = ? AND d.statut = 'en_file' ORDER BY d.date_demande");
    $stmt->execute([$demande['id_annonce']]);
    $enFile = $stmt->fetchAll();
    $pdo->prepare("UPDATE demande_achat SET statut = 'en_attente', date_attente = NOW() WHERE id_annonce = ? AND statut = 'en_file'")->execute([$demande['id_annonce']]);
    foreach ($enFile as $attente) {
        if ($idConversation = conversationOuverte($pdo, 'vente', (int) $attente['id_demande'])) {
            ajouterMessageSysteme($pdo, $idConversation, "Le livre est de nouveau disponible : votre demande sort de la file d'attente. Le vendeur a " . DEMANDE_EXPIRATION_JOURS . " jours pour répondre.");
        }
    }
    return $enFile;
}

function annulerDemande(PDO $pdo, int $idDemande, int $idUser, string $motif): array
{
    $motif = mb_substr(trim($motif), 0, 500);
    $pdo->beginTransaction();
    $demande = demandeParId($pdo, $idDemande);
    $annonce = $demande ? verrouillerAnnonce($pdo, (int) $demande['id_annonce']) : null;
    $demande = $demande ? demandeParId($pdo, $idDemande, true) : null;
    $estAcheteur = $demande && (int) $demande['id_acheteur'] === $idUser;
    $estVendeur = $demande && (int) $demande['id_vendeur'] === $idUser;
    $annulable = $demande && (($estAcheteur && in_array($demande['statut'], STATUTS_DEMANDE_ACTIFS, true)) || ($estVendeur && $demande['statut'] === 'acceptee'));
    if (!$annulable) {
        $pdo->rollBack();
        return ['warning', "Cette demande ne peut plus être annulée."];
    }
    $pdo->prepare("UPDATE demande_achat SET statut = 'annulee', motif = ?, date_fin = NOW() WHERE id_demande = ?")->execute([$motif ?: null, $idDemande]);
    $enFile = [];
    if ($demande['statut'] === 'acceptee') {
        $enFile = remettreAnnonceEnVente($pdo, $demande);
    }
    $auteur = $estAcheteur ? $demande['acheteur_pseudo'] : $demande['vendeur_pseudo'];
    fermerConversationsObjet($pdo, 'vente', $idDemande, "@$auteur a annulé " . ($demande['statut'] === 'acceptee' ? 'la vente' : 'la demande') . ($motif !== '' ? " : $motif" : '.'));
    $pdo->commit();

    insertLog('VENTE', "Demande #$idDemande annulée par " . ($estAcheteur ? "l'acheteur" : 'le vendeur'));
    [$destinataireEmail, $destinatairePseudo] = $estAcheteur ? [$demande['vendeur_email'], $demande['vendeur_pseudo']] : [$demande['acheteur_email'], $demande['acheteur_pseudo']];
    if ($demande['statut'] !== 'en_file' || !$estAcheteur) {
        notifierParMail($destinataireEmail, $demande['statut'] === 'acceptee' ? "Vente annulée" : "Demande d'achat annulée",
            "Bonjour $destinatairePseudo,\n\n@$auteur a annulé " . ($demande['statut'] === 'acceptee' ? 'la vente' : 'sa demande') . " pour « {$demande['titre']} »." . ($motif !== '' ? "\n\nMotif : $motif" : '')
            . ($demande['statut'] === 'acceptee' && $estAcheteur ? "\n\nVotre annonce est de nouveau en vente." : ''),
            SITE_URL . "/transaction.php?id=$idDemande", "Voir la transaction");
    }
    foreach ($enFile as $attente) {
        notifierParMail($attente['email'], "Le livre est de nouveau disponible",
            "Bonjour {$attente['pseudo']},\n\n« {$demande['titre']} » est de nouveau disponible : votre demande sort de la file d'attente et le vendeur va pouvoir y répondre.",
            SITE_URL . "/transaction.php?id={$attente['id_demande']}", "Voir ma demande");
    }
    return ['success', $demande['statut'] === 'acceptee' ? "Vente annulée : l'annonce est de nouveau en vente." : "Demande annulée."];
}

function numeroRecu(int $idDemande): string
{
    return 'BV-' . date('Y') . '-' . str_pad((string) $idDemande, 5, '0', STR_PAD_LEFT);
}

function dateLimiteConfirmation(array $demande): string
{
    return date('d/m/Y', strtotime($demande['date_validation'] . ' +' . CONFIRMATION_JOURS . ' days'));
}

function receptionAConfirmer(array $demande): bool
{
    return $demande['statut'] === 'validee' && empty($demande['date_confirmation']);
}

function conclureVente(PDO $pdo, int $idDemande, ?int $idVendeur, ?int $idModerateur): array
{
    $pdo->beginTransaction();
    $demande = demandeParId($pdo, $idDemande);
    $annonce = $demande ? verrouillerAnnonce($pdo, (int) $demande['id_annonce']) : null;
    $demande = $demande ? demandeParId($pdo, $idDemande, true) : null;
    if (!$demande || $demande['statut'] !== 'acceptee' || ($idVendeur !== null && (int) $demande['id_vendeur'] !== $idVendeur) || !$annonce) {
        $pdo->rollBack();
        return ['warning', "Cette vente ne peut pas être conclue."];
    }
    $pdo->prepare("UPDATE demande_achat SET statut = 'validee', date_validation = NOW(), date_fin = NOW(), numero_recu = ?, id_moderateur = COALESCE(?, id_moderateur) WHERE id_demande = ?")
        ->execute([numeroRecu($idDemande), $idModerateur, $idDemande]);
    $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'vendue', quantite = 0, is_disponible = 0 WHERE id_exemplaire = ?")->execute([$demande['id_annonce']]);
    $stmt = $pdo->prepare("
        SELECT d.id_demande, a.email, a.pseudo FROM demande_achat d INNER JOIN `user` a ON a.id = d.id_acheteur
        WHERE d.id_annonce = ? AND d.statut IN ('en_attente', 'en_file')");
    $stmt->execute([$demande['id_annonce']]);
    $autres = $stmt->fetchAll();
    $pdo->prepare("UPDATE demande_achat SET statut = 'refusee', motif = 'Livre vendu à un autre lecteur', date_fin = NOW() WHERE id_annonce = ? AND statut IN ('en_attente', 'en_file')")->execute([$demande['id_annonce']]);
    foreach ($autres as $autre) {
        fermerConversationsObjet($pdo, 'vente', (int) $autre['id_demande'], "Le livre a été vendu à un autre lecteur : la demande est close.");
    }

    $demande['date_validation'] = date('Y-m-d H:i:s');
    $dateLimite = dateLimiteConfirmation($demande);
    if ($idConversation = conversationOuverte($pdo, 'vente', $idDemande)) {
        ajouterMessageSysteme($pdo, $idConversation, ($idModerateur ? "L'équipe a conclu la vente." : "@{$demande['vendeur_pseudo']} a indiqué que la vente est conclue.")
            . " @{$demande['acheteur_pseudo']} peut confirmer la réception ou signaler un problème jusqu'au $dateLimite.");
    }

    $idVendeur = (int) $demande['id_vendeur'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_mouvement WHERE id_user = ? AND type = 'vente' AND statut IN ('valide', 'en_attente') AND date_mouvement >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute([$idVendeur]);
    $pointsPrevus = (int) $stmt->fetchColumn() < VENTES_PAYEES_PAR_MOIS
        && crediterPoints($pdo, $idVendeur, POINTS_VENTE, 'vente', "Vente entre lecteurs : « {$demande['titre']} »", 'vente:' . $idDemande, 'en_attente');
    $pdo->commit();

    insertLog('VENTE', "Vente #$idDemande conclue" . ($idModerateur ? " par l'équipe" : ' par le vendeur') . ($pointsPrevus ? ' (' . POINTS_VENTE . ' points en attente)' : ''));
    $lien = SITE_URL . "/transaction.php?id=$idDemande";
    notifierParMail($demande['acheteur_email'], "Confirmez la réception de votre livre",
        "Bonjour {$demande['acheteur_pseudo']},\n\n@{$demande['vendeur_pseudo']} a indiqué que la vente de « {$demande['titre']} » (" . formaterPrix($demande['prix']) . ") est conclue.\n\n"
        . "Confirmez la réception du livre, ou signalez un problème, avant le $dateLimite. Sans signalement de votre part, la vente sera confirmée automatiquement.\n\nLe reçu de la vente est disponible sur la page de la transaction.",
        $lien, "Confirmer la réception");
    notifierParMail($demande['vendeur_email'], "Vente conclue",
        "Bonjour {$demande['vendeur_pseudo']},\n\nLa vente de « {$demande['titre']} » à @{$demande['acheteur_pseudo']} est conclue (" . formaterPrix($demande['prix']) . "). Le reçu est disponible sur la page de la transaction."
        . ($pointsPrevus ? "\n\nVos " . POINTS_VENTE . " points seront crédités dès que l'acheteur aura confirmé la réception, ou automatiquement le $dateLimite sans signalement." : ''),
        $lien, "Voir le reçu");
    foreach ($autres as $autre) {
        notifierParMail($autre['email'], "Le livre a été vendu",
            "Bonjour {$autre['pseudo']},\n\n« {$demande['titre']} » a été vendu à un autre lecteur. D'autres offres existent peut-être pour ce livre.",
            SITE_URL . "/detail_livre.php?id={$demande['id_livre']}#offres", "Voir les autres offres");
    }
    return ['success', $idModerateur ? "Vente conclue par l'équipe." : "Vente conclue, merci ! Le reçu est disponible. @{$demande['acheteur_pseudo']} a jusqu'au $dateLimite pour confirmer la réception."];
}

function confirmerVente(PDO $pdo, int $idDemande, ?int $idAcheteur, string $mode, ?int $idModerateur = null): array
{
    $pdo->beginTransaction();
    $demande = demandeParId($pdo, $idDemande, true);
    $autorise = $demande && receptionAConfirmer($demande)
        && ($mode === 'equipe' || !(int) $demande['en_litige'])
        && ($idAcheteur === null || (int) $demande['id_acheteur'] === $idAcheteur);
    if (!$autorise) {
        $pdo->rollBack();
        return ['warning', "Cette vente ne peut pas être confirmée."];
    }
    $pdo->prepare("UPDATE demande_achat SET date_confirmation = NOW(), confirmation = ?, en_litige = 0, id_moderateur = COALESCE(?, id_moderateur) WHERE id_demande = ?")
        ->execute([$mode, $idModerateur, $idDemande]);
    $idVendeur = (int) $demande['id_vendeur'];
    $pointsCredites = changerStatutMouvement($pdo, $idVendeur, 'vente', 'vente:' . $idDemande, ['en_attente'], 'valide');
    verifierBadges($pdo, $idVendeur);
    $messages = [
        'acheteur' => "@{$demande['acheteur_pseudo']} a confirmé avoir reçu le livre. Merci à tous les deux !",
        'auto' => "Aucun problème signalé en " . CONFIRMATION_JOURS . " jours : la vente est confirmée automatiquement. Merci à tous les deux !",
        'equipe' => "L'équipe a examiné la transaction et confirmé la vente. Merci à tous les deux !",
    ];
    fermerConversationsObjet($pdo, 'vente', $idDemande, $messages[$mode] ?? $messages['acheteur']);
    $pdo->commit();

    insertLog('VENTE', "Vente #$idDemande confirmée ($mode)" . ($pointsCredites ? ' : +' . POINTS_VENTE . ' points vendeur' : ''));
    notifierParMail($demande['vendeur_email'], "Vente confirmée",
        "Bonjour {$demande['vendeur_pseudo']},\n\n" . match ($mode) {
            'auto' => "Aucun problème n'a été signalé sur la vente de « {$demande['titre']} » : elle est confirmée.",
            'equipe' => "L'équipe BIBLIOccaz a confirmé la vente de « {$demande['titre']} ».",
            default => "@{$demande['acheteur_pseudo']} a confirmé avoir bien reçu « {$demande['titre']} ».",
        } . ($pointsCredites ? "\n\nVous gagnez " . POINTS_VENTE . " points BIBLIOccaz. Merci d'avoir donné une seconde vie à ce livre !" : ''),
        SITE_URL . "/transaction.php?id=$idDemande", "Voir la transaction");
    return ['success', match ($mode) {
        'equipe' => "Vente confirmée par l'équipe" . ($pointsCredites ? ' : les points du vendeur sont crédités.' : '.'),
        'auto' => "Vente confirmée automatiquement.",
        default => "Merci ! La réception est confirmée. Bonne lecture !",
    }];
}

function confirmerVentesEchues(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SELECT id_demande FROM demande_achat WHERE statut = 'validee' AND date_confirmation IS NULL AND en_litige = 0
                             AND date_validation < NOW() - INTERVAL " . CONFIRMATION_JOURS . " DAY");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idDemande) {
            confirmerVente($pdo, (int) $idDemande, null, 'auto');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

function litigePossible(array $demande): bool
{
    return !(int) $demande['en_litige'] && ($demande['statut'] === 'acceptee'
        || (receptionAConfirmer($demande) && strtotime((string) $demande['date_validation']) > strtotime('-' . CONFIRMATION_JOURS . ' days')));
}

function signalerLitige(PDO $pdo, int $idDemande, int $idUser, string $motif): array
{
    $motif = mb_substr(trim($motif), 0, 500);
    if ($motif === '') {
        return ['warning', "Expliquez le problème en quelques mots : l'équipe le lira."];
    }
    $maj = $pdo->prepare("
        UPDATE demande_achat SET en_litige = 1, motif_litige = ?, id_signale_par = ?, date_litige = NOW()
        WHERE id_demande = ? AND (id_acheteur = ? OR id_vendeur = ?) AND en_litige = 0
          AND (statut = 'acceptee' OR (statut = 'validee' AND date_confirmation IS NULL AND date_validation > NOW() - INTERVAL " . CONFIRMATION_JOURS . " DAY))");
    $maj->execute([$motif, $idUser, $idDemande, $idUser, $idUser]);
    if (!$maj->rowCount()) {
        return ['warning', "Un problème ne peut plus être signalé sur cette transaction (ou il l'a déjà été)."];
    }
    $demande = demandeParId($pdo, $idDemande);
    if ($idConversation = conversationOuverte($pdo, 'vente', $idDemande)) {
        ajouterMessageSysteme($pdo, $idConversation, "Un problème a été signalé à l'équipe BIBLIOccaz, qui pourra lire cette conversation pour aider à le résoudre."
            . ($demande['statut'] === 'validee' ? " Les points de la vente restent bloqués jusqu'à sa décision." : ''));
    }
    insertLog('VENTE', "Litige signalé sur la transaction #$idDemande");
    return ['success', "Problème signalé : l'équipe va examiner la transaction et revenir vers vous."];
}

function annulerVenteParEquipe(PDO $pdo, int $idDemande, int $idModerateur, string $motif): array
{
    $motif = mb_substr(trim($motif), 0, 500);
    if ($motif === '') {
        return ['warning', "Indiquez le motif : il est envoyé aux deux membres."];
    }
    $pdo->beginTransaction();
    $demande = demandeParId($pdo, $idDemande);
    $annonce = $demande ? verrouillerAnnonce($pdo, (int) $demande['id_annonce']) : null;
    $demande = $demande ? demandeParId($pdo, $idDemande, true) : null;
    if (!$demande || !$annonce || !in_array($demande['statut'], ['en_attente', 'en_file', 'acceptee', 'validee'], true)) {
        $pdo->rollBack();
        return ['warning', "Cette transaction ne peut pas être annulée."];
    }
    $pdo->prepare("UPDATE demande_achat SET statut = 'annulee_equipe', motif = ?, id_moderateur = ?, en_litige = 0, date_fin = NOW() WHERE id_demande = ?")
        ->execute([$motif, $idModerateur, $idDemande]);
    $enFile = [];
    if ($demande['statut'] === 'acceptee') {
        $enFile = remettreAnnonceEnVente($pdo, $demande);
    }
    $pointsRetires = false;
    if ($demande['statut'] === 'validee') {
        $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'retiree', motif_moderation = ? WHERE id_exemplaire = ?")->execute(["Vente annulée par l'équipe : $motif", $demande['id_annonce']]);
        $pointsRetires = changerStatutMouvement($pdo, (int) $demande['id_vendeur'], 'vente', 'vente:' . $idDemande, ['valide', 'en_attente'], 'annule');
    }
    $conversation = derniereConversationObjet($pdo, 'vente', $idDemande, (int) $demande['id_acheteur']);
    if ($conversation && $conversation['statut'] === 'ouverte') {
        fermerConversation($pdo, (int) $conversation['id_conversation'], "L'équipe a annulé la transaction : $motif", 'close', $motif);
    } elseif ($conversation) {
        ajouterMessageSysteme($pdo, (int) $conversation['id_conversation'], "L'équipe a annulé la transaction : $motif");
    }
    $pdo->commit();

    insertLog('VENTE', "Transaction #$idDemande annulée par l'équipe ($motif)");
    foreach ([[$demande['acheteur_email'], $demande['acheteur_pseudo']], [$demande['vendeur_email'], $demande['vendeur_pseudo']]] as [$email, $pseudo]) {
        notifierParMail($email, "Transaction annulée par l'équipe",
            "Bonjour $pseudo,\n\nL'équipe BIBLIOccaz a annulé la transaction pour « {$demande['titre']} ».\n\nMotif : $motif"
            . ($pointsRetires ? "\n\nLes points liés à cette vente sont annulés." : ''),
            SITE_URL . "/transaction.php?id=$idDemande", "Voir la transaction");
    }
    foreach ($enFile as $attente) {
        notifierParMail($attente['email'], "Le livre est de nouveau disponible",
            "Bonjour {$attente['pseudo']},\n\n« {$demande['titre']} » est de nouveau disponible : votre demande sort de la file d'attente.",
            SITE_URL . "/transaction.php?id={$attente['id_demande']}", "Voir ma demande");
    }
    return ['success', "Transaction annulée. Les deux membres sont prévenus" . ($pointsRetires ? " et les points du vendeur annulés." : '.')];
}

function validerVenteParEquipe(PDO $pdo, int $idDemande, int $idModerateur): array
{
    $demande = demandeParId($pdo, $idDemande);
    if ($demande && $demande['statut'] === 'acceptee') {
        [$type, $message] = conclureVente($pdo, $idDemande, null, $idModerateur);
        if ($type !== 'success') {
            return [$type, $message];
        }
    }
    return confirmerVente($pdo, $idDemande, null, 'equipe', $idModerateur);
}

function classerLitige(PDO $pdo, int $idDemande, int $idModerateur): array
{
    $demande = demandeParId($pdo, $idDemande);
    if (!$demande || !(int) $demande['en_litige']) {
        return ['warning', "Ce litige est déjà traité."];
    }
    if (receptionAConfirmer($demande)) {
        [$type, $message] = confirmerVente($pdo, $idDemande, null, 'equipe', $idModerateur);
        return [$type, $type === 'success' ? "Litige classé : la vente est maintenue et les points du vendeur sont crédités." : $message];
    }
    $pdo->prepare("UPDATE demande_achat SET en_litige = 0, id_moderateur = ? WHERE id_demande = ? AND en_litige = 1")->execute([$idModerateur, $idDemande]);
    if ($idConversation = conversationOuverte($pdo, 'vente', $idDemande)) {
        ajouterMessageSysteme($pdo, $idConversation, "L'équipe a examiné le problème signalé et l'a classé : la transaction continue normalement.");
    }
    insertLog('VENTE', "Litige de la transaction #$idDemande classé");
    return ['success', "Litige classé : la transaction continue."];
}

function nombreVentesASurveiller(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM demande_achat WHERE en_litige = 1 OR (statut = 'acceptee' AND date_acceptation < NOW() - INTERVAL " . VENTE_RETARD_JOURS . " DAY)")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function annonceADesDemandes(PDO $pdo, int $idAnnonce): bool
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_achat WHERE id_annonce = ?");
        $stmt->execute([$idAnnonce]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function cloreDemandesEnAttente(PDO $pdo, int $idAnnonce, string $motif): void
{
    try {
        $stmt = $pdo->prepare("
            SELECT d.id_demande, a.email, a.pseudo, l.titre FROM demande_achat d
            INNER JOIN `user` a ON a.id = d.id_acheteur
            INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce INNER JOIN livre l ON l.id_livre = e.id_livre
            WHERE d.id_annonce = ? AND d.statut IN ('en_attente', 'en_file')");
        $stmt->execute([$idAnnonce]);
        $demandes = $stmt->fetchAll();
        $maj = $pdo->prepare("UPDATE demande_achat SET statut = 'refusee', motif = ?, date_fin = NOW() WHERE id_demande = ? AND statut IN ('en_attente', 'en_file')");
        foreach ($demandes as $demande) {
            $maj->execute([$motif, $demande['id_demande']]);
            fermerConversationsObjet($pdo, 'vente', (int) $demande['id_demande'], "Demande close : $motif.");
            notifierParMail($demande['email'], "Votre demande d'achat est close",
                "Bonjour {$demande['pseudo']},\n\nVotre demande pour « {$demande['titre']} » est close : $motif.",
                SITE_URL . "/transaction.php?id={$demande['id_demande']}", "Voir la demande");
        }
    } catch (PDOException $e) {
    }
}

function telechargerRecuVente(array $demande): void
{
    require_once __DIR__ . '/vendor/autoload.php';
    $echapper = fn($texte) => htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
    $prix = number_format((float) $demande['prix'], 2, ',', ' ') . ' €';
    $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>
        body { font-family: Helvetica, Arial, sans-serif; color: #2b2b2b; font-size: 12px; }
        .entete { border-bottom: 2px solid #274e13; padding-bottom: 10px; margin-bottom: 22px; }
        .marque { color: #274e13; font-size: 20px; font-weight: bold; }
        .numero { float: right; text-align: right; color: #555; }
        h1 { font-size: 17px; margin: 0 0 4px; color: #274e13; }
        .sous-titre { color: #666; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #cfd8cc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #e8f0e4; width: 32%; }
        .total td { font-size: 15px; font-weight: bold; color: #274e13; }
        .mention { color: #666; font-size: 10px; line-height: 1.5; margin-top: 24px; }
    </style></head><body>
        <div class="entete"><span class="numero">Reçu n° ' . $echapper($demande['numero_recu']) . '<br>Émis le ' . date('d/m/Y') . '</span>
        <span class="marque">BIBLIOccaz</span><br>Livres d\'occasion · ventes entre lecteurs</div>
        <h1>Reçu de vente entre particuliers</h1>
        <p class="sous-titre">Vente conclue le ' . date('d/m/Y à H:i', strtotime((string) $demande['date_validation'])) . ' sur BIBLIOccaz.</p>
        <table>
            <tr><th>Livre</th><td><strong>' . $echapper($demande['titre']) . '</strong>' . ($demande['auteur'] ? '<br>' . $echapper($demande['auteur']) : '') . '</td></tr>
            <tr><th>État annoncé</th><td>' . $echapper(libelleEtat($demande['etat'])) . '</td></tr>
            <tr><th>Vendeur</th><td>@' . $echapper($demande['vendeur_pseudo']) . '</td></tr>
            <tr><th>Acheteur</th><td>@' . $echapper($demande['acheteur_pseudo']) . '</td></tr>
            <tr><th>Remise</th><td>' . $echapper(libelleRemiseDemande($demande)) . '</td></tr>
            <tr><th>Demande</th><td>n° ' . (int) $demande['id_demande'] . ' du ' . date('d/m/Y', strtotime((string) $demande['date_demande'])) . ', acceptée le '
                . ($demande['date_acceptation'] ? date('d/m/Y', strtotime((string) $demande['date_acceptation'])) : '—') . '</td></tr>
            <tr class="total"><th>Prix payé au vendeur</th><td>' . $prix . '</td></tr>
        </table>
        <p class="mention">Ce reçu atteste d\'une vente d\'occasion entre deux particuliers, mise en relation par BIBLIOccaz.
        Le paiement a été réglé directement entre l\'acheteur et le vendeur : BIBLIOccaz n\'a encaissé aucune somme et ne prélève aucune commission.
        Les adresses et coordonnées des membres ne figurent pas sur ce document.</p>
    </body></html>';

    $options = new Dompdf\Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('recu_' . $demande['numero_recu'] . '.pdf', ['Attachment' => true]);
}
