<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BIBLIOCCAZ_ADMIN');
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../avis_lib.php';

const ROLES_EQUIPE = ['admin', 'gestionnaire', 'moderateur'];

const DROITS = [
    'equipe'     => ['admin', 'gestionnaire', 'moderateur'],
    'moderation' => ['admin', 'gestionnaire', 'moderateur'],
    'catalogue'  => ['admin', 'gestionnaire'],
    'evenements' => ['admin', 'gestionnaire'],
    'admin'      => ['admin'],
];

const ROLES_LIBELLES = [
    'admin'        => 'Administrateur',
    'gestionnaire' => 'Gestionnaire',
    'moderateur'   => 'Modérateur',
    'auteur'       => 'Auteur',
    'client'       => 'Client',
];

const ROLES_ATTRIBUABLES = ['gestionnaire', 'moderateur', 'auteur', 'client'];

function estEquipe(): bool
{
    return in_array($_SESSION['role'] ?? '', ROLES_EQUIPE, true);
}

function aLeDroit(string $droit): bool
{
    return in_array($_SESSION['role'] ?? '', DROITS[$droit] ?? [], true);
}

function exigerDroit(string $droit, bool $json = false): void
{
    if (aLeDroit($droit)) {
        return;
    }
    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['erreur' => 'Accès refusé']);
        exit();
    }
    if (!estEquipe()) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SESSION['admin_retour'] = basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
        }
        header('Location: login.php');
        exit();
    }
    global $pdo;
    http_response_code(403);
    $titrePage = 'Accès refusé';
    $pageActive = '';
    include __DIR__ . '/_haut.php';
    echo '<div class="alert alert-warning"><i class="bi bi-lock me-2"></i>Votre rôle ne donne pas accès à cette page.</div>';
    include __DIR__ . '/_bas.php';
    exit();
}

function flash(string $type, string $texte): void
{
    $_SESSION['flash_admin'] = [$type, $texte];
}

function rediriger(string $url, ?string $type = null, ?string $texte = null): void
{
    if ($type !== null) {
        flash($type, $texte);
    }
    header("Location: $url");
    exit();
}

function exigerPost(string $retour): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
        rediriger($retour, 'danger', 'Session expirée ou requête invalide, merci de réessayer.');
    }
}

function champCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . jetonCsrf() . '">';
}

function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES);
}

function imageLivre(?string $fichier): string
{
    return '../img/' . rawurlencode($fichier ?: 'default.png');
}

function enregistrerCouverture(string $champ = 'image'): array
{
    if (empty($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }
    $f = $_FILES[$champ];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, "L'envoi de l'image a échoué."];
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return [null, "L'image est trop lourde (2 Mo maximum)."];
    }
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($types[$info[2]])) {
        return [null, "Format d'image non supporté (JPG, PNG ou WEBP)."];
    }
    $nom = time() . '_' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../img/' . $nom)) {
        return [null, "Impossible d'enregistrer l'image (droits du dossier img/ ?)."];
    }
    return [$nom, ''];
}

function compteursEquipe(PDO $pdo): array
{
    $c = ['livres' => 0, 'avis' => 0, 'candidatures' => 0, 'avis_a_valider' => 0];
    try {
        $c['livres'] = (int) $pdo->query("SELECT COUNT(*) FROM livre WHERE is_valide = 0")->fetchColumn();
        $c['avis'] = (int) $pdo->query("SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0")->fetchColumn();
        $c['candidatures'] = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();
        $c['avis_a_valider'] = (int) $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'visible' AND date_validation IS NULL")->fetchColumn();
    } catch (PDOException $e) {
    }
    return $c;
}
