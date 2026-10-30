<?php
require_once __DIR__ . '/dons_lib.php';
require_once __DIR__ . '/messagerie_lib.php';

function suiviModerationDonsDisponible(PDO $pdo): bool
{
    try {
        $requete = $pdo->prepare('SELECT id_moderation FROM don_moderation LIMIT 0');
        $requete->execute();
        return true;
    } catch (PDOException $erreur) {
        return false;
    }
}

function modererDon(PDO $pdo, int $idDon, int $idModerateur, string $action, string $motif, string $statutAttendu, ?int $beneficiaireAttendu, string $reservationAttendue): array
{
    $motif = trim($motif);
    if (!in_array($action, ['annuler', 'liberer'], true) || mb_strlen($motif) < 10 || mb_strlen($motif) > 500) {
        return ['danger', 'Choisissez une action et indiquez un motif de 10 à 500 caractères.'];
    }
    try {
        $pdo->beginTransaction();
        $requete = $pdo->prepare('SELECT role, statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
        $requete->execute([$idModerateur]);
        $membre = $requete->fetch();
        if (!$membre || !in_array($membre['role'], ['admin', 'gestionnaire', 'moderateur'], true) || !in_array($membre['statut'], ['actif', 'valide_moderateur'], true) || !(int) $membre['email_verifie']) {
            throw new DomainException('Votre compte ne permet pas de modérer les dons.');
        }
        $requete = $pdo->prepare('SELECT * FROM don WHERE id_don = ? FOR UPDATE');
        $requete->execute([$idDon]);
        $don = $requete->fetch();
        if (!$don || !in_array($don['statut'], ['disponible', 'reserve'], true)) {
            throw new DomainException('Ce don ne peut plus être modifié.');
        }
        if ($don['statut'] !== $statutAttendu || ($don['id_beneficiaire'] === null ? null : (int) $don['id_beneficiaire']) !== $beneficiaireAttendu || (string) ($don['date_reservation'] ?? '') !== $reservationAttendue) {
            throw new DomainException('Ce don a changé depuis votre consultation. Vérifiez son nouvel état avant de recommencer.');
        }
        if ($action === 'liberer' && $don['statut'] !== 'reserve') {
            throw new DomainException('Seule une réservation en cours peut être libérée.');
        }
        $nouveauStatut = $action === 'annuler' ? 'annule' : 'disponible';
        $requete = $pdo->prepare('INSERT INTO don_moderation (id_don, id_moderateur, action, motif, ancien_statut, nouveau_statut, id_beneficiaire) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $requete->execute([$idDon, $idModerateur, $action, $motif, $don['statut'], $nouveauStatut, $don['id_beneficiaire']]);
        if ($action === 'liberer') {
            $pdo->prepare("UPDATE don SET statut = 'disponible', id_beneficiaire = NULL, date_reservation = NULL WHERE id_don = ?")->execute([$idDon]);
        } else {
            $pdo->prepare("UPDATE don SET statut = 'annule' WHERE id_don = ?")->execute([$idDon]);
        }
        $message = $action === 'annuler' ? "Le don a été annulé par l’équipe." : "La réservation a été libérée par l’équipe.";
        $requete = $pdo->prepare("SELECT id_conversation FROM conversation WHERE type_objet = 'don' AND id_objet = ? AND statut = 'ouverte' FOR UPDATE");
        $requete->execute([$idDon]);
        foreach ($requete->fetchAll(PDO::FETCH_COLUMN) as $idConversation) {
            fermerConversation($pdo, (int) $idConversation, $message . ' Motif : ' . $motif);
        }
        $pdo->commit();
    } catch (DomainException $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $erreur->getMessage()];
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Modération du don #' . $idDon . ' : ' . $erreur->getMessage());
        return ['danger', 'Le don n’a pas été modifié. Merci de réessayer.'];
    }
    insertLog('DON', 'Don #' . $idDon . ' : ' . $action . ' par l’équipe, motif : ' . $motif);
    $requete = $pdo->prepare('SELECT id, email, pseudo FROM `user` WHERE id IN (?, ?)');
    $requete->execute([(int) $don['id_donneur'], (int) ($don['id_beneficiaire'] ?? 0)]);
    $mailsEnvoyes = true;
    foreach ($requete->fetchAll() as $destinataire) {
        $precision = $action === 'liberer' ? ($don['date_limite'] < date('Y-m-d') ? 'La date limite est dépassée : ce don reste expiré et ne sera pas proposé à la réservation.' : 'Le don est de nouveau disponible à la réservation.') : 'Ce don n’est plus proposé sur le site.';
        $envoye = notifierParMail($destinataire['email'], $action === 'annuler' ? 'Don annulé par l’équipe' : 'Réservation de don libérée',
            'Bonjour ' . $destinataire['pseudo'] . ",\n\n" . $message . ' Livre : « ' . $don['titre'] . " ».\n\nMotif : " . $motif . "\n\n" . $precision,
            SITE_URL . '/mes_dons.php', 'Voir mes dons');
        $mailsEnvoyes = $envoye && $mailsEnvoyes;
    }
    return [$mailsEnvoyes ? 'success' : 'warning', $message . ($mailsEnvoyes ? ' Les personnes concernées ont été prévenues.' : ' Un email n’a pas pu être envoyé ; l’action est enregistrée.')];
}
