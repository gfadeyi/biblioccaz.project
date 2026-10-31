<?php
require_once __DIR__ . '/dons_lib.php';
require_once __DIR__ . '/messagerie_lib.php';

function modifierContenuDonEquipe(PDO $pdo, int $idDon, int $idActeur, array $saisie): array
{
    $valeurs = [];
    foreach (['titre', 'auteur', 'lieu_retrait', 'description', 'conditions_retrait', 'motif'] as $champ) {
        if (!is_string($saisie[$champ] ?? null)) { return ['warning', 'Champs invalides.']; }
        $valeurs[$champ] = trim($saisie[$champ]);
        $maximum = $champ === 'description' ? 1000 : ($champ === 'motif' ? 500 : 255);
        if (mb_strlen($valeurs[$champ]) > $maximum) { return ['warning', 'Un champ dépasse la longueur autorisée.']; }
    }
    if ($valeurs['titre'] === '' || $valeurs['lieu_retrait'] === '' || mb_strlen($valeurs['motif']) < 10) { return ['warning', 'Titre, lieu et motif de dix caractères minimum requis.']; }
    try {
        $pdo->beginTransaction();
        $requete = $pdo->prepare('SELECT role, statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
        $requete->execute([$idActeur]);
        $acteur = $requete->fetch();
        if (!$acteur || !in_array($acteur['role'], ['admin', 'gestionnaire', 'moderateur'], true) || !in_array($acteur['statut'], ['actif', 'valide_moderateur'], true) || !(int) $acteur['email_verifie']) { throw new DomainException('Accès refusé.'); }
        $requete = $pdo->prepare('SELECT * FROM don WHERE id_don = ? FOR UPDATE');
        $requete->execute([$idDon]);
        $don = $requete->fetch();
        if (!$don || !in_array($don['statut'], ['disponible', 'refuse', 'en_validation'], true)) { throw new DomainException('Ce don a changé ou ne peut plus être modifié.'); }
        $idLivre = $valeurs['titre'] === $don['titre'] && $valeurs['auteur'] === (string) $don['auteur'] ? $don['id_livre'] : null;
        $pdo->prepare("UPDATE don SET titre = ?, auteur = ?, lieu_retrait = ?, description = ?, conditions_retrait = ?, id_livre = ?, statut = 'en_validation' WHERE id_don = ?")
            ->execute([$valeurs['titre'], $valeurs['auteur'], $valeurs['lieu_retrait'], $valeurs['description'], $valeurs['conditions_retrait'], $idLivre, $idDon]);
        $pdo->prepare("INSERT INTO don_moderation (id_don, id_moderateur, action, motif, ancien_statut, nouveau_statut, id_beneficiaire) VALUES (?, ?, 'modifier', ?, ?, 'en_validation', NULL)")
            ->execute([$idDon, $idActeur, $valeurs['motif'], $don['statut']]);
        $pdo->commit();
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $erreur instanceof DomainException ? $erreur->getMessage() : 'La correction n’a pas pu être enregistrée.'];
    }
    insertLog('DON', 'Contenu du don #' . $idDon . ' modifié par l’équipe, nouvelle validation requise');
    $requete = $pdo->prepare('SELECT email FROM `user` WHERE id = ?');
    $requete->execute([(int) $don['id_donneur']]);
    $envoye = notifierParMail((string) $requete->fetchColumn(), 'Votre don a été corrigé par l’équipe', 'Le contenu de votre don « ' . $valeurs['titre'] . ' » a été corrigé. Il attend une nouvelle validation. Motif : ' . $valeurs['motif'], SITE_URL . '/mes_dons.php', 'Voir mes dons');
    return [$envoye ? 'success' : 'warning', 'Correction enregistrée et don remis en validation.' . ($envoye ? ' Le donneur a été prévenu.' : ' Le mail n’a pas pu être envoyé.')];
}

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
    if (!in_array($action, ['annuler', 'liberer', 'valider', 'refuser'], true) || mb_strlen($motif) < 10 || mb_strlen($motif) > 500) {
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
        if (!$don || !in_array($don['statut'], ['disponible', 'reserve', 'en_validation', 'refuse'], true)) {
            throw new DomainException('Ce don ne peut plus être modifié.');
        }
        if ($don['statut'] !== $statutAttendu || ($don['id_beneficiaire'] === null ? null : (int) $don['id_beneficiaire']) !== $beneficiaireAttendu || (string) ($don['date_reservation'] ?? '') !== $reservationAttendue) {
            throw new DomainException('Ce don a changé depuis votre consultation. Vérifiez son nouvel état avant de recommencer.');
        }
        if ($action === 'liberer' && $don['statut'] !== 'reserve') {
            throw new DomainException('Seule une réservation en cours peut être libérée.');
        }
        if (in_array($action, ['valider', 'refuser'], true) && $don['statut'] !== 'en_validation') {
            throw new DomainException('Cette action ne correspond plus au statut du don.');
        }
        if ($action === 'valider') {
            $requete = $pdo->prepare('SELECT statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
            $requete->execute([(int) $don['id_donneur']]);
            $donneur = $requete->fetch();
            if (!$donneur || !in_array($donneur['statut'], ['actif', 'valide_moderateur'], true) || !(int) $donneur['email_verifie'] || $don['date_limite'] < date('Y-m-d') || !$don['photo'] || !is_file(__DIR__ . '/img/dons/' . $don['photo']) || !getimagesize(__DIR__ . '/img/dons/' . $don['photo'])) {
                throw new DomainException('Vérifiez le compte du donneur, la photo et la date limite avant publication.');
            }
        }
        $nouveauStatut = match ($action) { 'annuler' => 'annule', 'refuser' => 'refuse', default => 'disponible' };
        $requete = $pdo->prepare('INSERT INTO don_moderation (id_don, id_moderateur, action, motif, ancien_statut, nouveau_statut, id_beneficiaire) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $requete->execute([$idDon, $idModerateur, $action, $motif, $don['statut'], $nouveauStatut, $don['id_beneficiaire']]);
        if ($action === 'liberer') {
            $pdo->prepare("UPDATE don SET statut = 'disponible', id_beneficiaire = NULL, date_reservation = NULL WHERE id_don = ?")->execute([$idDon]);
        } else {
            $pdo->prepare('UPDATE don SET statut = ? WHERE id_don = ?')->execute([$nouveauStatut, $idDon]);
        }
        $message = match ($action) {
            'annuler' => 'Le don a été annulé par l’équipe.',
            'liberer' => 'La réservation a été libérée par l’équipe.',
            'valider' => 'Le don a été validé et publié par l’équipe.',
            'refuser' => 'Le don doit être corrigé avant publication.',
        };
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
        $precision = $action === 'liberer' ? ($don['date_limite'] < date('Y-m-d') ? 'La date limite est dépassée : ce don reste expiré et ne sera pas proposé à la réservation.' : 'Le don est de nouveau disponible à la réservation.') : ($action === 'valider' ? 'Le don est maintenant visible et réservable.' : ($action === 'refuser' ? 'Modifiez votre don depuis Mes dons puis soumettez-le à nouveau.' : 'Ce don n’est plus proposé sur le site.'));
        $envoye = notifierParMail($destinataire['email'], $action === 'valider' ? 'Votre don est publié' : ($action === 'refuser' ? 'Votre don est à corriger' : ($action === 'annuler' ? 'Don annulé par l’équipe' : 'Réservation de don libérée')),
            'Bonjour ' . $destinataire['pseudo'] . ",\n\n" . $message . ' Livre : « ' . $don['titre'] . " ».\n\nMotif : " . $motif . "\n\n" . $precision,
            SITE_URL . '/mes_dons.php', 'Voir mes dons');
        $mailsEnvoyes = $envoye && $mailsEnvoyes;
    }
    return [$mailsEnvoyes ? 'success' : 'warning', $message . ($mailsEnvoyes ? ' Les personnes concernées ont été prévenues.' : ' Un email n’a pas pu être envoyé ; l’action est enregistrée.')];
}
