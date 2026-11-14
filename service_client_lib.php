<?php
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/notifications_membre_lib.php';

const SERVICE_CATEGORIES = [
    'commande' => 'Commande ou paiement',
    'livraison' => 'Livraison',
    'retour' => 'Retour ou remboursement',
    'compte' => 'Mon compte',
    'don' => 'Dons et échanges',
    'evenement' => 'Événements',
    'technique' => 'Problème sur le site',
    'autre' => 'Autre question',
];
const SERVICE_STATUTS = [
    'nouvelle' => ['Nouvelle', 'attente'],
    'en_cours' => ['En cours', 'en_cours'],
    'attente_client' => ['Réponse envoyée', 'en_cours'],
    'resolue' => ['Résolue', 'termine'],
];
const SERVICE_SUJET_MAX = 150;
const SERVICE_MESSAGE_MIN = 10;
const SERVICE_MESSAGE_MAX = 2000;
const SERVICE_DEMANDES_OUVERTES_MAX = 5;
const SERVICE_DEMANDES_PAR_JOUR = 5;
const SERVICE_MESSAGES_PAR_HEURE = 20;
const SERVICE_REOUVERTURE_JOURS = 14;

function serviceClientDisponible(PDO $pdo): bool
{
    try {
        $pdo->query("SELECT id_demande FROM demande_client LIMIT 0");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function libelleStatutDemande(string $statut): string
{
    return SERVICE_STATUTS[$statut][0] ?? ucfirst($statut);
}

function normaliserMessageService(string $contenu): string
{
    $contenu = trim(preg_replace("/\n{3,}/", "\n\n", str_replace(["\r\n", "\r"], "\n", $contenu)));
    if (!mb_check_encoding($contenu, 'UTF-8') || mb_strlen($contenu) < SERVICE_MESSAGE_MIN) {
        throw new DomainException('Votre message doit compter au moins ' . SERVICE_MESSAGE_MIN . ' caractères.');
    }
    if (mb_strlen($contenu) > SERVICE_MESSAGE_MAX) {
        throw new DomainException('Votre message dépasse ' . SERVICE_MESSAGE_MAX . ' caractères.');
    }
    return $contenu;
}

function verifierCadenceService(PDO $pdo, int $idUser): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client_message WHERE id_auteur = ? AND cote = 'client' AND date_envoi > NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$idUser]);
    if ((int) $stmt->fetchColumn() >= SERVICE_MESSAGES_PAR_HEURE) {
        throw new DomainException('Vous avez envoyé beaucoup de messages en une heure. Réessayez un peu plus tard.');
    }
}

function commandesPourService(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare("SELECT id_commande, date_commande, total, statut FROM commande WHERE id_user = ? ORDER BY date_commande DESC LIMIT 20");
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function creerDemandeClient(PDO $pdo, int $idUser, string $categorie, string $sujet, string $message, int $idCommande): array
{
    $sujet = trim(preg_replace('/\s+/u', ' ', $sujet));
    try {
        if (!isset(SERVICE_CATEGORIES[$categorie])) {
            throw new DomainException('Choisissez une catégorie.');
        }
        if (mb_strlen($sujet) < 5 || mb_strlen($sujet) > SERVICE_SUJET_MAX) {
            throw new DomainException('Le sujet doit compter de 5 à ' . SERVICE_SUJET_MAX . ' caractères.');
        }
        $message = normaliserMessageService($message);
        $pdo->beginTransaction();
        $verrou = $pdo->prepare("SELECT id FROM user WHERE id = ? FOR UPDATE");
        $verrou->execute([$idUser]);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client WHERE id_user = ? AND statut <> 'resolue'");
        $stmt->execute([$idUser]);
        if ((int) $stmt->fetchColumn() >= SERVICE_DEMANDES_OUVERTES_MAX) {
            throw new DomainException('Vous avez déjà ' . SERVICE_DEMANDES_OUVERTES_MAX . ' demandes en cours : répondez dans l’une d’elles.');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client WHERE id_user = ? AND date_creation > NOW() - INTERVAL 1 DAY");
        $stmt->execute([$idUser]);
        if ((int) $stmt->fetchColumn() >= SERVICE_DEMANDES_PAR_JOUR) {
            throw new DomainException('Vous avez ouvert ' . SERVICE_DEMANDES_PAR_JOUR . ' demandes aujourd’hui. Réessayez demain.');
        }
        verifierCadenceService($pdo, $idUser);
        $commande = null;
        if ($idCommande > 0) {
            $stmt = $pdo->prepare("SELECT id_commande FROM commande WHERE id_commande = ? AND id_user = ?");
            $stmt->execute([$idCommande, $idUser]);
            $commande = $stmt->fetchColumn() ?: null;
            if (!$commande) {
                throw new DomainException('Cette commande ne vous appartient pas.');
            }
        }
        $pdo->prepare("INSERT INTO demande_client (id_user, categorie, sujet, id_commande) VALUES (?, ?, ?, ?)")->execute([$idUser, $categorie, $sujet, $commande]);
        $idDemande = (int) $pdo->lastInsertId();
        $reference = 'SC-' . date('Y') . '-' . str_pad((string) $idDemande, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE demande_client SET reference = ? WHERE id_demande = ?")->execute([$reference, $idDemande]);
        $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'client', ?)")->execute([$idDemande, $idUser, $message]);
        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        insertLog('ERREUR', 'Service client : ' . $e->getMessage());
        return ['danger', 'La demande n’a pas pu être envoyée. Merci de réessayer.'];
    }
    insertLog('SERVICE', "Demande $reference ouverte ($categorie)");
    return ['success', "Demande $reference envoyée : l’équipe vous répond ici, vous serez prévenu par notification et par mail.", $idDemande];
}

function demandeClient(PDO $pdo, int $idDemande, ?int $idUser = null): ?array
{
    $stmt = $pdo->prepare("SELECT d.*, u.pseudo, u.email, a.pseudo AS pseudo_assigne FROM demande_client d
        INNER JOIN user u ON u.id = d.id_user LEFT JOIN user a ON a.id = d.id_assigne
        WHERE d.id_demande = ?" . ($idUser !== null ? ' AND d.id_user = ?' : ''));
    $stmt->execute($idUser !== null ? [$idDemande, $idUser] : [$idDemande]);
    return $stmt->fetch() ?: null;
}

function messagesDemandeClient(PDO $pdo, int $idDemande): array
{
    $stmt = $pdo->prepare("SELECT m.*, u.pseudo, u.role FROM demande_client_message m LEFT JOIN user u ON u.id = m.id_auteur WHERE m.id_demande = ? ORDER BY m.date_envoi, m.id_message");
    $stmt->execute([$idDemande]);
    return $stmt->fetchAll();
}

function demandesDuClient(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare("SELECT d.*, (SELECT COUNT(*) FROM demande_client_message m WHERE m.id_demande = d.id_demande) AS nb_messages FROM demande_client d WHERE d.id_user = ? ORDER BY d.statut = 'resolue', d.date_maj DESC");
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function nombreReponsesServiceNonLues(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande_client WHERE id_user = ? AND non_lu_client = 1");
        $stmt->execute([$idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function marquerDemandeLue(PDO $pdo, int $idDemande, string $cote): void
{
    $colonne = $cote === 'client' ? 'non_lu_client' : 'non_lu_equipe';
    $pdo->prepare("UPDATE demande_client SET $colonne = 0 WHERE id_demande = ?")->execute([$idDemande]);
}

function repondreDemandeClient(PDO $pdo, int $idDemande, int $idUser, string $message): array
{
    try {
        $message = normaliserMessageService($message);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM demande_client WHERE id_demande = ? AND id_user = ? FOR UPDATE");
        $stmt->execute([$idDemande, $idUser]);
        $demande = $stmt->fetch();
        if (!$demande) {
            throw new DomainException('Cette demande ne vous concerne pas.');
        }
        if ($demande['statut'] === 'resolue' && strtotime((string) $demande['date_cloture'] . ' +' . SERVICE_REOUVERTURE_JOURS . ' days') < time()) {
            throw new DomainException('Cette demande est close depuis plus de ' . SERVICE_REOUVERTURE_JOURS . ' jours : ouvrez une nouvelle demande.');
        }
        verifierCadenceService($pdo, $idUser);
        $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'client', ?)")->execute([$idDemande, $idUser, $message]);
        $pdo->prepare("UPDATE demande_client SET statut = IF(statut = 'nouvelle', 'nouvelle', 'en_cours'), non_lu_equipe = 1, date_maj = NOW(), date_cloture = NULL WHERE id_demande = ?")->execute([$idDemande]);
        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        insertLog('ERREUR', 'Service client : ' . $e->getMessage());
        return ['danger', 'Le message n’a pas pu être envoyé.'];
    }
    return ['success', $demande['statut'] === 'resolue' ? 'Message envoyé : la demande est rouverte.' : 'Message envoyé à l’équipe.'];
}

function cloreDemandeParClient(PDO $pdo, int $idDemande, int $idUser): array
{
    $stmt = $pdo->prepare("UPDATE demande_client SET statut = 'resolue', date_cloture = NOW(), date_maj = NOW() WHERE id_demande = ? AND id_user = ? AND statut <> 'resolue'");
    $stmt->execute([$idDemande, $idUser]);
    if (!$stmt->rowCount()) {
        return ['warning', 'Cette demande est déjà close.'];
    }
    $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'systeme', 'Le client a indiqué que sa demande est réglée.')")->execute([$idDemande, $idUser]);
    return ['success', 'Merci ! Votre demande est close. Vous pouvez la rouvrir en écrivant dans les ' . SERVICE_REOUVERTURE_JOURS . ' jours.'];
}

function repondreDemandeEquipe(PDO $pdo, int $idDemande, int $idActeur, string $message, bool $resoudre, string $dateMajAttendue): array
{
    try {
        $message = normaliserMessageService($message);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT d.*, u.email, u.pseudo FROM demande_client d INNER JOIN user u ON u.id = d.id_user WHERE d.id_demande = ? FOR UPDATE");
        $stmt->execute([$idDemande]);
        $demande = $stmt->fetch();
        if (!$demande) {
            throw new DomainException('Cette demande n’existe pas.');
        }
        if ($dateMajAttendue !== '' && $demande['date_maj'] !== $dateMajAttendue) {
            throw new DomainException('Le client ou un collègue vient d’écrire : relisez le fil avant de répondre.');
        }
        $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'equipe', ?)")->execute([$idDemande, $idActeur, $message]);
        $pdo->prepare("UPDATE demande_client SET statut = ?, id_assigne = COALESCE(id_assigne, ?), non_lu_client = 1, non_lu_equipe = 0, date_maj = NOW(), date_cloture = ? WHERE id_demande = ?")
            ->execute([$resoudre ? 'resolue' : 'attente_client', $idActeur, $resoudre ? date('Y-m-d H:i:s') : null, $idDemande]);
        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        insertLog('ERREUR', 'Service client : ' . $e->getMessage());
        return ['danger', 'La réponse n’a pas pu être envoyée.'];
    }
    notifierMembre($pdo, (int) $demande['id_user'], 'service_client', 'Réponse de l’équipe à votre demande ' . $demande['reference'] . ($resoudre ? ' (marquée comme résolue).' : '.'), 'service_client.php?id=' . $idDemande);
    $envoye = notifierParMail($demande['email'], 'Réponse à votre demande ' . $demande['reference'],
        "Bonjour {$demande['pseudo']},\n\nL’équipe BIBLIOccaz a répondu à votre demande « {$demande['sujet']} » :\n\n" . mb_substr($message, 0, 600) . (mb_strlen($message) > 600 ? '…' : '') . "\n\nRépondez directement depuis votre espace.",
        SITE_URL . '/service_client.php?id=' . $idDemande, 'Voir la conversation');
    insertLog('SERVICE', "Réponse de l'équipe à {$demande['reference']}" . ($resoudre ? ' (résolue)' : ''));
    return ['success', 'Réponse envoyée' . ($resoudre ? ', demande résolue' : '') . '.' . ($envoye ? ' Le client est prévenu par mail.' : ' Le mail n’a pas pu partir.')];
}

function attribuerDemandeClient(PDO $pdo, int $idDemande, int $idActeur, int $idAssigne, string $note): array
{
    require_once __DIR__ . '/messagerie_equipe_lib.php';
    $note = trim($note);
    if (mb_strlen($note) > 500) {
        return ['warning', 'La note dépasse 500 caractères.'];
    }
    if (!estMembreEquipeActif($pdo, $idAssigne)) {
        return ['warning', 'Ce membre ne fait pas partie de l’équipe active.'];
    }
    $demande = demandeClient($pdo, $idDemande);
    if (!$demande) {
        return ['warning', 'Cette demande n’existe pas.'];
    }
    $pdo->prepare("UPDATE demande_client SET id_assigne = ?, statut = IF(statut = 'nouvelle', 'en_cours', statut) WHERE id_demande = ?")->execute([$idAssigne, $idDemande]);
    $stmt = $pdo->prepare("SELECT pseudo FROM user WHERE id = ?");
    $stmt->execute([$idAssigne]);
    $pseudo = (string) $stmt->fetchColumn();
    $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'interne', ?)")
        ->execute([$idDemande, $idActeur, $idAssigne === $idActeur ? 'Demande prise en charge.' : 'Demande confiée à @' . $pseudo . ($note !== '' ? ' : ' . $note : '.')]);
    if ($idAssigne !== $idActeur) {
        try {
            $idConversation = ouvrirConversationPriveeEquipe($pdo, $idActeur, $idAssigne);
            envoyerMessageInterneEquipe($pdo, $idConversation, $idActeur, "Je te confie la demande client {$demande['reference']} (« {$demande['sujet']} ») : demande_client.php?id=$idDemande" . ($note !== '' ? "\n\n$note" : ''));
        } catch (Throwable $e) {
            return ['warning', 'Demande attribuée à @' . $pseudo . ', mais le message dans la messagerie de l’équipe n’a pas pu partir : ' . $e->getMessage()];
        }
        return ['success', 'Demande confiée à @' . $pseudo . ' : un message privé lui a été envoyé dans la messagerie de l’équipe.'];
    }
    return ['success', 'Vous avez pris en charge la demande.'];
}

function changerStatutDemandeEquipe(PDO $pdo, int $idDemande, int $idActeur, string $statut): array
{
    if (!in_array($statut, ['resolue', 'en_cours'], true)) {
        return ['warning', 'Statut inconnu.'];
    }
    $stmt = $pdo->prepare("UPDATE demande_client SET statut = ?, date_cloture = ?, non_lu_equipe = 0, date_maj = NOW() WHERE id_demande = ? AND statut <> ?");
    $stmt->execute([$statut, $statut === 'resolue' ? date('Y-m-d H:i:s') : null, $idDemande, $statut]);
    if (!$stmt->rowCount()) {
        return ['warning', 'La demande a déjà ce statut.'];
    }
    $pdo->prepare("INSERT INTO demande_client_message (id_demande, id_auteur, cote, contenu) VALUES (?, ?, 'interne', ?)")
        ->execute([$idDemande, $idActeur, $statut === 'resolue' ? 'Demande marquée comme résolue.' : 'Demande rouverte.']);
    if ($statut === 'resolue' && ($demande = demandeClient($pdo, $idDemande))) {
        notifierMembre($pdo, (int) $demande['id_user'], 'service_client', 'Votre demande ' . $demande['reference'] . ' est marquée comme résolue.', 'service_client.php?id=' . $idDemande);
    }
    return ['success', $statut === 'resolue' ? 'Demande résolue.' : 'Demande rouverte.'];
}

function nombreDemandesClientATraiter(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM demande_client WHERE statut = 'nouvelle' OR (statut <> 'resolue' AND non_lu_equipe = 1)")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
