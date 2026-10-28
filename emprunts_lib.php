<?php
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/messagerie_lib.php';
require_once __DIR__ . '/points_lib.php';

const EMPRUNTS_EN_COURS_MAX = 3;
const ATTENTES_EMPRUNT_MAX = 3;
const RESERVATION_JOURS_OUVRES = 3;
const DUREE_EMPRUNT_DEFAUT = 21;
const RENOUVELLEMENTS_DEFAUT = 2;
const RENOUVELLEMENT_JOURS = 14;
const RAPPEL_AVANT_JOURS = 3;
const RELANCE_INTERVALLE_JOURS = 7;
const BLOCAGE_MAX_JOURS = 30;
const POINTS_EMPRUNT = 5;
const EMPRUNTS_PAYES_PAR_MOIS = 5;
const ROLES_EMPRUNTEURS = ['client', 'auteur'];
const STATUTS_EMPRUNT = [
    'reserve'  => ['Réservé, à retirer', 'attente', 'bookmark-check'],
    'en_cours' => ['Emprunté', '', 'book'],
    'rendu'    => ['Rendu', 'neutre', 'check2-circle'],
    'expire'   => ['Réservation expirée', 'neutre', 'clock-history'],
    'annule'   => ['Annulé', 'neutre', 'x-circle'],
];
const STATUTS_PRET = [
    'disponible' => 'Disponible',
    'reserve'    => 'Réservé',
    'emprunte'   => 'Emprunté',
];
const CARACTERES_CODE_RETRAIT = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function empruntEnRetard(array $emprunt): bool
{
    return $emprunt['statut'] === 'en_cours' && $emprunt['date_retour_prevue'] && $emprunt['date_retour_prevue'] < date('Y-m-d');
}

function joursDeRetard(array $emprunt): int
{
    if ($emprunt['statut'] === 'rendu') {
        return (int) $emprunt['jours_retard'];
    }
    if (!empruntEnRetard($emprunt)) {
        return 0;
    }
    return (int) (new DateTimeImmutable($emprunt['date_retour_prevue']))->diff(new DateTimeImmutable('today'))->days;
}

function pastilleStatutEmprunt(array $emprunt): string
{
    [$libelle, $classe, $icone] = STATUTS_EMPRUNT[$emprunt['statut']] ?? [$emprunt['statut'], 'neutre', 'question-circle'];
    if ($emprunt['statut'] === 'rendu' && (int) $emprunt['jours_retard'] > 0) {
        $libelle = 'Rendu avec ' . (int) $emprunt['jours_retard'] . ' j de retard';
    }
    $html = '<span class="pastille-etat ' . $classe . '"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> ' . htmlspecialchars($libelle) . '</span>';
    if (empruntEnRetard($emprunt)) {
        $jours = joursDeRetard($emprunt);
        $html .= ' <span class="pastille-etat refuse"><i class="bi bi-alarm" aria-hidden="true"></i> En retard de ' . $jours . ' jour' . ($jours > 1 ? 's' : '') . '</span>';
    }
    return $html;
}

function dateExpirationReservation(?DateTimeImmutable $depuis = null): string
{
    $date = $depuis ?? new DateTimeImmutable();
    $joursOuvres = 0;
    while ($joursOuvres < RESERVATION_JOURS_OUVRES) {
        $date = $date->modify('+1 day');
        if ((int) $date->format('N') <= 5) {
            $joursOuvres++;
        }
    }
    return $date->format('Y-m-d 23:59:59');
}

function genererCodeRetrait(PDO $pdo): string
{
    $verification = $pdo->prepare("SELECT COUNT(*) FROM emprunt WHERE code_retrait = ?");
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= CARACTERES_CODE_RETRAIT[random_int(0, strlen(CARACTERES_CODE_RETRAIT) - 1)];
        }
        $verification->execute([$code]);
    } while ((int) $verification->fetchColumn() > 0);
    return $code;
}

function normaliserCodeRetrait(string $saisie): string
{
    if (preg_match('/[?&]code=([A-Za-z0-9]{6})/', $saisie, $trouve)) {
        $saisie = $trouve[1];
    }
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $saisie));
}

function conditionExemplaireEmprunt(string $alias = 'e'): string
{
    return "$alias.id_proprietaire IS NULL AND $alias.type_offre = 'emprunt'";
}

function verrouillerLivreEmprunt(PDO $pdo, int $idLivre): void
{
    $pdo->prepare("SELECT id_livre FROM livre WHERE id_livre = ? FOR UPDATE")->execute([$idLivre]);
}

function verrouillerLecteurEmprunt(PDO $pdo, int $idUser): void
{
    $pdo->prepare("SELECT id FROM `user` WHERE id = ? FOR UPDATE")->execute([$idUser]);
}

function blocageActif(PDO $pdo, int $idUser): ?array
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM blocage_emprunt WHERE id_user = ? AND date_levee IS NULL AND date_fin > NOW() ORDER BY date_fin DESC LIMIT 1" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
        $stmt->execute([$idUser]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            throw $e;
        }
        return null;
    }
}

function nombreEmpruntsActifs(PDO $pdo, int $idUser): int
{
    $stmt = $pdo->prepare("SELECT id_emprunt FROM emprunt WHERE id_user = ? AND statut IN ('reserve', 'en_cours')" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $stmt->execute([$idUser]);
    return count($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function nombreEmpruntsEnRetard(PDO $pdo, int $idUser): int
{
    $stmt = $pdo->prepare("SELECT id_emprunt FROM emprunt WHERE id_user = ? AND statut = 'en_cours' AND date_retour_prevue < CURDATE()" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $stmt->execute([$idUser]);
    return count($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function raisonInterdictionEmprunt(PDO $pdo, int $idUser, bool $compterLimite = true): string
{
    $stmt = $pdo->prepare("SELECT role, statut, email_verifie FROM user WHERE id = ?" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $stmt->execute([$idUser]);
    $membre = $stmt->fetch();
    if (!$membre) {
        return "Compte introuvable.";
    }
    if (!in_array($membre['role'], ROLES_EMPRUNTEURS, true)) {
        return "L'emprunt est réservé aux comptes lecteurs et auteurs.";
    }
    if (in_array($membre['statut'], STATUTS_COMPTE_SUSPENDU, true)) {
        return "Votre compte est suspendu : vous ne pouvez pas emprunter.";
    }
    if (!(int) $membre['email_verifie']) {
        return "Confirmez d'abord votre adresse email pour pouvoir emprunter.";
    }
    if (nombreEmpruntsEnRetard($pdo, $idUser) > 0) {
        return "Vous avez un livre en retard : rapportez-le avant de réserver un nouvel emprunt.";
    }
    if ($blocage = blocageActif($pdo, $idUser)) {
        return "Vos emprunts sont bloqués jusqu'au " . date('d/m/Y à H:i', strtotime($blocage['date_fin'])) . " à la suite d'un retour en retard.";
    }
    if ($compterLimite && nombreEmpruntsActifs($pdo, $idUser) >= EMPRUNTS_EN_COURS_MAX) {
        return "Vous avez déjà " . EMPRUNTS_EN_COURS_MAX . " emprunts ou réservations en cours : rendez un livre avant d'en réserver un autre.";
    }
    return '';
}

function requeteEmprunt(): string
{
    return "
        SELECT em.*, em.blocage_retard AS blocage_retard, e.id_livre, e.etat, e.statut_pret, e.is_disponible AS exemplaire_disponible,
               l.titre, l.auteur, l.couverture, u.pseudo, u.email, u.photo, u.statut AS membre_statut,
               va.pseudo AS valide_par_pseudo, rp.pseudo AS retour_par_pseudo
        FROM emprunt em
        INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire
        INNER JOIN livre l ON l.id_livre = e.id_livre
        INNER JOIN `user` u ON u.id = em.id_user
        LEFT JOIN `user` va ON va.id = em.id_valide_par
        LEFT JOIN `user` rp ON rp.id = em.id_retour_par";
}

function empruntParId(PDO $pdo, int $idEmprunt, bool $verrouiller = false): ?array
{
    if ($verrouiller) {
        $identifiants = $pdo->prepare("SELECT e.id_livre, em.id_user FROM emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire WHERE em.id_emprunt = ?");
        $identifiants->execute([$idEmprunt]);
        if (!$objet = $identifiants->fetch()) {
            return null;
        }
        verrouillerLivreEmprunt($pdo, (int) $objet['id_livre']);
        verrouillerLecteurEmprunt($pdo, (int) $objet['id_user']);
    }
    $stmt = $pdo->prepare(requeteEmprunt() . " WHERE em.id_emprunt = ?" . ($verrouiller ? ' FOR UPDATE' : ''));
    $stmt->execute([$idEmprunt]);
    return $stmt->fetch() ?: null;
}

function empruntParCode(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare(requeteEmprunt() . " WHERE em.code_retrait = ?");
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

function empruntActifLivre(PDO $pdo, int $idLivre, int $idUser): ?array
{
    $stmt = $pdo->prepare(requeteEmprunt() . " WHERE e.id_livre = ? AND em.id_user = ? AND em.statut IN ('reserve', 'en_cours') LIMIT 1" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $stmt->execute([$idLivre, $idUser]);
    return $stmt->fetch() ?: null;
}

function empruntsMembre(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare(requeteEmprunt() . " WHERE em.id_user = ? ORDER BY FIELD(em.statut, 'reserve', 'en_cours') DESC, em.date_retour_prevue, em.date_reservation DESC");
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function etatEmpruntLivre(PDO $pdo, int $idLivre): array
{
    $etat = ['total' => 0, 'libres' => 0, 'prochain_retour' => null, 'attente' => 0, 'exemplaires' => []];
    try {
        $stmt = $pdo->prepare("
            SELECT e.id_exemplaire, e.etat, e.statut_pret, e.duree_emprunt_jours, e.renouvellements_max, e.blocage_retard,
                   (SELECT MIN(em.date_retour_prevue) FROM emprunt em WHERE em.id_exemplaire = e.id_exemplaire AND em.statut = 'en_cours' AND em.blocage_retard IN (0, 1)) AS retour_prevu
            FROM exemplaire e
            WHERE e.id_livre = ? AND " . conditionExemplaireEmprunt() . " AND e.is_disponible = 1
            ORDER BY FIELD(e.statut_pret, 'disponible', 'reserve', 'emprunte'), e.id_exemplaire");
        $stmt->execute([$idLivre]);
        $etat['exemplaires'] = $stmt->fetchAll();
        $etat['total'] = count($etat['exemplaires']);
        foreach ($etat['exemplaires'] as $exemplaire) {
            if ($exemplaire['statut_pret'] === 'disponible') {
                $etat['libres']++;
            }
            if ($exemplaire['retour_prevu'] && (!$etat['prochain_retour'] || $exemplaire['retour_prevu'] < $etat['prochain_retour'])) {
                $etat['prochain_retour'] = $exemplaire['retour_prevu'];
            }
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunt_attente WHERE id_livre = ?");
        $stmt->execute([$idLivre]);
        $etat['attente'] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
    }
    return $etat;
}

function positionAttenteEmprunt(PDO $pdo, int $idLivre, int $idUser): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM emprunt_attente a
        INNER JOIN emprunt_attente courant ON courant.id_livre = a.id_livre AND courant.id_user = ?
        WHERE a.id_livre = ? AND (a.date_demande < courant.date_demande OR (a.date_demande = courant.date_demande AND a.id_attente <= courant.id_attente))");
    $stmt->execute([$idUser, $idLivre]);
    return (int) $stmt->fetchColumn();
}

function attentesMembre(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare("
        SELECT a.*, l.titre, l.auteur, l.couverture,
               (SELECT COUNT(*) FROM emprunt_attente a2 WHERE a2.id_livre = a.id_livre AND (a2.date_demande < a.date_demande OR (a2.date_demande = a.date_demande AND a2.id_attente <= a.id_attente))) AS position,
               (SELECT MIN(em.date_retour_prevue) FROM emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire
                WHERE e.id_livre = a.id_livre AND em.statut = 'en_cours') AS prochain_retour
        FROM emprunt_attente a INNER JOIN livre l ON l.id_livre = a.id_livre
        WHERE a.id_user = ? ORDER BY a.date_demande, a.id_attente");
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function personneEnAttente(PDO $pdo, int $idLivre, int $idUserExclu): bool
{
    $stmt = $pdo->prepare("SELECT id_attente FROM emprunt_attente WHERE id_livre = ? AND id_user <> ?" . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $stmt->execute([$idLivre, $idUserExclu]);
    return $stmt->fetchColumn() !== false;
}

function prevenirEmprunteur(array $emprunt, string $sujet, string $texte, string $libelleBouton = 'Voir mes emprunts'): bool
{
    return notifierParMail($emprunt['email'], $sujet, "Bonjour {$emprunt['pseudo']},\n\n$texte", SITE_URL . '/mes_emprunts.php', $libelleBouton);
}

function creerReservation(PDO $pdo, array $exemplaire, int $idUser, bool $depuisAttente): int
{
    $code = genererCodeRetrait($pdo);
    $expiration = dateExpirationReservation();
    $pdo->prepare("
        INSERT INTO emprunt (id_exemplaire, id_user, code_retrait, duree_jours, renouvellements_max, blocage_retard, date_expiration_reservation)
        VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$exemplaire['id_exemplaire'], $idUser, $code, $exemplaire['duree_emprunt_jours'], $exemplaire['renouvellements_max'], $exemplaire['blocage_retard'], $expiration]);
    $idEmprunt = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE exemplaire SET statut_pret = 'reserve' WHERE id_exemplaire = ?")->execute([$exemplaire['id_exemplaire']]);
    $pdo->prepare("DELETE FROM emprunt_attente WHERE id_livre = ? AND id_user = ?")->execute([$exemplaire['id_livre'], $idUser]);

    $dateLimite = date('d/m/Y', strtotime($expiration));
    ouvrirConversation($pdo, 'emprunt', $idEmprunt, [$idUser => 'emprunteur'],
        ($depuisAttente ? "Un exemplaire s'est libéré : il vous est réservé. " : "Réservation enregistrée. ")
        . "Retirez « {$exemplaire['titre']} » en magasin avant le $dateLimite avec le code $code. L'équipe vous répond ici si vous avez une question.");
    return $idEmprunt;
}

function reserverEmprunt(PDO $pdo, int $idLivre, int $idUser): array
{
    $pdo->beginTransaction();
    try {
        verrouillerLivreEmprunt($pdo, $idLivre);
        verrouillerLecteurEmprunt($pdo, $idUser);
        $raison = raisonInterdictionEmprunt($pdo, $idUser);
        if ($raison !== '') {
            $pdo->rollBack();
            return ['warning', $raison, null];
        }
        if (empruntActifLivre($pdo, $idLivre, $idUser)) {
            $pdo->rollBack();
            return ['info', "Vous avez déjà réservé ou emprunté ce livre.", null];
        }
        $stmt = $pdo->prepare("
            SELECT e.*, l.titre FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre
            WHERE e.id_livre = ? AND " . conditionExemplaireEmprunt() . " AND e.is_disponible = 1 AND e.statut_pret = 'disponible'
            ORDER BY e.id_exemplaire LIMIT 1 FOR UPDATE");
        $stmt->execute([$idLivre]);
        $exemplaire = $stmt->fetch();
        if (!$exemplaire) {
            $pdo->rollBack();
            return ['info', "Tous les exemplaires de ce livre sont déjà empruntés ou réservés : inscrivez-vous sur la liste d'attente.", null];
        }
        $attribution = attribuerExemplaireLibere($pdo, (int) $exemplaire['id_exemplaire']);
        if ($attribution) {
            $attribue = empruntParId($pdo, $attribution);
            $pdo->commit();
            prevenirAttribution($pdo, $attribution);
            return (int) $attribue['id_user'] === $idUser
                ? ['success', "Un exemplaire vous a été attribué depuis la liste d'attente.", $attribution]
                : ['info', "Cet exemplaire a été réservé pour un lecteur prioritaire de la liste d'attente. Vous pouvez réessayer ou rejoindre la liste.", null];
        }
        $idEmprunt = creerReservation($pdo, $exemplaire, $idUser, false);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Réservation d'emprunt du livre #$idLivre impossible : " . $e->getMessage());
        return ['danger', "La réservation n'a pas pu être enregistrée. Merci de réessayer.", null];
    }

    $emprunt = empruntParId($pdo, $idEmprunt);
    insertLog('EMPRUNT', "Réservation #$idEmprunt de « {$emprunt['titre']} » par @{$emprunt['pseudo']}");
    $dateLimite = date('d/m/Y', strtotime($emprunt['date_expiration_reservation']));
    prevenirEmprunteur($emprunt, "Votre réservation : « {$emprunt['titre']} »",
        "Votre réservation de « {$emprunt['titre']} » est enregistrée.\n\nVotre code de retrait : {$emprunt['code_retrait']}\n\n"
        . "Présentez ce code (ou le QR code de la page Mes emprunts) en magasin avant le $dateLimite. Passé ce délai, la réservation sera annulée automatiquement.\n\n"
        . "Durée de l'emprunt : {$emprunt['duree_jours']} jours à partir du retrait.");
    return ['success', "Livre réservé ! Retirez-le en magasin avant le $dateLimite avec le code {$emprunt['code_retrait']}.", $idEmprunt];
}

function attribuerExemplaireLibere(PDO $pdo, int $idExemplaire): ?int
{
    if (!$pdo->inTransaction()) {
        throw new LogicException("L'attribution nécessite une transaction.");
    }
    $stmt = $pdo->prepare("SELECT e.*, l.titre FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre WHERE e.id_exemplaire = ? FOR UPDATE");
    $stmt->execute([$idExemplaire]);
    $exemplaire = $stmt->fetch();
    if (!$exemplaire || $exemplaire['id_proprietaire'] !== null || $exemplaire['type_offre'] !== 'emprunt' || !(int) $exemplaire['is_disponible'] || $exemplaire['statut_pret'] !== 'disponible') {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id_user FROM emprunt_attente WHERE id_livre = ? ORDER BY date_demande, id_attente FOR UPDATE");
    $stmt->execute([$exemplaire['id_livre']]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idCandidat) {
        $idCandidat = (int) $idCandidat;
        verrouillerLecteurEmprunt($pdo, $idCandidat);
        if (raisonInterdictionEmprunt($pdo, $idCandidat) !== '' || empruntActifLivre($pdo, (int) $exemplaire['id_livre'], $idCandidat)) {
            continue;
        }
        return creerReservation($pdo, $exemplaire, $idCandidat, true);
    }
    return null;
}

function libererExemplaire(PDO $pdo, int $idExemplaire): ?int
{
    $pdo->prepare("UPDATE exemplaire SET statut_pret = 'disponible' WHERE id_exemplaire = ?")->execute([$idExemplaire]);
    return attribuerExemplaireLibere($pdo, $idExemplaire);
}

function prevenirAttribution(PDO $pdo, ?int $idEmprunt): void
{
    if (!$idEmprunt || !($emprunt = empruntParId($pdo, $idEmprunt))) {
        return;
    }
    insertLog('EMPRUNT', "Réservation #$idEmprunt attribuée depuis la liste d'attente à @{$emprunt['pseudo']}");
    prevenirEmprunteur($emprunt, "Bonne nouvelle : « {$emprunt['titre']} » vous attend",
        "Un exemplaire de « {$emprunt['titre']} » s'est libéré : il vous est réservé.\n\nVotre code de retrait : {$emprunt['code_retrait']}\n\n"
        . "Retirez-le en magasin avant le " . date('d/m/Y', strtotime($emprunt['date_expiration_reservation'])) . ", sinon il passera au lecteur suivant.");
}

function annulerReservation(PDO $pdo, int $idEmprunt, ?int $idEmprunteur, ?int $idEquipe = null, string $motif = ''): array
{
    $motif = trim($motif);
    if ($idEquipe && $motif === '') {
        return ['warning', "Indiquez le motif de l'annulation : il est envoyé au lecteur.", null];
    }
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt || (!$idEquipe && (!$idEmprunteur || (int) $emprunt['id_user'] !== $idEmprunteur))) {
            $pdo->rollBack();
            return ['danger', "Réservation introuvable.", null];
        }
        if ($emprunt['statut'] !== 'reserve') {
            $pdo->rollBack();
            return ['info', "Cette réservation n'est plus en cours.", null];
        }
        $pdo->prepare("UPDATE emprunt SET statut = 'annule', date_fin = NOW(), motif_annulation = ? WHERE id_emprunt = ?")
            ->execute([$idEquipe ? mb_substr($motif, 0, 500) : 'Annulée par le lecteur', $idEmprunt]);
        $attribution = libererExemplaire($pdo, (int) $emprunt['id_exemplaire']);
        fermerConversationsObjet($pdo, 'emprunt', $idEmprunt, $idEquipe ? "L'équipe a annulé la réservation : $motif" : "@{$emprunt['pseudo']} a annulé sa réservation.");
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Annulation de la réservation #$idEmprunt impossible : " . $e->getMessage());
        return ['danger', "L'annulation n'a pas pu être enregistrée.", null];
    }
    insertLog('EMPRUNT', "Réservation #$idEmprunt annulée" . ($idEquipe ? " par l'équipe : $motif" : ' par le lecteur'));
    if ($idEquipe) {
        prevenirEmprunteur($emprunt, "Réservation annulée : « {$emprunt['titre']} »",
            "L'équipe BIBLIOccaz a annulé votre réservation de « {$emprunt['titre']} ».\n\nMotif : $motif");
    }
    prevenirAttribution($pdo, $attribution);
    return ['success', $idEquipe ? "Réservation annulée, le lecteur est prévenu par mail." : "Réservation annulée.", $idEmprunt];
}

function validerRetrait(PDO $pdo, int $idEmprunt, int $idEquipe): array
{
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt) {
            $pdo->rollBack();
            return ['danger', "Emprunt introuvable.", null];
        }
        if ($emprunt['statut'] === 'en_cours') {
            $pdo->rollBack();
            return ['info', "Ce livre a déjà été remis à @{$emprunt['pseudo']} le " . date('d/m/Y', strtotime($emprunt['date_retrait'])) . ".", $idEmprunt];
        }
        if ($emprunt['statut'] !== 'reserve') {
            $pdo->rollBack();
            return ['warning', "Cette réservation n'est plus valable (" . mb_strtolower(STATUTS_EMPRUNT[$emprunt['statut']][0]) . ").", $idEmprunt];
        }
        if ($emprunt['date_expiration_reservation'] < date('Y-m-d H:i:s')) {
            $pdo->rollBack();
            return ['warning', "Cette réservation a expiré le " . date('d/m/Y', strtotime($emprunt['date_expiration_reservation'])) . ".", $idEmprunt];
        }
        $raison = raisonInterdictionEmprunt($pdo, (int) $emprunt['id_user'], false);
        if ($raison !== '' || !$emprunt['exemplaire_disponible'] || $emprunt['statut_pret'] !== 'reserve') {
            $pdo->rollBack();
            return ['warning', $raison !== '' ? $raison : "L'exemplaire n'est plus disponible pour ce retrait.", $idEmprunt];
        }
        $dateRetour = (new DateTimeImmutable('today'))->modify('+' . (int) $emprunt['duree_jours'] . ' days')->format('Y-m-d');
        $pdo->prepare("UPDATE emprunt SET statut = 'en_cours', date_retrait = NOW(), date_retour_prevue = ?, id_valide_par = ? WHERE id_emprunt = ?")
            ->execute([$dateRetour, $idEquipe, $idEmprunt]);
        $pdo->prepare("UPDATE exemplaire SET statut_pret = 'emprunte' WHERE id_exemplaire = ?")->execute([$emprunt['id_exemplaire']]);
        if ($idConversation = conversationOuverte($pdo, 'emprunt', $idEmprunt)) {
            ajouterMessageSysteme($pdo, $idConversation, "Livre remis en magasin. À rendre au plus tard le " . date('d/m/Y', strtotime($dateRetour)) . ".");
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Retrait de l'emprunt #$idEmprunt impossible : " . $e->getMessage());
        return ['danger', "Le retrait n'a pas pu être enregistré.", null];
    }
    insertLog('EMPRUNT', "Emprunt #$idEmprunt remis à @{$emprunt['pseudo']}, retour le $dateRetour");
    prevenirEmprunteur($emprunt, "Bonne lecture : « {$emprunt['titre']} »",
        "Vous avez retiré « {$emprunt['titre']} » au magasin.\n\nÀ rendre au plus tard le " . date('d/m/Y', strtotime($dateRetour)) . ".\n\n"
        . "Vous pourrez renouveler l'emprunt de " . RENOUVELLEMENT_JOURS . " jours depuis la page Mes emprunts (jusqu'à {$emprunt['renouvellements_max']} fois), si personne n'attend ce livre.\n\n"
        . "Votre ticket d'emprunt est téléchargeable sur la page Mes emprunts.");
    return ['success', "Livre remis à @{$emprunt['pseudo']} : retour prévu le " . date('d/m/Y', strtotime($dateRetour)) . ".", $idEmprunt];
}

function raisonRefusRenouvellement(PDO $pdo, array $emprunt): string
{
    if ($emprunt['statut'] !== 'en_cours') {
        return "Seul un emprunt en cours peut être renouvelé.";
    }
    if (empruntEnRetard($emprunt)) {
        return "Cet emprunt est en retard : il ne peut plus être renouvelé, rapportez-le au magasin.";
    }
    $raison = raisonInterdictionEmprunt($pdo, (int) $emprunt['id_user'], false);
    if ($raison !== '') {
        return $raison;
    }
    if ((int) $emprunt['nb_renouvellements'] >= (int) $emprunt['renouvellements_max']) {
        return (int) $emprunt['renouvellements_max'] === 0
            ? "Cet exemplaire ne peut pas être renouvelé."
            : "Vous avez déjà renouvelé cet emprunt " . (int) $emprunt['renouvellements_max'] . " fois.";
    }
    if (personneEnAttente($pdo, (int) $emprunt['id_livre'], (int) $emprunt['id_user'])) {
        return "Un autre lecteur attend ce livre : l'emprunt ne peut pas être renouvelé.";
    }
    return '';
}

function renouvelerEmprunt(PDO $pdo, int $idEmprunt, int $idUser): array
{
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt || (int) $emprunt['id_user'] !== $idUser) {
            $pdo->rollBack();
            return ['danger', "Emprunt introuvable."];
        }
        $raison = raisonRefusRenouvellement($pdo, $emprunt);
        if ($raison !== '') {
            $pdo->rollBack();
            return ['warning', $raison];
        }
        $nouvelleDate = (new DateTimeImmutable($emprunt['date_retour_prevue']))->modify('+' . RENOUVELLEMENT_JOURS . ' days')->format('Y-m-d');
        $pdo->prepare("UPDATE emprunt SET date_retour_prevue = ?, nb_renouvellements = nb_renouvellements + 1, rappel_avant = 0, rappel_jour = 0 WHERE id_emprunt = ?")
            ->execute([$nouvelleDate, $idEmprunt]);
        if ($idConversation = conversationOuverte($pdo, 'emprunt', $idEmprunt)) {
            ajouterMessageSysteme($pdo, $idConversation, "Emprunt renouvelé : nouvelle date de retour le " . date('d/m/Y', strtotime($nouvelleDate)) . ".");
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Renouvellement de l'emprunt #$idEmprunt impossible : " . $e->getMessage());
        return ['danger', "Le renouvellement n'a pas pu être enregistré."];
    }
    $restants = (int) $emprunt['renouvellements_max'] - (int) $emprunt['nb_renouvellements'] - 1;
    insertLog('EMPRUNT', "Emprunt #$idEmprunt renouvelé jusqu'au $nouvelleDate");
    prevenirEmprunteur($emprunt, "Emprunt renouvelé : « {$emprunt['titre']} »",
        "Votre emprunt de « {$emprunt['titre']} » est prolongé de " . RENOUVELLEMENT_JOURS . " jours.\n\nNouvelle date de retour : " . date('d/m/Y', strtotime($nouvelleDate)) . ".\n\n"
        . ($restants > 0 ? "Renouvellements encore possibles : $restants." : "C'était le dernier renouvellement possible pour cet emprunt."));
    return ['success', "Emprunt renouvelé : à rendre le " . date('d/m/Y', strtotime($nouvelleDate)) . "."];
}

function enregistrerRetour(PDO $pdo, int $idEmprunt, int $idEquipe): array
{
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt) {
            $pdo->rollBack();
            return ['danger', "Emprunt introuvable."];
        }
        if ($emprunt['statut'] !== 'en_cours') {
            $pdo->rollBack();
            return ['info', "Cet emprunt n'est pas en cours."];
        }
        $joursRetard = joursDeRetard($emprunt);
        $pdo->prepare("UPDATE emprunt SET statut = 'rendu', date_retour = NOW(), date_fin = NOW(), jours_retard = ?, id_retour_par = ? WHERE id_emprunt = ?")
            ->execute([$joursRetard, $idEquipe, $idEmprunt]);
        $idUser = (int) $emprunt['id_user'];

        $joursBlocage = 0;
        if ($joursRetard > 0 && (int) $emprunt['blocage_retard']) {
            $joursBlocage = min($joursRetard, BLOCAGE_MAX_JOURS);
            $pdo->prepare("INSERT INTO blocage_emprunt (id_user, id_emprunt, jours, date_fin, motif) VALUES (?, ?, ?, NOW() + INTERVAL $joursBlocage DAY, ?)")
                ->execute([$idUser, $idEmprunt, $joursBlocage, "Retour de « {$emprunt['titre']} » avec $joursRetard jour" . ($joursRetard > 1 ? 's' : '') . " de retard"]);
        }

        $pointsCredites = false;
        if ($joursRetard === 0) {
            $stmt = $pdo->prepare("SELECT id_mouvement FROM point_mouvement WHERE id_user = ? AND type = 'emprunt' AND statut = 'valide' AND date_mouvement >= DATE_FORMAT(NOW(), '%Y-%m-01') FOR UPDATE");
            $stmt->execute([$idUser]);
            $pointsCredites = count($stmt->fetchAll(PDO::FETCH_COLUMN)) < EMPRUNTS_PAYES_PAR_MOIS
                && crediterPoints($pdo, $idUser, POINTS_EMPRUNT, 'emprunt', "Emprunt rendu à l'heure : « {$emprunt['titre']} »", 'emprunt:' . $idEmprunt);
            verifierBadges($pdo, $idUser);
        }

        fermerConversationsObjet($pdo, 'emprunt', $idEmprunt, "Livre rendu le " . date('d/m/Y') . ($joursRetard > 0 ? " avec $joursRetard jour" . ($joursRetard > 1 ? 's' : '') . " de retard." : ", à l'heure. Merci !"));
        $attribution = libererExemplaire($pdo, (int) $emprunt['id_exemplaire']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Retour de l'emprunt #$idEmprunt impossible : " . $e->getMessage());
        return ['danger', "Le retour n'a pas pu être enregistré."];
    }

    insertLog('EMPRUNT', "Emprunt #$idEmprunt rendu" . ($joursRetard ? " avec $joursRetard j de retard" . ($joursBlocage ? ", blocage de $joursBlocage j" : '') : " à l'heure"));
    if ($joursRetard === 0) {
        $texte = "Merci d'avoir rendu « {$emprunt['titre']} » à l'heure !"
            . ($pointsCredites ? "\n\n" . POINTS_EMPRUNT . " points ont été ajoutés à votre compte fidélité." : '');
    } else {
        $texte = "Nous avons bien reçu « {$emprunt['titre']} », rendu avec $joursRetard jour" . ($joursRetard > 1 ? 's' : '') . " de retard."
            . ($joursBlocage ? "\n\nVos emprunts sont bloqués pendant $joursBlocage jour" . ($joursBlocage > 1 ? 's' : '') . ", jusqu'au " . date('d/m/Y', strtotime("+$joursBlocage days")) . ". Vous pourrez de nouveau réserver après cette date." : '');
    }
    prevenirEmprunteur($emprunt, "Retour enregistré : « {$emprunt['titre']} »", $texte);
    prevenirAttribution($pdo, $attribution);
    return ['success', "Retour enregistré" . ($joursRetard ? " avec $joursRetard jour" . ($joursRetard > 1 ? 's' : '') . " de retard" . ($joursBlocage ? " : emprunts bloqués $joursBlocage jour" . ($joursBlocage > 1 ? 's' : '') : '') : " à l'heure" . ($pointsCredites ? ' : +' . POINTS_EMPRUNT . ' points pour le lecteur' : '')) . "."];
}

function leverBlocage(PDO $pdo, int $idBlocage, int $idEquipe, string $motif): array
{
    $motif = trim($motif);
    if ($motif === '') {
        return ['warning', "Indiquez le motif de la levée du blocage."];
    }
    $stmt = $pdo->prepare("SELECT b.*, u.pseudo, u.email FROM blocage_emprunt b INNER JOIN `user` u ON u.id = b.id_user WHERE b.id_blocage = ?");
    $stmt->execute([$idBlocage]);
    $blocage = $stmt->fetch();
    if (!$blocage || $blocage['date_levee'] || $blocage['date_fin'] <= date('Y-m-d H:i:s')) {
        return ['info', "Ce blocage n'est plus actif."];
    }
    $miseAJour = $pdo->prepare("UPDATE blocage_emprunt SET date_levee = NOW(), id_leve_par = ?, motif_levee = ? WHERE id_blocage = ? AND date_levee IS NULL AND date_fin > NOW()");
    $miseAJour->execute([$idEquipe, mb_substr($motif, 0, 500), $idBlocage]);
    if (!$miseAJour->rowCount()) {
        return ['info', "Ce blocage n'est plus actif."];
    }
    insertLog('EMPRUNT', "Blocage #$idBlocage de @{$blocage['pseudo']} levé : $motif");
    notifierParMail($blocage['email'], "Un blocage de vos emprunts a été levé",
        "Bonjour {$blocage['pseudo']},\n\nL'équipe BIBLIOccaz a levé le blocage suivant : {$blocage['motif']}.\n\nMotif de la levée : $motif\n\nConsultez Mes emprunts pour vérifier si d'autres restrictions restent applicables à votre compte.",
        SITE_URL . '/mes_emprunts.php', 'Voir mes emprunts');
    return ['success', "Blocage levé pour @{$blocage['pseudo']}. Les autres restrictions éventuelles restent applicables."];
}

function rejoindreAttente(PDO $pdo, int $idLivre, int $idUser): array
{
    $pdo->beginTransaction();
    try {
        verrouillerLivreEmprunt($pdo, $idLivre);
        verrouillerLecteurEmprunt($pdo, $idUser);
        $raison = raisonInterdictionEmprunt($pdo, $idUser, false);
        if ($raison !== '') {
            return ['warning', $raison];
        }
        if (empruntActifLivre($pdo, $idLivre, $idUser)) {
            return ['info', "Vous avez déjà réservé ou emprunté ce livre."];
        }
        $etat = etatEmpruntLivre($pdo, $idLivre);
        if ($etat['total'] === 0) {
            return ['warning', "Ce livre n'est pas proposé à l'emprunt."];
        }
        if ($etat['libres'] > 0) {
            return ['info', "Un exemplaire est libre : vous pouvez le réserver directement."];
        }
        $stmt = $pdo->prepare("SELECT id_attente FROM emprunt_attente WHERE id_user = ? FOR UPDATE");
        $stmt->execute([$idUser]);
        if (count($stmt->fetchAll(PDO::FETCH_COLUMN)) >= ATTENTES_EMPRUNT_MAX) {
            return ['warning', "Vous êtes déjà sur " . ATTENTES_EMPRUNT_MAX . " listes d'attente : quittez-en une pour en rejoindre une autre."];
        }
        $stmt = $pdo->prepare("INSERT IGNORE INTO emprunt_attente (id_livre, id_user) VALUES (?, ?)");
        $stmt->execute([$idLivre, $idUser]);
        if (!$stmt->rowCount()) {
            return ['info', "Vous êtes déjà sur la liste d'attente de ce livre."];
        }
        return ['success', "Vous êtes sur la liste d'attente (position " . positionAttenteEmprunt($pdo, $idLivre, $idUser) . ") : un exemplaire vous sera réservé dès qu'il sera rendu, et vous serez prévenu par mail."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }
}

function quitterAttente(PDO $pdo, int $idLivre, int $idUser): array
{
    $pdo->beginTransaction();
    try {
        verrouillerLivreEmprunt($pdo, $idLivre);
        verrouillerLecteurEmprunt($pdo, $idUser);
        $stmt = $pdo->prepare("DELETE FROM emprunt_attente WHERE id_livre = ? AND id_user = ?");
        $stmt->execute([$idLivre, $idUser]);
        return $stmt->rowCount() ? ['success', "Vous avez quitté la liste d'attente."] : ['info', "Vous n'étiez pas sur cette liste d'attente."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }
}

function envoyerRappelEmprunt(PDO $pdo, int $idEmprunt): void
{
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt || $emprunt['statut'] !== 'en_cours') {
            $pdo->rollBack();
            return;
        }
        $dateRetour = $emprunt['date_retour_prevue'];
        $titre = $emprunt['titre'];
        $dateLisible = date('d/m/Y', strtotime($dateRetour));
        if (empruntEnRetard($emprunt)) {
            if ($emprunt['date_derniere_relance'] && strtotime($emprunt['date_derniere_relance']) > strtotime('-' . RELANCE_INTERVALLE_JOURS . ' days')) {
                $pdo->rollBack();
                return;
            }
            $jours = joursDeRetard($emprunt);
            $sujet = "Retard : « $titre » est à rapporter";
            $texte = "Votre emprunt de « $titre » devait être rendu le $dateLisible : il a $jours jour" . ($jours > 1 ? 's' : '') . " de retard.\n\nMerci de le rapporter au magasin au plus vite. " . textePenaliteEmprunt($emprunt);
            $miseAJour = "date_derniere_relance = NOW(), nb_relances = nb_relances + 1";
        } elseif ($dateRetour === date('Y-m-d') && !(int) $emprunt['rappel_jour']) {
            $sujet = "C'est aujourd'hui : « $titre » est à rendre";
            $texte = "Votre emprunt de « $titre » est à rendre aujourd'hui au magasin.\n\nÀ partir de demain, il sera en retard. " . textePenaliteEmprunt($emprunt);
            $miseAJour = "rappel_jour = 1, rappel_avant = 1";
        } elseif ($dateRetour > date('Y-m-d') && $dateRetour <= date('Y-m-d', strtotime('+' . RAPPEL_AVANT_JOURS . ' days')) && !(int) $emprunt['rappel_avant']) {
            $sujet = "Rappel : « $titre » à rendre le $dateLisible";
            $texte = "Votre emprunt de « $titre » est à rendre au magasin le $dateLisible.\n\n"
                . (raisonRefusRenouvellement($pdo, $emprunt) === '' ? "Besoin de plus de temps ? Vous pouvez encore le renouveler depuis la page Mes emprunts." : "Pensez à le rapporter à temps pour pouvoir continuer à emprunter.");
            $miseAJour = "rappel_avant = 1";
        } else {
            $pdo->rollBack();
            return;
        }
        if (!prevenirEmprunteur($emprunt, $sujet, $texte)) {
            $pdo->rollBack();
            return;
        }
        $pdo->prepare("UPDATE emprunt SET $miseAJour WHERE id_emprunt = ?")->execute([$idEmprunt]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Rappel de l'emprunt #$idEmprunt impossible : " . $e->getMessage());
    }
}

function textePenaliteEmprunt(array $emprunt): string
{
    return "En cas de retard, vous ne pouvez plus emprunter tant que le livre n'est pas rendu."
        . ((int) $emprunt['blocage_retard'] ? " Après le retour, vos emprunts sont bloqués autant de jours que de jours de retard (" . BLOCAGE_MAX_JOURS . " au maximum)." : " Aucun blocage supplémentaire n'est appliqué après le retour pour cet emprunt.");
}

function verifierEmprunts(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SELECT id_emprunt FROM emprunt WHERE statut = 'reserve' AND date_expiration_reservation < NOW()");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idEmprunt) {
            expirerReservation($pdo, (int) $idEmprunt);
        }
        $stmt = $pdo->query("SELECT id_emprunt FROM emprunt WHERE statut = 'en_cours' AND (
            (date_retour_prevue > CURDATE() AND date_retour_prevue <= CURDATE() + INTERVAL " . RAPPEL_AVANT_JOURS . " DAY AND rappel_avant = 0)
            OR (date_retour_prevue = CURDATE() AND rappel_jour = 0)
            OR (date_retour_prevue < CURDATE() AND (date_derniere_relance IS NULL OR date_derniere_relance <= NOW() - INTERVAL " . RELANCE_INTERVALLE_JOURS . " DAY)))");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idEmprunt) {
            envoyerRappelEmprunt($pdo, (int) $idEmprunt);
        }
    } catch (PDOException $e) {
        insertLog('ERREUR', "Vérification des emprunts impossible : " . $e->getMessage());
    }
}

function expirerReservation(PDO $pdo, int $idEmprunt): void
{
    $pdo->beginTransaction();
    try {
        $emprunt = empruntParId($pdo, $idEmprunt, true);
        if (!$emprunt || $emprunt['statut'] !== 'reserve' || $emprunt['date_expiration_reservation'] >= date('Y-m-d H:i:s')) {
            $pdo->rollBack();
            return;
        }
        $pdo->prepare("UPDATE emprunt SET statut = 'expire', date_fin = NOW() WHERE id_emprunt = ?")->execute([$idEmprunt]);
        fermerConversationsObjet($pdo, 'emprunt', $idEmprunt, "Réservation expirée : le livre n'a pas été retiré à temps.");
        $attribution = libererExemplaire($pdo, (int) $emprunt['id_exemplaire']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Expiration de la réservation #$idEmprunt impossible : " . $e->getMessage());
        return;
    }
    insertLog('EMPRUNT', "Réservation #$idEmprunt expirée");
    prevenirEmprunteur($emprunt, "Réservation expirée : « {$emprunt['titre']} »",
        "Votre réservation de « {$emprunt['titre']} » a expiré : le livre n'a pas été retiré avant le " . date('d/m/Y', strtotime($emprunt['date_expiration_reservation'])) . ".\n\nVous pouvez le réserver de nouveau s'il est disponible.");
    prevenirAttribution($pdo, $attribution);
}

function verifierEmpruntsSiBesoin(PDO $pdo): void
{
    if (($_SESSION['emprunts_verifies'] ?? 0) > time() - 300) {
        return;
    }
    $_SESSION['emprunts_verifies'] = time();
    verifierEmprunts($pdo);
}

function compteursEmprunts(PDO $pdo): array
{
    $compteurs = ['a_preparer' => 0, 'en_retard' => 0, 'en_cours' => 0, 'blocages' => 0];
    try {
        $compteurs['a_preparer'] = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'reserve'")->fetchColumn();
        $compteurs['en_cours'] = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours'")->fetchColumn();
        $compteurs['en_retard'] = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()")->fetchColumn();
        $compteurs['blocages'] = (int) $pdo->query("SELECT COUNT(*) FROM blocage_emprunt WHERE date_levee IS NULL AND date_fin > NOW()")->fetchColumn();
    } catch (PDOException $e) {
    }
    return $compteurs;
}

function mettreEnEmprunt(PDO $pdo, int $idLivre, string $etat, ?int $idExemplaireVente, int $duree, int $renouvellements, bool $blocage, int $idEquipe): array
{
    if ($duree < 7 || $duree > 90 || $renouvellements < 0 || $renouvellements > 5) {
        return ['warning', "Durée entre 7 et 90 jours, renouvellements entre 0 et 5."];
    }
    $pdo->beginTransaction();
    try {
        $prix = 0;
        verrouillerLivreEmprunt($pdo, $idLivre);
        if ($idExemplaireVente) {
            $stmt = $pdo->prepare("SELECT * FROM exemplaire WHERE id_exemplaire = ? AND id_livre = ? AND " . conditionExemplaireMagasin('exemplaire') . " FOR UPDATE");
            $stmt->execute([$idExemplaireVente, $idLivre]);
            $offre = $stmt->fetch();
            if (!$offre || (int) $offre['quantite'] < 1) {
                $pdo->rollBack();
                return ['warning', "Cette offre n'a plus de stock à mettre en emprunt."];
            }
            $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ?")->execute([$idExemplaireVente]);
            $etat = $offre['etat'];
            $prix = $offre['prix'];
        }
        $pdo->prepare("
            INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite, id_user, statut_pret, duree_emprunt_jours, renouvellements_max, blocage_retard, date_creation)
            VALUES (?, ?, ?, 'emprunt', 1, 1, ?, 'disponible', ?, ?, ?, NOW())")
            ->execute([$idLivre, $etat, $prix, $idEquipe, $duree, $renouvellements, $blocage ? 1 : 0]);
        $idExemplaire = (int) $pdo->lastInsertId();
        $attribution = attribuerExemplaireLibere($pdo, $idExemplaire);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        insertLog('ERREUR', "Mise en emprunt du livre #$idLivre impossible : " . $e->getMessage());
        return ['danger', "L'exemplaire n'a pas pu être mis en emprunt."];
    }
    insertLog('EMPRUNT', "Exemplaire #$idExemplaire du livre #$idLivre mis en emprunt" . ($idExemplaireVente ? " (pris dans l'offre #$idExemplaireVente)" : ''));
    prevenirAttribution($pdo, $attribution);
    return ['success', "Exemplaire mis en emprunt" . ($idExemplaireVente ? " (le stock de vente baisse de 1)" : '') . ($attribution ? " et réservé pour le premier lecteur de la liste d'attente." : '.')];
}

function modifierReglagesEmprunt(PDO $pdo, int $idExemplaire, int $duree, int $renouvellements, bool $blocage): array
{
    if ($duree < 7 || $duree > 90 || $renouvellements < 0 || $renouvellements > 5) {
        return ['warning', "Durée entre 7 et 90 jours, renouvellements entre 0 et 5."];
    }
    $stmt = $pdo->prepare("UPDATE exemplaire SET duree_emprunt_jours = ?, renouvellements_max = ?, blocage_retard = ? WHERE id_exemplaire = ? AND " . conditionExemplaireEmprunt('exemplaire'));
    $stmt->execute([$duree, $renouvellements, $blocage ? 1 : 0, $idExemplaire]);
    return ['success', "Réglages enregistrés : ils s'appliquent aux prochains emprunts de cet exemplaire."];
}

function changerDisponibiliteEmprunt(PDO $pdo, int $idExemplaire, bool $disponible): array
{
    $pdo->beginTransaction();
    try {
        $livre = $pdo->prepare("SELECT id_livre FROM exemplaire WHERE id_exemplaire = ?");
        $livre->execute([$idExemplaire]);
        verrouillerLivreEmprunt($pdo, (int) $livre->fetchColumn());
        $stmt = $pdo->prepare("SELECT * FROM exemplaire WHERE id_exemplaire = ? AND " . conditionExemplaireEmprunt('exemplaire') . " FOR UPDATE");
        $stmt->execute([$idExemplaire]);
        $exemplaire = $stmt->fetch();
        if (!$exemplaire) {
            $pdo->rollBack();
            return ['danger', "Exemplaire introuvable.", false];
        }
        if (!$disponible && $exemplaire['statut_pret'] !== 'disponible') {
            $pdo->rollBack();
            return ['warning', "Cet exemplaire est réservé ou emprunté : attendez son retour pour le retirer de l'emprunt.", false];
        }
        $pdo->prepare("UPDATE exemplaire SET is_disponible = ? WHERE id_exemplaire = ?")->execute([$disponible ? 1 : 0, $idExemplaire]);
        $attribution = $disponible ? attribuerExemplaireLibere($pdo, $idExemplaire) : null;
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['danger', "La modification n'a pas pu être enregistrée.", false];
    }
    prevenirAttribution($pdo, $attribution);
    return ['success', $disponible ? "Exemplaire remis en emprunt." . ($attribution ? " Il est réservé pour le premier lecteur de la liste d'attente." : '') : "Exemplaire retiré de l'emprunt.", true];
}

function supprimerExemplaireEmprunt(PDO $pdo, int $idExemplaire): array
{
    $pdo->beginTransaction();
    try {
        $livre = $pdo->prepare("SELECT id_livre FROM exemplaire WHERE id_exemplaire = ?");
        $livre->execute([$idExemplaire]);
        verrouillerLivreEmprunt($pdo, (int) $livre->fetchColumn());
        $verrou = $pdo->prepare("SELECT id_exemplaire FROM exemplaire WHERE id_exemplaire = ? AND " . conditionExemplaireEmprunt('exemplaire') . " FOR UPDATE");
        $verrou->execute([$idExemplaire]);
        if (!$verrou->fetchColumn()) {
            return ['warning', "Exemplaire d'emprunt introuvable."];
        }
        $stmt = $pdo->prepare("SELECT id_emprunt FROM emprunt WHERE id_exemplaire = ? FOR UPDATE");
        $stmt->execute([$idExemplaire]);
        if ($stmt->fetchColumn() !== false) {
            return ['warning', "Cet exemplaire a déjà été emprunté : il reste dans l'historique. Retirez-le de l'emprunt à la place."];
        }
        $stmt = $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND " . conditionExemplaireEmprunt('exemplaire') . " AND statut_pret = 'disponible' AND NOT EXISTS (SELECT 1 FROM emprunt WHERE emprunt.id_exemplaire = exemplaire.id_exemplaire)");
        $stmt->execute([$idExemplaire]);
        return $stmt->rowCount() ? ['success', "Exemplaire d'emprunt supprimé."] : ['warning', "Cet exemplaire ne peut pas être supprimé."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }
}

function telechargerTicketEmprunt(array $emprunt): void
{
    require_once __DIR__ . '/vendor/autoload.php';
    $echapper = fn($texte) => htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
    $date = fn($valeur, $format = 'd/m/Y') => $valeur ? date($format, strtotime((string) $valeur)) : '—';
    $estReserve = $emprunt['statut'] === 'reserve';
    $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>
        body { font-family: Helvetica, Arial, sans-serif; color: #2b2b2b; font-size: 12px; }
        .entete { border-bottom: 2px solid #274e13; padding-bottom: 10px; margin-bottom: 22px; }
        .marque { color: #274e13; font-size: 20px; font-weight: bold; }
        .numero { float: right; text-align: right; color: #555; }
        h1 { font-size: 17px; margin: 0 0 4px; color: #274e13; }
        .sous-titre { color: #666; margin-bottom: 18px; }
        .code { border: 2px dashed #274e13; border-radius: 8px; padding: 14px; text-align: center; margin-bottom: 20px; }
        .code span { display: block; font-size: 30px; letter-spacing: 8px; font-weight: bold; color: #274e13; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #cfd8cc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #e8f0e4; width: 34%; }
        .retour td { font-size: 15px; font-weight: bold; color: #274e13; }
        .mention { color: #666; font-size: 10px; line-height: 1.5; margin-top: 24px; }
    </style></head><body>
        <div class="entete"><span class="numero">Emprunt n° ' . (int) $emprunt['id_emprunt'] . '<br>Édité le ' . date('d/m/Y') . '</span>
        <span class="marque">BIBLIOccaz</span><br>Livres d\'occasion · bibliothèque de prêt</div>
        <h1>' . ($estReserve ? 'Bon de réservation' : 'Ticket d\'emprunt') . '</h1>
        <p class="sous-titre">' . ($estReserve ? 'À présenter en magasin avant le ' . $date($emprunt['date_expiration_reservation']) . '.' : 'Livre remis le ' . $date($emprunt['date_retrait'], 'd/m/Y à H:i') . '.') . '</p>
        <div class="code">Code de retrait<span>' . $echapper($emprunt['code_retrait']) . '</span></div>
        <table>
            <tr><th>Livre</th><td><strong>' . $echapper($emprunt['titre']) . '</strong>' . ($emprunt['auteur'] ? '<br>' . $echapper($emprunt['auteur']) : '') . '</td></tr>
            <tr><th>État de l\'exemplaire</th><td>' . $echapper(libelleEtat($emprunt['etat'])) . ' (exemplaire n° ' . (int) $emprunt['id_exemplaire'] . ')</td></tr>
            <tr><th>Emprunteur</th><td>@' . $echapper($emprunt['pseudo']) . '</td></tr>
            <tr><th>Réservé le</th><td>' . $date($emprunt['date_reservation'], 'd/m/Y à H:i') . '</td></tr>
            <tr><th>Durée</th><td>' . (int) $emprunt['duree_jours'] . ' jours, renouvelable ' . (int) $emprunt['renouvellements_max'] . ' fois (' . RENOUVELLEMENT_JOURS . ' jours de plus à chaque fois)</td></tr>
            ' . ($estReserve ? '' : '<tr><th>Renouvellements utilisés</th><td>' . (int) $emprunt['nb_renouvellements'] . ' sur ' . (int) $emprunt['renouvellements_max'] . '</td></tr>
            <tr class="retour"><th>À rendre au plus tard le</th><td>' . $date($emprunt['date_retour_prevue']) . '</td></tr>') . '
            ' . ($emprunt['statut'] === 'rendu' ? '<tr><th>Rendu le</th><td>' . $date($emprunt['date_retour'], 'd/m/Y à H:i') . '</td></tr>' : '') . '
        </table>
        <p class="mention">L\'emprunt est gratuit. ' . $echapper(textePenaliteEmprunt($emprunt)) . ' Un emprunt rendu à l\'heure rapporte ' . POINTS_EMPRUNT . ' points fidélité, dans la limite de ' . EMPRUNTS_PAYES_PAR_MOIS . ' emprunts récompensés par mois civil.
        Le renouvellement se fait depuis la page Mes emprunts, si l\'emprunt n\'est pas en retard et si personne n\'attend ce livre.</p>
    </body></html>';

    $options = new Dompdf\Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'portrait');
    $dompdf->render();
    $dompdf->stream('ticket_emprunt_' . (int) $emprunt['id_emprunt'] . '.pdf', ['Attachment' => true]);
}
