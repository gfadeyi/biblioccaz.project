<?php
require_once __DIR__ . '/points_lib.php';
require_once __DIR__ . '/notifications_lib.php';

const EVT_TYPES = [
    'dedicace'  => ['Dédicace', 'pen'],
    'rencontre' => ['Rencontre', 'people'],
    'atelier'   => ['Atelier', 'easel'],
];
const EVT_MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const EVT_MOIS_COURTS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const EVT_JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

function chargerEvenement(PDO $pdo, int $id, bool $verrou = false): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit') AS nb_inscrits,
               (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'attente') AS nb_attente,
               (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit' AND i.present = 1) AS nb_presents
        FROM evenement e WHERE e.id_evenement = ?" . ($verrou ? " FOR UPDATE" : ""));
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function placesRestantes(array $evt): int
{
    return max(0, (int) $evt['places_max'] - (int) $evt['nb_inscrits']);
}

function etatEvenement(array $evt): string
{
    $maintenant = date('Y-m-d H:i:s');
    if ($evt['statut'] === 'annule') return 'annule';
    if ($evt['date_fin'] < $maintenant) return 'termine';
    if ($evt['date_debut'] <= $maintenant) return 'en_cours';
    if (isset($evt['nb_inscrits']) && placesRestantes($evt) === 0) return 'complet';
    return 'ouvert';
}

function badgeEvenement(array $evt): string
{
    [$libelle, $classe, $icone] = match (etatEvenement($evt)) {
        'annule'   => ['Annulé', 'annule', 'x-octagon'],
        'termine'  => ['Terminé', 'termine', 'flag'],
        'en_cours' => ['En cours', 'en-cours', 'broadcast'],
        'complet'  => ['Complet', 'complet', 'hourglass-split'],
        default    => ['Places disponibles', 'ouvert', 'check2-circle'],
    };
    return "<span class=\"pastille-evt $classe\"><i class=\"bi bi-$icone\" aria-hidden=\"true\"></i> $libelle</span>";
}

function badgeTypeEvenement(string $type): string
{
    [$libelle, $icone] = EVT_TYPES[$type] ?? [ucfirst($type), 'calendar-event'];
    return "<span class=\"pastille-evt type\"><i class=\"bi bi-$icone\" aria-hidden=\"true\"></i> $libelle</span>";
}

function dateLongue(string $date): string
{
    $t = strtotime($date);
    return EVT_JOURS[(int) date('w', $t)] . ' ' . date('j', $t) . ' ' . EVT_MOIS[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function horaireEvenement(array $evt): string
{
    $debut = strtotime($evt['date_debut']);
    $fin = strtotime($evt['date_fin']);
    if (date('Y-m-d', $debut) === date('Y-m-d', $fin)) {
        return dateLongue($evt['date_debut']) . ', ' . date('G\hi', $debut) . ' – ' . date('G\hi', $fin);
    }
    return 'du ' . dateLongue($evt['date_debut']) . ' ' . date('G\hi', $debut) . ' au ' . dateLongue($evt['date_fin']) . ' ' . date('G\hi', $fin);
}

function imageEvenement(?string $image, string $racine = ''): string
{
    return urlImage($image, $racine);
}

function urlPresence(array $inscription): string
{
    return SITE_URL . '/admin/presence.php?code=' . $inscription['code'];
}

function genererCodeBillet(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $stmt = $pdo->prepare("SELECT 1 FROM evenement_inscription WHERE code_court = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

function inscriptionDe(PDO $pdo, int $idEvt, int $idUser): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM evenement_inscription WHERE id_evenement = ? AND id_user = ?");
    $stmt->execute([$idEvt, $idUser]);
    return $stmt->fetch() ?: null;
}

function positionAttente(PDO $pdo, array $inscription): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM evenement_inscription
        WHERE id_evenement = ? AND statut = 'attente'
          AND (prioritaire > ? OR (prioritaire = ? AND (date_inscription < ? OR (date_inscription = ? AND id_inscription <= ?))))");
    $prioritaire = (int) ($inscription['prioritaire'] ?? 0);
    $stmt->execute([$inscription['id_evenement'], $prioritaire, $prioritaire, $inscription['date_inscription'], $inscription['date_inscription'], $inscription['id_inscription']]);
    return (int) $stmt->fetchColumn();
}

function inscrireAEvenement(PDO $pdo, int $idEvt, int $idUser): array
{
    $pdo->beginTransaction();
    try {
        $evt = chargerEvenement($pdo, $idEvt, true);
        if (!$evt || $evt['statut'] !== 'publie') {
            $pdo->rollBack();
            return ['danger', "Cet événement n'est plus ouvert aux inscriptions."];
        }
        if ($evt['date_debut'] <= date('Y-m-d H:i:s')) {
            $pdo->rollBack();
            return ['warning', "Les inscriptions sont closes : l'événement a déjà commencé."];
        }
        $existante = inscriptionDe($pdo, $idEvt, $idUser);
        if ($existante && in_array($existante['statut'], ['inscrit', 'attente'], true)) {
            $pdo->rollBack();
            return ['info', "Vous êtes déjà " . ($existante['statut'] === 'inscrit' ? 'inscrit' : "sur la liste d'attente") . "."];
        }

        $statut = placesRestantes($evt) > 0 ? 'inscrit' : 'attente';
        $prioritaire = niveauMembre($pdo, $idUser)['prioritaire'] ? 1 : 0;
        if ($existante) {
            $pdo->prepare("UPDATE evenement_inscription SET statut = ?, prioritaire = ?, date_inscription = NOW(), present = 0, date_presence = NULL WHERE id_inscription = ?")
                ->execute([$statut, $prioritaire, $existante['id_inscription']]);
        } else {
            $pdo->prepare("INSERT INTO evenement_inscription (id_evenement, id_user, statut, prioritaire, code, code_court) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$idEvt, $idUser, $statut, $prioritaire, bin2hex(random_bytes(16)), genererCodeBillet($pdo)]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    insertLog('EVENEMENT', "Inscription ($statut) à l'événement #$idEvt");
    $email = emailUtilisateur($pdo, $idUser);
    if ($statut === 'inscrit') {
        notifierParMail($email, "Inscription confirmée : " . $evt['titre'],
            "Votre place est réservée pour « {$evt['titre']} ».\n\nQuand : " . horaireEvenement($evt) . "\nOù : {$evt['lieu']}\n\n"
            . "Votre billet (avec son QR code) est dans votre espace : " . SITE_URL . "/mes_evenements.php");
        return ['success', "C'est noté, votre place est réservée ! Votre billet est disponible ci-dessous."];
    }
    notifierParMail($email, "Liste d'attente : " . $evt['titre'],
        "L'événement « {$evt['titre']} » est complet : vous êtes sur la liste d'attente.\n"
        . "Nous vous écrirons si une place se libère pour vous.");
    return ['warning', "L'événement est complet : vous êtes sur la liste d'attente. Nous vous préviendrons par mail si une place se libère."];
}

function desinscrireDeEvenement(PDO $pdo, int $idEvt, int $idUser, bool $parEquipe = false): array
{
    $promus = [];
    $pdo->beginTransaction();
    try {
        $evt = chargerEvenement($pdo, $idEvt, true);
        $inscription = inscriptionDe($pdo, $idEvt, $idUser);
        if (!$evt || !$inscription || !in_array($inscription['statut'], ['inscrit', 'attente'], true)) {
            $pdo->rollBack();
            return ['warning', "Aucune inscription en cours pour cet événement."];
        }
        $pdo->prepare("UPDATE evenement_inscription SET statut = 'annule', present = 0, date_presence = NULL WHERE id_inscription = ?")
            ->execute([$inscription['id_inscription']]);
        if ($inscription['statut'] === 'inscrit' && $evt['attente_auto'] && $evt['statut'] === 'publie') {
            $promus = promouvoirListeAttente($pdo, $idEvt);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    insertLog('EVENEMENT', ($parEquipe ? "Retrait par l'équipe" : "Désinscription") . " de l'utilisateur #$idUser (événement #$idEvt)");
    prevenirPromus($promus, $evt);
    return ['success', $inscription['statut'] === 'inscrit' ? "Inscription annulée, votre place a été libérée." : "Vous avez quitté la liste d'attente."];
}

function promouvoirListeAttente(PDO $pdo, int $idEvt, ?int $idInscription = null): array
{
    $evt = chargerEvenement($pdo, $idEvt, true);
    $places = placesRestantes($evt);
    if ($places <= 0) {
        return [];
    }
    if ($idInscription !== null) {
        $stmt = $pdo->prepare("
            SELECT i.id_inscription, u.email FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
            WHERE i.id_evenement = ? AND i.statut = 'attente' AND i.id_inscription = ?");
        $stmt->execute([$idEvt, $idInscription]);
    } else {
        $stmt = $pdo->prepare("
            SELECT i.id_inscription, u.email FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
            WHERE i.id_evenement = ? AND i.statut = 'attente'
            ORDER BY i.prioritaire DESC, i.date_inscription, i.id_inscription LIMIT $places");
        $stmt->execute([$idEvt]);
    }
    $promus = $stmt->fetchAll();
    $maj = $pdo->prepare("UPDATE evenement_inscription SET statut = 'inscrit' WHERE id_inscription = ?");
    foreach ($promus as $p) {
        $maj->execute([$p['id_inscription']]);
    }
    return $promus;
}

function prevenirPromus(array $promus, array $evt): void
{
    foreach ($promus as $p) {
        insertLog('EVENEMENT', "Inscription #{$p['id_inscription']} passée de la liste d'attente à inscrit (événement #{$evt['id_evenement']})");
        notifierParMail($p['email'], "Une place s'est libérée : " . $evt['titre'],
            "Bonne nouvelle : une place s'est libérée pour « {$evt['titre']} » et elle est à vous !\n\n"
            . "Quand : " . horaireEvenement($evt) . "\nOù : {$evt['lieu']}\n\n"
            . "Votre billet est dans votre espace : " . SITE_URL . "/mes_evenements.php\n"
            . "Si vous ne pouvez plus venir, pensez à vous désinscrire pour laisser la place à quelqu'un d'autre.");
    }
}

function notifierParticipants(PDO $pdo, int $idEvt, string $sujet, string $texte): int
{
    $stmt = $pdo->prepare("
        SELECT u.email FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
        WHERE i.id_evenement = ? AND i.statut IN ('inscrit', 'attente')");
    $stmt->execute([$idEvt]);
    $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($emails as $email) {
        notifierParMail($email, $sujet, $texte);
    }
    return count($emails);
}

function emailUtilisateur(PDO $pdo, int $idUser): string
{
    $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    return (string) $stmt->fetchColumn();
}

const EVT_STATUTS = [
    'en_attente' => ['En attente de validation', 'attente', 'hourglass-split'],
    'publie'     => ['Publié', '', 'check2-circle'],
    'refuse'     => ['Refusé', 'annulee', 'x-circle'],
    'retire'     => ['Retiré', 'attente', 'arrow-counterclockwise'],
    'annule'     => ['Annulé', 'annulee', 'x-octagon'],
];
const EVT_LIVRES_MAX = 5;
const EVT_PLACES_MAX = 500;
const EVT_MOTIF_MAX = 500;

function erreurEvenement(array $valeurs, ?array $evt): string
{
    $debut = strtotime((string) $valeurs['date_debut']);
    $fin = strtotime((string) $valeurs['date_fin']);
    $places = (int) $valeurs['places_max'];
    $doitEtreFutur = !$evt || $evt['statut'] === 'en_attente';

    if ($valeurs['titre'] === '' || $valeurs['description'] === '' || $valeurs['lieu'] === '') {
        return "Le titre, la description et le lieu sont obligatoires.";
    }
    if (mb_strlen($valeurs['titre']) > 200 || mb_strlen($valeurs['lieu']) > 255 || mb_strlen((string) $valeurs['auteurs']) > 255) {
        return "Le titre (200 caractères), le lieu ou les intervenants (255 caractères) sont trop longs.";
    }
    if (!isset(EVT_TYPES[$valeurs['type']])) {
        return "Choisissez un type d'événement.";
    }
    if (!$debut || !$fin || $fin <= $debut) {
        return "L'heure de fin doit être après l'heure de début.";
    }
    if ($doitEtreFutur && $debut <= time()) {
        return "La date de début doit être dans le futur.";
    }
    if ($places < 1 || $places > EVT_PLACES_MAX) {
        return "Le nombre de places doit être compris entre 1 et " . EVT_PLACES_MAX . ".";
    }
    if ($evt && $places < (int) $evt['nb_inscrits']) {
        return "Il y a déjà {$evt['nb_inscrits']} inscrits : impossible de descendre à $places places. Retirez d'abord des participants.";
    }
    return '';
}

function estPropositionAuteur(array $evt): bool
{
    return in_array($evt['statut'], ['en_attente', 'refuse', 'retire'], true) || !empty($evt['date_validation']);
}

function proposeurEvenement(PDO $pdo, array $evt): ?array
{
    if (!estPropositionAuteur($evt) || empty($evt['id_createur'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id, pseudo, prenom, nom, email FROM user WHERE id = ?");
    $stmt->execute([$evt['id_createur']]);
    $proposeur = $stmt->fetch();
    if (!$proposeur) {
        return null;
    }
    $nomComplet = trim(($proposeur['prenom'] ?? '') . ' ' . ($proposeur['nom'] ?? ''));
    $proposeur['nom_affiche'] = $nomComplet !== '' ? $nomComplet : $proposeur['pseudo'];
    return $proposeur;
}

function libelleStatutEvenement(array $evt): string
{
    [$libelle, $classe, $icone] = EVT_STATUTS[$evt['statut']] ?? [ucfirst($evt['statut']), 'attente', 'circle'];
    if ($evt['statut'] === 'publie' && $evt['date_fin'] < date('Y-m-d H:i:s')) {
        [$libelle, $classe, $icone] = ['Terminé', 'attente', 'flag'];
    }
    return '<span class="etiquette-profil ' . $classe . '"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> ' . $libelle . '</span>';
}

function livresEvenement(PDO $pdo, int $idEvt): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT l.id_livre, l.titre, l.auteur, l.couverture
            FROM evenement_livre el INNER JOIN livre l ON l.id_livre = el.id_livre
            WHERE el.id_evenement = ? AND l.is_valide = 1
            ORDER BY l.titre");
        $stmt->execute([$idEvt]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function livresAssociables(PDO $pdo, int $idAuteur = 0): array
{
    $stmt = $pdo->prepare("
        SELECT id_livre, titre, auteur, couverture, (id_user_auteur <=> ?) AS est_a_moi
        FROM livre WHERE is_valide = 1
        ORDER BY est_a_moi DESC, titre");
    $stmt->execute([$idAuteur ?: null]);
    return $stmt->fetchAll();
}

function livresChoisis(PDO $pdo, mixed $saisie): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', is_array($saisie) ? $saisie : []), fn($id) => $id > 0)));
    if (count($ids) > EVT_LIVRES_MAX) {
        return [[], "Choisissez au maximum " . EVT_LIVRES_MAX . " livres."];
    }
    if (!$ids) {
        return [[], ''];
    }
    $marqueurs = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id_livre FROM livre WHERE is_valide = 1 AND id_livre IN ($marqueurs)");
    $stmt->execute($ids);
    return [array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), ''];
}

function enregistrerLivresEvenement(PDO $pdo, int $idEvt, array $idsLivres): void
{
    $pdo->prepare("DELETE FROM evenement_livre WHERE id_evenement = ?")->execute([$idEvt]);
    $ajout = $pdo->prepare("INSERT INTO evenement_livre (id_evenement, id_livre) VALUES (?, ?)");
    foreach ($idsLivres as $idLivre) {
        $ajout->execute([$idEvt, $idLivre]);
    }
}

function validerEvenementPropose(PDO $pdo, int $idEvt, int $idValidateur): array
{
    $pdo->beginTransaction();
    try {
        $evt = chargerEvenement($pdo, $idEvt, true);
        if (!$evt || $evt['statut'] !== 'en_attente') {
            $pdo->rollBack();
            return ['warning', "Cette proposition a déjà été traitée."];
        }
        if ($evt['date_debut'] <= date('Y-m-d H:i:s')) {
            $pdo->rollBack();
            return ['warning', "La date de l'événement est passée : modifiez-la avant de le valider."];
        }
        $pdo->prepare("UPDATE evenement SET statut = 'publie', date_validation = NOW(), id_validateur = ?, motif_refus = NULL WHERE id_evenement = ?")
            ->execute([$idValidateur, $idEvt]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    insertLog('EVENEMENT', "Validation de l'événement proposé #$idEvt : {$evt['titre']}");
    $proposeur = proposeurEvenement($pdo, $evt);
    if ($proposeur) {
        notifierParMail($proposeur['email'], "Votre événement est publié : " . $evt['titre'],
            "Bonne nouvelle : l'équipe a validé votre événement « {$evt['titre']} ». Il est maintenant visible dans le calendrier et ouvert aux inscriptions.\n\n"
            . "Quand : " . horaireEvenement($evt) . "\nOù : {$evt['lieu']}\nPlaces : {$evt['places_max']}\n\n"
            . "Page de l'événement : " . SITE_URL . "/evenement.php?id=$idEvt\n"
            . "Suivez les inscriptions depuis votre espace créateur : " . SITE_URL . "/dashboard_auteur.php");
    }
    return ['success', "Événement validé : il est publié dans le calendrier" . ($proposeur ? " et l'auteur a été prévenu par mail." : ".")];
}

function refuserEvenementPropose(PDO $pdo, int $idEvt, int $idValidateur, string $motif): array
{
    $motif = trim($motif);
    if ($motif === '') {
        return ['danger', "Indiquez le motif du refus : il sera envoyé à l'auteur."];
    }
    if (mb_strlen($motif) > EVT_MOTIF_MAX) {
        return ['danger', "Le motif est trop long (" . EVT_MOTIF_MAX . " caractères maximum)."];
    }
    $stmt = $pdo->prepare("UPDATE evenement SET statut = 'refuse', motif_refus = ?, date_validation = NOW(), id_validateur = ? WHERE id_evenement = ? AND statut = 'en_attente'");
    $stmt->execute([$motif, $idValidateur, $idEvt]);
    if ($stmt->rowCount() === 0) {
        return ['warning', "Cette proposition a déjà été traitée."];
    }

    $evt = chargerEvenement($pdo, $idEvt);
    insertLog('EVENEMENT', "Refus de l'événement proposé #$idEvt : {$evt['titre']}");
    $proposeur = proposeurEvenement($pdo, $evt);
    if ($proposeur) {
        notifierParMail($proposeur['email'], "Votre proposition d'événement : " . $evt['titre'],
            "L'équipe a examiné votre proposition « {$evt['titre']} » (" . horaireEvenement($evt) . ") et ne peut pas la publier.\n\n"
            . "Motif :\n$motif\n\n"
            . "Vous pouvez proposer un nouvel événement en tenant compte de cette remarque depuis votre espace créateur : " . SITE_URL . "/dashboard_auteur.php");
    }
    return ['success', "Proposition refusée" . ($proposeur ? " : l'auteur a reçu le motif par mail." : ".")];
}

function prevenirAuteurAnnulation(PDO $pdo, array $evt, string $motif): void
{
    $proposeur = proposeurEvenement($pdo, $evt);
    if (!$proposeur) {
        return;
    }
    notifierParMail($proposeur['email'], "Votre événement est annulé : " . $evt['titre'],
        "L'équipe a annulé votre événement « {$evt['titre']} » prévu " . horaireEvenement($evt) . "."
        . ($motif !== '' ? "\n\nRaison : $motif" : '')
        . "\n\nLes personnes inscrites ont été prévenues par mail.");
}

function retirerPropositionEvenement(PDO $pdo, int $idEvt, int $idAuteur): bool
{
    $stmt = $pdo->prepare("UPDATE evenement SET statut = 'retire', date_annulation = NOW() WHERE id_evenement = ? AND id_createur = ? AND statut = 'en_attente'");
    $stmt->execute([$idEvt, $idAuteur]);
    if ($stmt->rowCount() === 0) {
        return false;
    }
    insertLog('EVENEMENT', "Proposition d'événement #$idEvt retirée par son auteur");
    return true;
}
