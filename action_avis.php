<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'points_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$idLivre = (int) ($_POST['id_livre'] ?? 0);
$retour = $_POST['retour'] ?? '';
$urlRetour = $retour === 'mes_avis' ? 'avis.php' : "detail_livre.php?id=$idLivre#avis";

function finir(string $url, string $type, string $message): void
{
    $_SESSION['flash_avis'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (!verifierCsrf()) {
    finir($urlRetour, 'danger', "Session expirée, merci de réessayer.");
}

$idUser = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'enregistrer') {
        $note = (int) ($_POST['note'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($note < 1 || $note > 5) {
            finir($urlRetour, 'danger', "Choisissez une note entre 1 et 5 étoiles.");
        }
        if (mb_strlen($commentaire) < AVIS_COMMENTAIRE_MIN || mb_strlen($commentaire) > AVIS_COMMENTAIRE_MAX) {
            finir($urlRetour, 'danger', "Le commentaire doit faire entre " . AVIS_COMMENTAIRE_MIN . " et " . AVIS_COMMENTAIRE_MAX . " caractères.");
        }
        $titre = mb_substr($titre, 0, 120);

        $stmt = $pdo->prepare("SELECT id_avis FROM avis WHERE id_livre = ? AND id_user = ?");
        $stmt->execute([$idLivre, $idUser]);
        $existant = $stmt->fetchColumn();

        if ($existant) {
            $pdo->prepare("UPDATE avis SET note = ?, titre = ?, commentaire = ?, date_modification = NOW() WHERE id_avis = ?")
                ->execute([$note, $titre ?: null, $commentaire, $existant]);
            insertLog('AVIS', "Avis #$existant modifié (livre #$idLivre)");
            finir($urlRetour, 'success', "Votre avis a été mis à jour.");
        }

        $pdo->prepare("INSERT INTO avis (id_livre, id_user, note, titre, commentaire) VALUES (?, ?, ?, ?, ?)")
            ->execute([$idLivre, $idUser, $note, $titre ?: null, $commentaire]);
        insertLog('AVIS', "Nouvel avis ($note/5) sur le livre #$idLivre");

        $message = "Merci, votre avis est publié !";
        if (($_SESSION['role'] ?? '') === 'client') {
            $stmt = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ?");
            $stmt->execute([$idLivre]);
            $message .= match (proposerPointsAvis($pdo, $idUser, $idLivre, (string) $stmt->fetchColumn())) {
                'en_attente' => " " . pointsRegle($pdo, 'avis') . " points en attente de validation par notre équipe.",
                'non_achete' => " Les points sont réservés aux avis sur un livre acheté sur le site.",
                'deja_paye' => " Ce livre a déjà rapporté des points pour un avis.",
                'quota' => " Vous avez atteint les " . quotaRegle($pdo, 'avis') . " avis rémunérés ce mois-ci.",
                default => '',
            };
        }
        finir($urlRetour, 'success', $message);

    } elseif ($action === 'supprimer') {
        $stmt = $pdo->prepare("SELECT id_livre FROM avis WHERE id_avis = ? AND id_user = ?");
        $stmt->execute([(int) ($_POST['id_avis'] ?? 0), $idUser]);
        $idLivreSupprime = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM avis WHERE id_avis = ? AND id_user = ?");
        $stmt->execute([(int) ($_POST['id_avis'] ?? 0), $idUser]);
        if ($stmt->rowCount() && $idLivreSupprime) {
            changerStatutMouvement($pdo, $idUser, 'avis', 'livre:' . $idLivreSupprime, ['en_attente', 'valide'], 'annule');
        }
        insertLog('AVIS', "Avis supprimé par son auteur (livre #$idLivre)");
        finir($urlRetour, 'success', "Votre avis a été supprimé.");

    } elseif ($action === 'signaler') {
        $idAvis = (int) ($_POST['id_avis'] ?? 0);
        $motif = $_POST['motif'] ?? '';
        if (!isset(AVIS_MOTIFS[$motif])) {
            finir($urlRetour, 'danger', "Choisissez un motif de signalement.");
        }
        $stmt = $pdo->prepare("INSERT IGNORE INTO avis_signalement (id_avis, id_user, motif)
                               SELECT id_avis, ?, ? FROM avis WHERE id_avis = ? AND id_user <> ?");
        $stmt->execute([$idUser, $motif, $idAvis, $idUser]);
        insertLog('AVIS', "Signalement de l'avis #$idAvis ($motif)");
        finir($urlRetour, 'success', "Merci, l'avis a été signalé à l'équipe de modération.");
    }
} catch (PDOException $e) {
    insertLog('ERREUR', "Avis : " . $e->getMessage());
    finir($urlRetour, 'danger', "Une erreur est survenue, réessayez plus tard.");
}

header("Location: $urlRetour");
exit();
