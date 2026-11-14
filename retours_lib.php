<?php
require_once __DIR__ . '/livraison_lib.php';
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/notifications_membre_lib.php';
require_once __DIR__ . '/points_lib.php';

const RETOUR_DELAI_JOURS = 14;
const RETOUR_REMBOURSEMENT_DELAI_JOURS = 30;
const RETOUR_MESSAGE_MAX = 1000;
const RETOUR_NOTE_MAX = 500;
const RETOUR_MOTIFS = [
    'abime' => 'Livre abîmé à la réception',
    'non_conforme' => 'État différent de l’annonce',
    'erreur' => 'Mauvais livre reçu',
    'retractation' => 'Je change d’avis (rétractation)',
    'autre' => 'Autre raison',
];
const RETOUR_MOTIFS_REMBOURSEMENT = [
    'annulation' => 'Suivi du remboursement d’une commande annulée',
    'autre' => 'Autre question sur le remboursement',
];
const RETOUR_STATUTS = [
    'demande' => ['Demandé', 'attente'],
    'accepte' => ['Accepté', 'en_cours'],
    'recu' => ['Reçu', 'en_cours'],
    'rembourse' => ['Remboursé', 'termine'],
    'refuse' => ['Refusé', 'refuse'],
    'annule' => ['Annulé', 'refuse'],
];
const RETOUR_STATUTS_ACTIFS = ['demande', 'accepte', 'recu'];
const RETOUR_TRANSITIONS = [
    'retour' => ['demande' => ['accepte', 'refuse'], 'accepte' => ['recu'], 'recu' => ['rembourse']],
    'remboursement' => ['demande' => ['accepte', 'refuse'], 'accepte' => ['rembourse']],
];

function retoursDisponibles(PDO $pdo): bool
{
    try {
        $pdo->query("SELECT id_retour FROM retour_commande LIMIT 0");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function libelleStatutRetour(string $statut): string
{
    return RETOUR_STATUTS[$statut][0] ?? ucfirst($statut);
}

function libelleMotifRetour(string $type, string $motif): string
{
    return ($type === 'remboursement' ? RETOUR_MOTIFS_REMBOURSEMENT : RETOUR_MOTIFS)[$motif] ?? 'Autre raison';
}

function etapesRetour(string $type): array
{
    return $type === 'remboursement' ? ['demande', 'accepte', 'rembourse'] : ['demande', 'accepte', 'recu', 'rembourse'];
}

function typeRetourPossible(array $commande): ?string
{
    if (($commande['statut'] ?? '') === 'annulee') {
        $dateAnnulation = $commande['date_annulation'] ?? null;
        return $dateAnnulation && strtotime($dateAnnulation . ' +' . RETOUR_REMBOURSEMENT_DELAI_JOURS . ' days') >= time() && (float) $commande['total'] > 0 ? 'remboursement' : null;
    }
    if (!commandeLivree($commande)) {
        return null;
    }
    return strtotime(dateLivraisonCommande($commande) . ' +' . RETOUR_DELAI_JOURS . ' days') >= time() ? 'retour' : null;
}

function dateLimiteRetour(array $commande): string
{
    return ($commande['statut'] ?? '') === 'annulee'
        ? date('Y-m-d H:i:s', strtotime(($commande['date_annulation'] ?? $commande['date_commande']) . ' +' . RETOUR_REMBOURSEMENT_DELAI_JOURS . ' days'))
        : date('Y-m-d H:i:s', strtotime(dateLivraisonCommande($commande) . ' +' . RETOUR_DELAI_JOURS . ' days'));
}

function retoursParCommande(PDO $pdo, array $idsCommandes): array
{
    if (!$idsCommandes || !retoursDisponibles($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT id_retour, id_commande, reference, type, statut FROM retour_commande WHERE id_commande IN (" . implode(',', array_fill(0, count($idsCommandes), '?')) . ") ORDER BY date_demande DESC, id_retour DESC");
    $stmt->execute(array_values(array_map('intval', $idsCommandes)));
    $parCommande = [];
    foreach ($stmt->fetchAll() as $retour) {
        $parCommande[(int) $retour['id_commande']][] = $retour;
    }
    return $parCommande;
}

function retourActifCommande(array $retours): ?array
{
    foreach ($retours as $retour) {
        if (in_array($retour['statut'], RETOUR_STATUTS_ACTIFS, true) || $retour['statut'] === 'rembourse') {
            return $retour;
        }
    }
    return null;
}

function commandeDuMembre(PDO $pdo, int $idCommande, int $idUser, bool $verrouiller = false): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM commande WHERE id_commande = ? AND id_user = ?" . ($verrouiller ? ' FOR UPDATE' : ''));
    $stmt->execute([$idCommande, $idUser]);
    return $stmt->fetch() ?: null;
}

function lignesRetournables(PDO $pdo, int $idCommande): array
{
    $stmt = $pdo->prepare("SELECT cl.id_ligne, cl.id_livre, cl.id_exemplaire, cl.titre, cl.etat, cl.prix_unitaire, cl.quantite, l.couverture
        FROM commande_ligne cl LEFT JOIN livre l ON l.id_livre = cl.id_livre WHERE cl.id_commande = ? ORDER BY cl.id_ligne");
    $stmt->execute([$idCommande]);
    return $stmt->fetchAll();
}

function montantRemboursable(array $commande, array $lignesChoisies): float
{
    if ($commande['statut'] === 'annulee') {
        return round((float) $commande['total'], 2);
    }
    $sousTotal = (float) $commande['sous_total'];
    $livres = 0.0;
    foreach ($lignesChoisies as $ligne) {
        $livres += (float) $ligne['prix_unitaire'] * (int) $ligne['quantite_retour'];
    }
    if ($sousTotal <= 0) {
        return 0.0;
    }
    $payeLivres = max(0.0, (float) $commande['total'] - (float) $commande['frais_livraison']);
    return round(min($payeLivres, $livres * $payeLivres / $sousTotal), 2);
}

function creerDemandeRetour(PDO $pdo, int $idUser, int $idCommande, string $motif, string $message, array $quantites): array
{
    $message = trim($message);
    if (mb_strlen($message) > RETOUR_MESSAGE_MAX) {
        return ['danger', 'Votre message dépasse ' . RETOUR_MESSAGE_MAX . ' caractères.'];
    }
    $pdo->beginTransaction();
    try {
        $commande = commandeDuMembre($pdo, $idCommande, $idUser, true);
        if (!$commande) {
            throw new DomainException('Cette commande ne vous appartient pas.');
        }
        $type = typeRetourPossible($commande);
        if ($type === null) {
            throw new DomainException('Le délai de demande est dépassé ou la commande n’est pas encore livrée.');
        }
        $motifs = $type === 'remboursement' ? RETOUR_MOTIFS_REMBOURSEMENT : RETOUR_MOTIFS;
        if (!isset($motifs[$motif])) {
            throw new DomainException('Choisissez un motif.');
        }
        if ($motif === 'autre' && mb_strlen($message) < 10) {
            throw new DomainException('Précisez la raison en quelques mots (10 caractères minimum).');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM retour_commande WHERE id_commande = ? AND statut IN ('demande', 'accepte', 'recu', 'rembourse') FOR UPDATE");
        $stmt->execute([$idCommande]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new DomainException('Une demande est déjà en cours ou traitée pour cette commande.');
        }
        $choisies = [];
        if ($type === 'retour') {
            foreach (lignesRetournables($pdo, $idCommande) as $ligne) {
                $quantite = (int) ($quantites[$ligne['id_ligne']] ?? 0);
                if ($quantite < 0 || $quantite > (int) $ligne['quantite']) {
                    throw new DomainException('Quantité de retour invalide.');
                }
                if ($quantite > 0) {
                    $ligne['quantite_retour'] = $quantite;
                    $choisies[] = $ligne;
                }
            }
            if (!$choisies) {
                throw new DomainException('Cochez au moins un livre à retourner.');
            }
        }
        $montant = montantRemboursable($commande, $choisies);
        $pdo->prepare("INSERT INTO retour_commande (id_commande, id_user, type, motif, message, montant) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$idCommande, $idUser, $type, $motif, $message !== '' ? $message : null, $montant]);
        $idRetour = (int) $pdo->lastInsertId();
        $reference = 'RT-' . date('Y') . '-' . str_pad((string) $idRetour, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE retour_commande SET reference = ? WHERE id_retour = ?")->execute([$reference, $idRetour]);
        $insererLigne = $pdo->prepare("INSERT INTO retour_ligne (id_retour, id_ligne, quantite) VALUES (?, ?, ?)");
        foreach ($choisies as $ligne) {
            $insererLigne->execute([$idRetour, $ligne['id_ligne'], $ligne['quantite_retour']]);
        }
        $pdo->prepare("INSERT INTO retour_historique (id_retour, statut, id_acteur, commentaire) VALUES (?, 'demande', ?, NULL)")->execute([$idRetour, $idUser]);
        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        insertLog('ERREUR', 'Demande de retour : ' . $e->getMessage());
        return ['danger', 'La demande n’a pas pu être enregistrée. Merci de réessayer.'];
    }
    insertLog('RETOUR', "Demande $reference ($type) pour la commande #$idCommande");
    notifierMembre($pdo, $idUser, 'retour', "Votre demande $reference est enregistrée : l’équipe vous répond rapidement.", 'retours.php?id=' . $idRetour);
    $stmt = $pdo->prepare("SELECT email, pseudo FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    if ($membre = $stmt->fetch()) {
        notifierParMail($membre['email'], "Demande $reference enregistrée",
            "Bonjour {$membre['pseudo']},\n\nNous avons bien reçu votre demande de " . ($type === 'retour' ? 'retour' : 'remboursement') . " pour la commande n° $idCommande (" . formaterPrix($montant) . ").\n\nVous suivrez chaque étape dans votre espace. Ce site est une démonstration : aucun colis ni paiement réel.",
            SITE_URL . '/retours.php?id=' . $idRetour, 'Suivre ma demande');
    }
    return ['success', "Demande $reference envoyée. Vous suivrez son avancement ici.", $idRetour];
}

function retourComplet(PDO $pdo, int $idRetour, ?int $idUser = null): ?array
{
    $stmt = $pdo->prepare("SELECT r.*, c.date_commande, c.total AS total_commande, c.statut AS statut_commande, c.date_annulation, c.sous_total, c.frais_livraison,
            u.pseudo, u.email, t.pseudo AS pseudo_traitant
        FROM retour_commande r
        INNER JOIN commande c ON c.id_commande = r.id_commande
        INNER JOIN user u ON u.id = r.id_user
        LEFT JOIN user t ON t.id = r.id_traitant
        WHERE r.id_retour = ?" . ($idUser !== null ? ' AND r.id_user = ?' : ''));
    $stmt->execute($idUser !== null ? [$idRetour, $idUser] : [$idRetour]);
    $retour = $stmt->fetch();
    if (!$retour) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT rl.quantite AS quantite_retour, cl.*, l.couverture FROM retour_ligne rl
        INNER JOIN commande_ligne cl ON cl.id_ligne = rl.id_ligne LEFT JOIN livre l ON l.id_livre = cl.id_livre
        WHERE rl.id_retour = ? ORDER BY cl.id_ligne");
    $stmt->execute([$idRetour]);
    $retour['lignes'] = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT h.*, a.pseudo, a.role FROM retour_historique h LEFT JOIN user a ON a.id = h.id_acteur WHERE h.id_retour = ? ORDER BY h.date_action, h.id_historique");
    $stmt->execute([$idRetour]);
    $retour['historique'] = $stmt->fetchAll();
    return $retour;
}

function retoursMembre(PDO $pdo, int $idUser): array
{
    if (!retoursDisponibles($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT * FROM retour_commande WHERE id_user = ? ORDER BY date_demande DESC, id_retour DESC");
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function annulerDemandeRetour(PDO $pdo, int $idRetour, int $idUser): array
{
    $stmt = $pdo->prepare("UPDATE retour_commande SET statut = 'annule', date_maj = NOW() WHERE id_retour = ? AND id_user = ? AND statut = 'demande'");
    $stmt->execute([$idRetour, $idUser]);
    if (!$stmt->rowCount()) {
        return ['warning', 'Cette demande ne peut plus être annulée : l’équipe l’a déjà traitée.'];
    }
    $pdo->prepare("INSERT INTO retour_historique (id_retour, statut, id_acteur, commentaire) VALUES (?, 'annule', ?, 'Annulée par le client')")->execute([$idRetour, $idUser]);
    insertLog('RETOUR', "Demande de retour #$idRetour annulée par le client");
    return ['success', 'Votre demande est annulée.'];
}

function changerStatutRetour(PDO $pdo, int $idRetour, int $idActeur, string $nouveauStatut, string $commentaire, string $statutAttendu): array
{
    $commentaire = mb_substr(trim($commentaire), 0, RETOUR_NOTE_MAX);
    $remisEnStock = [];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT r.*, c.points_gagnes, c.id_user AS client FROM retour_commande r INNER JOIN commande c ON c.id_commande = r.id_commande WHERE r.id_retour = ? FOR UPDATE");
        $stmt->execute([$idRetour]);
        $retour = $stmt->fetch();
        if (!$retour) {
            throw new DomainException('Cette demande n’existe pas.');
        }
        if ($retour['statut'] !== $statutAttendu) {
            throw new DomainException('Cette demande a changé depuis votre consultation : vérifiez son nouvel état.');
        }
        if (!in_array($nouveauStatut, RETOUR_TRANSITIONS[$retour['type']][$retour['statut']] ?? [], true)) {
            throw new DomainException('Cette étape n’est pas possible depuis le statut « ' . libelleStatutRetour($retour['statut']) . ' ».');
        }
        if ($nouveauStatut === 'refuse' && mb_strlen($commentaire) < 10) {
            throw new DomainException('Indiquez au client le motif du refus (10 caractères minimum).');
        }
        $pointsRetires = 0;
        if ($nouveauStatut === 'recu') {
            require_once __DIR__ . '/commandes_lib.php';
            $stmt = $pdo->prepare("SELECT rl.quantite AS quantite_retour, cl.* FROM retour_ligne rl INNER JOIN commande_ligne cl ON cl.id_ligne = rl.id_ligne WHERE rl.id_retour = ?");
            $stmt->execute([$idRetour]);
            $remettre = $pdo->prepare("UPDATE exemplaire SET quantite = quantite + ? WHERE id_exemplaire = ?");
            foreach ($stmt->fetchAll() as $ligne) {
                if (!empty($ligne['id_livre']) && $retour['motif'] !== 'abime') {
                    $remettre->execute([(int) $ligne['quantite_retour'], exemplairePourRetour($pdo, $ligne)]);
                    $remisEnStock[(int) $ligne['id_livre']] = ($remisEnStock[(int) $ligne['id_livre']] ?? 0) + (int) $ligne['quantite_retour'];
                }
            }
        }
        if ($nouveauStatut === 'rembourse' && $retour['type'] === 'retour') {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM point_mouvement WHERE id_user = ? AND statut = 'valide' AND type = 'achat' AND reference = ?");
            $stmt->execute([(int) $retour['id_user'], 'commande:' . (int) $retour['id_commande']]);
            $pointsRetires = min((int) floor((float) $retour['montant']), max(0, (int) $stmt->fetchColumn()));
            if ($pointsRetires > 0) {
                crediterPoints($pdo, (int) $retour['id_user'], -$pointsRetires, 'annulation', 'Points retirés : retour ' . $retour['reference'] . ' remboursé', 'retour:' . $idRetour);
            }
        }
        $pdo->prepare("UPDATE retour_commande SET statut = ?, id_traitant = ?, note_equipe = COALESCE(?, note_equipe), points_retires = points_retires + ?, date_maj = NOW() WHERE id_retour = ?")
            ->execute([$nouveauStatut, $idActeur, $commentaire !== '' ? $commentaire : null, $pointsRetires, $idRetour]);
        $pdo->prepare("INSERT INTO retour_historique (id_retour, statut, id_acteur, commentaire) VALUES (?, ?, ?, ?)")
            ->execute([$idRetour, $nouveauStatut, $idActeur, $commentaire !== '' ? $commentaire : null]);
        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        insertLog('ERREUR', 'Retour #' . $idRetour . ' : ' . $e->getMessage());
        return ['danger', 'Le statut n’a pas pu être modifié. Merci de réessayer.'];
    }
    foreach ($remisEnStock as $idLivre => $quantiteRemise) {
        notifierRetourEnStock($pdo, $idLivre, $quantiteRemise);
    }
    insertLog('RETOUR', "Retour {$retour['reference']} : {$retour['statut']} -> $nouveauStatut");
    $messages = [
        'accepte' => $retour['type'] === 'retour' ? 'est acceptée : renvoyez le colis, le remboursement suivra sa réception.' : 'est acceptée : le remboursement est en préparation.',
        'refuse' => 'est refusée. Motif : ' . $commentaire,
        'recu' => 'a été reçue par notre équipe : le remboursement est en cours.',
        'rembourse' => 'est remboursée (' . formaterPrix((float) $retour['montant']) . ', paiement fictif).' . ($pointsRetires > 0 ? " $pointsRetires points de la commande ont été retirés." : ''),
    ];
    $texte = 'Votre demande ' . $retour['reference'] . ' ' . $messages[$nouveauStatut];
    notifierMembre($pdo, (int) $retour['id_user'], 'retour', mb_substr($texte, 0, 255), 'retours.php?id=' . $idRetour);
    $stmt = $pdo->prepare("SELECT email, pseudo FROM user WHERE id = ?");
    $stmt->execute([(int) $retour['id_user']]);
    $envoye = false;
    if ($membre = $stmt->fetch()) {
        $envoye = notifierParMail($membre['email'], 'Demande ' . $retour['reference'] . ' : ' . mb_strtolower(libelleStatutRetour($nouveauStatut)),
            "Bonjour {$membre['pseudo']},\n\n$texte" . ($commentaire !== '' && $nouveauStatut !== 'refuse' ? "\n\nMessage de l’équipe : $commentaire" : ''),
            SITE_URL . '/retours.php?id=' . $idRetour, 'Suivre ma demande');
    }
    return ['success', 'Demande ' . $retour['reference'] . ' : ' . mb_strtolower(libelleStatutRetour($nouveauStatut)) . ($remisEnStock ? ', livres remis en stock' : '') . ($pointsRetires > 0 ? ", $pointsRetires points retirés" : '') . '.' . ($envoye ? ' Le client a été prévenu.' : ' Le mail n’a pas pu être envoyé.')];
}

function nombreRetoursATraiter(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM retour_commande WHERE statut IN ('demande', 'recu') OR (statut = 'accepte' AND type = 'remboursement')")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
