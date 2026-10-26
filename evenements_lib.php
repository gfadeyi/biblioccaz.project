<?php
require_once __DIR__ . '/points_lib.php';
require_once __DIR__ . '/notifications_lib.php';

const EVT_TYPES = [
    'dedicace'  => ['Dédicace', 'pen'],
    'rencontre' => ['Rencontre', 'people'],
    'atelier'   => ['Atelier', 'easel'],
];
const EVT_MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
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
    [$libelle, $classe] = match (etatEvenement($evt)) {
        'annule'   => ['Annulé', 'bg-danger'],
        'termine'  => ['Terminé', 'bg-secondary'],
        'en_cours' => ['En cours', 'bg-info text-dark'],
        'complet'  => ['Complet', 'bg-warning text-dark'],
        default    => ['Places disponibles', 'bg-success'],
    };
    return "<span class=\"badge rounded-pill $classe\">$libelle</span>";
}

function badgeTypeEvenement(string $type): string
{
    [$libelle, $icone] = EVT_TYPES[$type] ?? [ucfirst($type), 'calendar-event'];
    return "<span class=\"badge rounded-pill bg-success-subtle text-success border border-success-subtle\"><i class=\"bi bi-$icone\"></i> $libelle</span>";
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
    return $racine . 'img/' . rawurlencode($image ?: 'default.png');
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
