<?php
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/dons_lib.php';
require_once __DIR__ . '/points_lib.php';

const ADRESSES_MAX = 10;

function pseudoAffiche(string $pseudo): string
{
    return initialePseudo($pseudo) . mb_substr($pseudo, 1, null, 'UTF-8');
}

function initialePseudo(string $pseudo): string
{
    return mb_strtoupper(mb_substr($pseudo, 0, 1, 'UTF-8'), 'UTF-8');
}

const PHOTO_PROFIL_DOSSIER = __DIR__ . '/img/profils/';
const PHOTO_PROFIL_TAILLE_MAX = 2 * 1024 * 1024;
const PHOTO_PROFIL_COTE = 320;
const PHOTO_PROFIL_DIMENSION_MAX = 8000;
const PHOTO_PROFIL_TYPES = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
const TEINTES_AVATAR = 5;

function cheminPhotoMembre(?string $photo): ?string
{
    if (!$photo || basename($photo) !== $photo || !is_file(PHOTO_PROFIL_DOSSIER . $photo)) {
        return null;
    }
    return 'img/profils/' . rawurlencode($photo) . '?v=' . filemtime(PHOTO_PROFIL_DOSSIER . $photo);
}

function avatarMembre(string $pseudo, ?string $photo, string $classe = '', string $prefixe = ''): string
{
    $classes = trim('avatar-lecteur ' . $classe);
    $chemin = cheminPhotoMembre($photo);
    if ($chemin) {
        return '<span class="' . $classes . ' avec-photo" aria-hidden="true"><img src="' . htmlspecialchars($prefixe . $chemin) . '" alt="" loading="lazy"></span>';
    }
    $teinte = crc32(mb_strtolower($pseudo, 'UTF-8')) % TEINTES_AVATAR + 1;
    return '<span class="' . $classes . ' teinte-' . $teinte . '" aria-hidden="true">' . htmlspecialchars(initialePseudo($pseudo)) . '</span>';
}

function photoProfilDe(PDO $pdo, int $idUser): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT photo FROM user WHERE id = ?");
        $stmt->execute([$idUser]);
        return $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function imageRedresseeSelonExif($image, string $fichier)
{
    if (!function_exists('exif_read_data')) {
        return $image;
    }
    $exif = @exif_read_data($fichier);
    $rotations = [3 => 180, 6 => -90, 8 => 90];
    $orientation = (int) ($exif['Orientation'] ?? 1);
    return isset($rotations[$orientation]) ? imagerotate($image, $rotations[$orientation], 0) : $image;
}

function enregistrerPhotoProfil(PDO $pdo, int $idUser, array $fichier): void
{
    $erreur = $fichier['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($erreur === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException("Choisissez d'abord une image.");
    }
    if (in_array($erreur, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || ($fichier['size'] ?? 0) > PHOTO_PROFIL_TAILLE_MAX) {
        throw new RuntimeException("Votre photo est trop lourde : 2 Mo maximum.");
    }
    if ($erreur !== UPLOAD_ERR_OK || !is_uploaded_file($fichier['tmp_name'])) {
        throw new RuntimeException("L'envoi de la photo a échoué, merci de réessayer.");
    }
    $infos = @getimagesize($fichier['tmp_name']);
    if (!$infos || !isset(PHOTO_PROFIL_TYPES[$infos[2]])) {
        throw new RuntimeException("Ce fichier n'est pas une image JPG, PNG ou WebP.");
    }
    [$largeur, $hauteur] = $infos;
    if ($largeur < 1 || $hauteur < 1 || $largeur > PHOTO_PROFIL_DIMENSION_MAX || $hauteur > PHOTO_PROFIL_DIMENSION_MAX) {
        throw new RuntimeException("Les dimensions de cette image ne sont pas acceptées.");
    }

    $ouvrir = PHOTO_PROFIL_TYPES[$infos[2]];
    $source = @$ouvrir($fichier['tmp_name']);
    if (!$source) {
        throw new RuntimeException("Cette image est illisible, essayez-en une autre.");
    }
    if ($infos[2] === IMAGETYPE_JPEG) {
        $source = imageRedresseeSelonExif($source, $fichier['tmp_name']);
    }
    $largeur = imagesx($source);
    $hauteur = imagesy($source);
    $cote = min($largeur, $hauteur);
    $carre = imagecreatetruecolor(PHOTO_PROFIL_COTE, PHOTO_PROFIL_COTE);
    imagealphablending($carre, false);
    imagesavealpha($carre, true);
    imagefill($carre, 0, 0, imagecolorallocatealpha($carre, 0, 0, 0, 127));
    imagecopyresampled($carre, $source, 0, 0, intdiv($largeur - $cote, 2), intdiv($hauteur - $cote, 2), PHOTO_PROFIL_COTE, PHOTO_PROFIL_COTE, $cote, $cote);

    if (!is_dir(PHOTO_PROFIL_DOSSIER)) {
        mkdir(PHOTO_PROFIL_DOSSIER, 0755, true);
    }
    $nomFichier = bin2hex(random_bytes(12)) . '.webp';
    if (!imagewebp($carre, PHOTO_PROFIL_DOSSIER . $nomFichier, 85)) {
        throw new RuntimeException("La photo n'a pas pu être enregistrée.");
    }

    $anciennePhoto = photoProfilDe($pdo, $idUser);
    $pdo->prepare("UPDATE user SET photo = ? WHERE id = ?")->execute([$nomFichier, $idUser]);
    effacerFichierPhoto($anciennePhoto);
}

function supprimerPhotoProfil(PDO $pdo, int $idUser): void
{
    $anciennePhoto = photoProfilDe($pdo, $idUser);
    $pdo->prepare("UPDATE user SET photo = NULL WHERE id = ?")->execute([$idUser]);
    effacerFichierPhoto($anciennePhoto);
}

function effacerFichierPhoto(?string $photo): void
{
    if ($photo && basename($photo) === $photo && is_file(PHOTO_PROFIL_DOSSIER . $photo)) {
        unlink(PHOTO_PROFIL_DOSSIER . $photo);
    }
}

function resumeCompte(PDO $pdo, int $idUser): array
{
    $requetes = [
        'commandes'        => "SELECT COUNT(*) FROM commande WHERE id_user = ? AND statut <> 'annulee'",
        'alertes'          => "SELECT COUNT(*) FROM alerte_stock WHERE id_user = ? AND date_envoi IS NULL",
        'avis'             => "SELECT COUNT(*) FROM avis WHERE id_user = ?",
        'dons'             => "SELECT COUNT(*) FROM don WHERE id_donneur = ? AND statut IN ('disponible', 'reserve') AND date_limite >= CURDATE()",
        'dons_recuperes'   => "SELECT COUNT(*) FROM don WHERE id_donneur = ? AND statut = 'recupere'",
        'evenements'       => "SELECT COUNT(*) FROM evenement_inscription i INNER JOIN evenement e ON e.id_evenement = i.id_evenement
                               WHERE i.id_user = ? AND i.statut IN ('inscrit', 'attente') AND e.statut = 'publie' AND e.date_fin >= NOW()",
    ];
    $resume = [];
    foreach ($requetes as $cle => $sql) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUser]);
            $resume[$cle] = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $resume[$cle] = 0;
        }
    }
    $resume['niveau'] = niveauMembre($pdo, $idUser);
    return $resume;
}

function stockDuLivre(PDO $pdo, int $idLivre): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) FROM exemplaire WHERE id_livre = ? AND is_disponible = 1 AND quantite > 0");
    $stmt->execute([$idLivre]);
    return (int) $stmt->fetchColumn();
}

function estFavori(PDO $pdo, int $idUser, int $idLivre): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM favori WHERE id_user = ? AND id_livre = ?");
    $stmt->execute([$idUser, $idLivre]);
    return (bool) $stmt->fetchColumn();
}

function idsFavoris(PDO $pdo, int $idUser): array
{
    try {
        $stmt = $pdo->prepare("SELECT id_livre FROM favori WHERE id_user = ?");
        $stmt->execute([$idUser]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        return [];
    }
}

function nbFavoris(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favori WHERE id_user = ?");
        $stmt->execute([$idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function alerteEnAttente(PDO $pdo, int $idUser, int $idLivre): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM alerte_stock WHERE id_user = ? AND id_livre = ? AND date_envoi IS NULL");
    $stmt->execute([$idUser, $idLivre]);
    return (bool) $stmt->fetchColumn();
}

function creerAlerte(PDO $pdo, int $idUser, int $idLivre): void
{
    $stmt = $pdo->prepare("SELECT id_alerte FROM alerte_stock WHERE id_user = ? AND id_livre = ?");
    $stmt->execute([$idUser, $idLivre]);
    if ($id = $stmt->fetchColumn()) {
        $pdo->prepare("UPDATE alerte_stock SET date_creation = NOW(), date_envoi = NULL WHERE id_alerte = ?")->execute([$id]);
    } else {
        $pdo->prepare("INSERT INTO alerte_stock (id_user, id_livre) VALUES (?, ?)")->execute([$idUser, $idLivre]);
    }
}

function notifierRetourEnStock(PDO $pdo, int $idLivre): int
{
    if (stockDuLivre($pdo, $idLivre) <= 0) {
        return 0;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT a.id_alerte, u.email, l.titre
            FROM alerte_stock a
            INNER JOIN user u ON u.id = a.id_user
            INNER JOIN livre l ON l.id_livre = a.id_livre
            WHERE a.id_livre = ? AND a.date_envoi IS NULL");
        $stmt->execute([$idLivre]);
        $alertes = $stmt->fetchAll();
    } catch (PDOException $e) {
        return 0;
    }
    $maj = $pdo->prepare("UPDATE alerte_stock SET date_envoi = NOW() WHERE id_alerte = ?");
    foreach ($alertes as $a) {
        notifierParMail($a['email'], "« {$a['titre']} » est de nouveau disponible",
            "Bonne nouvelle : le livre « {$a['titre']} » que vous attendiez est de nouveau en stock.\n\n"
            . "Les exemplaires d'occasion partent vite, ne tardez pas : " . SITE_URL . "/detail_livre.php?id=$idLivre");
        $maj->execute([$a['id_alerte']]);
    }
    if ($alertes) {
        insertLog('STOCK', "Retour en stock du livre #$idLivre : " . count($alertes) . " alerte(s) envoyée(s)");
    }
    return count($alertes);
}

function adressesDe(PDO $pdo, int $idUser): array
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM adresse_livraison WHERE id_user = ? ORDER BY par_defaut DESC, libelle");
        $stmt->execute([$idUser]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function adresseEnLigne(array $a): string
{
    return trim($a['adresse'] . ($a['complement'] ? ', ' . $a['complement'] : '') . ', ' . $a['code_postal'] . ' ' . $a['ville']);
}
