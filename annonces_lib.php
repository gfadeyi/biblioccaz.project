<?php
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/upload_lib.php';

const ANNONCE_PRIX_MIN = 0.5;
const ANNONCE_PRIX_MAX = 150;
const ANNONCES_ACTIVES_MAX = 20;
const ANNONCE_PHOTOS_MAX = 3;
const ANNONCE_DESCRIPTION_MAX = 2000;
const ANNONCE_MOTIF_MAX = 500;
const ANNONCE_DOSSIER_PHOTOS = 'annonces/';
const ROLES_VENDEURS = ['client', 'auteur'];

const ANNONCE_STATUTS = [
    'en_validation' => ['En attente de validation', 'attente', 'hourglass-split'],
    'publiee'       => ['En ligne', '', 'check2-circle'],
    'refusee'       => ['Refusée', 'refuse', 'x-circle'],
    'retiree'       => ['Retirée', 'neutre', 'archive'],
    'reservee'      => ['Réservée', 'attente', 'lock'],
    'vendue'        => ['Vendue', '', 'bag-check'],
];
const ANNONCE_STATUTS_ACTIFS = ['en_validation', 'publiee', 'reservee'];
const ANNONCE_STATUTS_MODIFIABLES = ['en_validation', 'publiee', 'refusee', 'retiree'];
const MODES_REMISE = [
    'main_propre' => ['En main propre', 'person-raised-hand'],
    'envoi'       => ['Envoi postal', 'envelope-paper'],
    'les_deux'    => ['En main propre ou par envoi', 'arrow-left-right'],
];

function raisonInterdictionVente(PDO $pdo, int $idUser): string
{
    $stmt = $pdo->prepare("SELECT role, statut, email_verifie FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $membre = $stmt->fetch();
    if (!$membre) {
        return "Compte introuvable.";
    }
    if (!in_array($membre['role'], ROLES_VENDEURS, true)) {
        return "La revente est réservée aux comptes lecteurs et auteurs : les comptes de l'équipe ne peuvent pas vendre.";
    }
    if (in_array($membre['statut'], STATUTS_COMPTE_SUSPENDU, true)) {
        return "Votre compte est suspendu : vous ne pouvez pas publier d'annonce.";
    }
    if (!(int) $membre['email_verifie']) {
        return "Confirmez d'abord votre adresse email pour pouvoir vendre.";
    }
    return '';
}

function nombreAnnoncesActives(PDO $pdo, int $idUser): int
{
    $marqueurs = implode(', ', array_fill(0, count(ANNONCE_STATUTS_ACTIFS), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exemplaire WHERE id_proprietaire = ? AND statut_annonce IN ($marqueurs)");
    $stmt->execute([$idUser, ...ANNONCE_STATUTS_ACTIFS]);
    return (int) $stmt->fetchColumn();
}

function annonceParId(PDO $pdo, int $idAnnonce): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l.titre, l.auteur, l.couverture, l.is_valide AS livre_valide, l.id_categorie, l.editeur, l.annee_parution,
               v.pseudo AS vendeur_pseudo, v.photo AS vendeur_photo, v.email AS vendeur_email, v.ville AS vendeur_ville,
               v.statut AS vendeur_statut, v.date_inscription AS vendeur_depuis,
               m.pseudo AS moderateur_pseudo
        FROM exemplaire e
        INNER JOIN livre l ON l.id_livre = e.id_livre
        INNER JOIN user v ON v.id = e.id_proprietaire
        LEFT JOIN user m ON m.id = e.id_moderateur
        WHERE e.id_exemplaire = ? AND e.id_proprietaire IS NOT NULL AND e.type_offre = 'vente'");
    $stmt->execute([$idAnnonce]);
    return $stmt->fetch() ?: null;
}

function photosAnnonce(PDO $pdo, int $idAnnonce): array
{
    $stmt = $pdo->prepare("SELECT id_photo, fichier FROM exemplaire_photo WHERE id_exemplaire = ? ORDER BY ordre, id_photo");
    $stmt->execute([$idAnnonce]);
    return $stmt->fetchAll();
}

function premieresPhotos(PDO $pdo, array $idsAnnonces): array
{
    if (!$idsAnnonces) {
        return [];
    }
    $marqueurs = implode(', ', array_fill(0, count($idsAnnonces), '?'));
    $stmt = $pdo->prepare("SELECT id_exemplaire, fichier FROM exemplaire_photo WHERE id_exemplaire IN ($marqueurs) ORDER BY ordre DESC, id_photo DESC");
    $stmt->execute(array_values($idsAnnonces));
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function urlPhotoAnnonce(?string $fichier, ?string $couvertureLivre, string $racine = ''): string
{
    return $fichier ? urlImage(ANNONCE_DOSSIER_PHOTOS . $fichier, $racine) : urlImage($couvertureLivre, $racine);
}

function pastilleStatutAnnonce(string $statut): string
{
    [$libelle, $classe, $icone] = ANNONCE_STATUTS[$statut] ?? [$statut, 'neutre', 'question-circle'];
    return '<span class="pastille-etat ' . $classe . '"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> ' . htmlspecialchars($libelle) . '</span>';
}

function libelleModeRemise(?string $mode, ?string $ville): string
{
    $libelle = MODES_REMISE[$mode][0] ?? 'À convenir';
    return $ville && $mode !== 'envoi' ? $libelle . ' · ' . $ville : $libelle;
}

function lirePrix(string $saisie): ?float
{
    $saisie = str_replace([' ', "\u{00A0}", '€', ','], ['', '', '', '.'], trim($saisie));
    return preg_match('/^\d{1,4}(\.\d{1,2})?$/', $saisie) ? round((float) $saisie, 2) : null;
}

function enregistrerPhotosAnnonce(array $fichiers): array
{
    $noms = [];
    foreach ($fichiers as $fichier) {
        [$nom, $erreur] = enregistrerImageEnvoyee($fichier, ANNONCE_DOSSIER_PHOTOS);
        if ($erreur !== '') {
            supprimerFichiersPhotos($noms);
            return [[], $erreur];
        }
        $noms[] = $nom;
    }
    return [$noms, ''];
}

function supprimerFichiersPhotos(array $fichiers): void
{
    foreach ($fichiers as $fichier) {
        $chemin = __DIR__ . '/img/' . ANNONCE_DOSSIER_PHOTOS . basename($fichier);
        if (is_file($chemin)) {
            @unlink($chemin);
        }
    }
}

function supprimerAnnonce(PDO $pdo, int $idAnnonce): bool
{
    $fichiers = array_column(photosAnnonce($pdo, $idAnnonce), 'fichier');
    $stmt = $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND id_proprietaire IS NOT NULL AND statut_annonce NOT IN ('reservee', 'vendue')");
    $stmt->execute([$idAnnonce]);
    if ($stmt->rowCount() === 0) {
        return false;
    }
    supprimerFichiersPhotos($fichiers);
    return true;
}

function modererAnnonce(PDO $pdo, int $idAnnonce, string $decision, int $idModerateur, string $motif): array
{
    $transitions = [
        'valider' => [['en_validation'], 'publiee'],
        'refuser' => [['en_validation'], 'refusee'],
        'retirer' => [['publiee'], 'retiree'],
    ];
    if (!isset($transitions[$decision])) {
        return ['danger', "Action inconnue."];
    }
    [$statutsDepart, $nouveauStatut] = $transitions[$decision];
    $motif = mb_substr(trim($motif), 0, ANNONCE_MOTIF_MAX);
    if ($decision !== 'valider' && $motif === '') {
        return ['warning', "Indiquez le motif : il est envoyé au vendeur."];
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT statut_annonce, id_livre FROM exemplaire WHERE id_exemplaire = ? AND id_proprietaire IS NOT NULL FOR UPDATE");
    $stmt->execute([$idAnnonce]);
    $actuelle = $stmt->fetch();
    if (!$actuelle || !in_array($actuelle['statut_annonce'], $statutsDepart, true)) {
        $pdo->rollBack();
        return ['warning', "Cette annonce a déjà été traitée."];
    }
    $pdo->prepare("UPDATE exemplaire SET statut_annonce = ?, motif_moderation = ?, id_moderateur = ?, date_moderation = NOW() WHERE id_exemplaire = ?")
        ->execute([$nouveauStatut, $decision === 'valider' ? null : $motif, $idModerateur, $idAnnonce]);
    $ficheValidee = false;
    if ($decision === 'valider') {
        $ficheValidee = $pdo->prepare("UPDATE livre SET is_valide = 1 WHERE id_livre = ? AND is_valide = 0");
        $ficheValidee->execute([$actuelle['id_livre']]);
        $ficheValidee = $ficheValidee->rowCount() > 0;
    }
    $pdo->commit();

    $annonce = annonceParId($pdo, $idAnnonce);
    $lien = SITE_URL . '/' . ($decision === 'valider' ? 'annonce.php?id=' . $idAnnonce : 'mes_annonces.php');
    $textes = [
        'valider' => ["Votre annonce est en ligne", "Bonne nouvelle : votre annonce « {$annonce['titre']} » a été validée par l'équipe. Elle est maintenant visible sur BIBLIOccaz et les lecteurs peuvent la consulter.", "Voir mon annonce"],
        'refuser' => ["Votre annonce n'a pas été validée", "Votre annonce « {$annonce['titre']} » n'a pas été validée par l'équipe.\n\nMotif : $motif\n\nVous pouvez la modifier depuis « Mes annonces » : elle repassera en validation.", "Mes annonces"],
        'retirer' => ["Votre annonce a été retirée", "L'équipe a retiré votre annonce « {$annonce['titre']} » du site.\n\nMotif : $motif\n\nVous pouvez la modifier depuis « Mes annonces » pour la proposer de nouveau.", "Mes annonces"],
    ];
    [$sujet, $texte, $bouton] = $textes[$decision];
    notifierParMail($annonce['vendeur_email'], $sujet, "Bonjour {$annonce['vendeur_pseudo']},\n\n$texte", $lien, $bouton);
    insertLog('ANNONCE', ucfirst($decision === 'valider' ? 'validation' : ($decision === 'refuser' ? 'refus' : 'retrait')) . " de l'annonce #$idAnnonce" . ($motif !== '' ? " ($motif)" : ''));

    $messages = [
        'valider' => "Annonce validée et mise en ligne" . ($ficheValidee ? ", avec sa nouvelle fiche livre" : '') . ". Le vendeur a été prévenu.",
        'refuser' => "Annonce refusée. Le vendeur a reçu le motif par mail.",
        'retirer' => "Annonce retirée du site. Le vendeur a reçu le motif par mail.",
    ];
    return ['success', $messages[$decision]];
}

function nombreAnnoncesAValider(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM exemplaire WHERE statut_annonce = 'en_validation'")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
