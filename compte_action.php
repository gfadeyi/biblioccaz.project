<?php
require_once 'config.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';
require_once 'recommandations_lib.php';
require_once 'series_lib.php';
require_once 'alertes_serie_lib.php';

if (($_POST['action'] ?? '') === 'favori_basculer') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        $retourPage = retourConnexionValide((string) ($_POST['retour_page'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifierCsrf()) {
            memoriserFavoriApresConnexion((int) ($_POST['id_livre'] ?? 0), $retourPage);
        }
        http_response_code(401);
        echo json_encode(['erreur' => 'connexion', 'connexion' => lienConnexion($retourPage ?? './')]);
        exit();
    }
    if (!verifierCsrf()) {
        http_response_code(403);
        echo json_encode(['erreur' => 'csrf']);
        exit();
    }
    $idUser = (int) $_SESSION['user_id'];
    $idLivre = (int) ($_POST['id_livre'] ?? 0);
    $stmt = $pdo->prepare("SELECT 1 FROM livre WHERE id_livre = ? AND is_valide = 1");
    $stmt->execute([$idLivre]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['erreur' => 'livre']);
        exit();
    }
    if (estFavori($pdo, $idUser, $idLivre)) {
        $pdo->prepare("DELETE FROM favori WHERE id_user = ? AND id_livre = ?")->execute([$idUser, $idLivre]);
        $favori = false;
    } else {
        $pdo->prepare("INSERT IGNORE INTO favori (id_user, id_livre) VALUES (?, ?)")->execute([$idUser, $idLivre]);
        $favori = true;
    }
    oublierRecommandations();
    echo json_encode(['favori' => $favori, 'nb' => nbFavoris($pdo, $idUser)]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}

$idUser = (int) $_SESSION['user_id'];
$idLivre = (int) ($_POST['id_livre'] ?? 0);
$action = $_POST['action'] ?? '';
$retour = match ($_POST['retour'] ?? '') {
    'wishlist' => 'wishlist',
    'alertes'  => 'alertes',
    default    => "livre?id=$idLivre",
};

function finir(string $retour, string $type, string $message): void
{
    $_SESSION['flash_compte'] = [$type, $message];
    header("Location: $retour");
    exit();
}

if (!verifierCsrf()) {
    finir($retour, 'danger', "Session expirée, merci de réessayer.");
}

$stmt = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ? AND is_valide = 1");
$stmt->execute([$idLivre]);
$titre = $stmt->fetchColumn();
if ($titre === false) {
    finir($retour, 'warning', "Ce livre n'existe pas.");
}

switch ($action) {
    case 'favori_ajouter':
        $pdo->prepare("INSERT IGNORE INTO favori (id_user, id_livre) VALUES (?, ?)")->execute([$idUser, $idLivre]);
        oublierRecommandations();
        finir($retour, 'success', "« $titre » est dans vos favoris.");

    case 'favori_retirer':
        $pdo->prepare("DELETE FROM favori WHERE id_user = ? AND id_livre = ?")->execute([$idUser, $idLivre]);
        oublierRecommandations();
        finir($retour, 'success', "« $titre » a été retiré de vos favoris.");

    case 'alerte_ajouter':
        if (stockDuLivre($pdo, $idLivre) > 0) {
            finir($retour, 'info', "« $titre » est déjà disponible !");
        }
        creerAlerte($pdo, $idUser, $idLivre);
        finir($retour, 'success', "C'est noté : vous recevrez un mail dès que « $titre » sera de nouveau en stock.");

    case 'serie_suivre':
    case 'serie_ne_plus_suivre':
        $serie = serieDuLivre($pdo, $idLivre);
        if (!$serie) {
            finir($retour, 'warning', "Ce livre ne fait partie d'aucune série.");
        }
        if ($action === 'serie_ne_plus_suivre') {
            desabonnerAlerteSerie($pdo, $idUser, (int) $serie['id_serie']);
            finir($retour, 'success', "Vous ne recevrez plus d'alerte pour la série « {$serie['nom']} ».");
        }
        $resultat = abonnerAlerteSerie($pdo, $idUser, (int) $serie['id_serie']);
        if ($resultat === 'limite') {
            finir($retour, 'warning', "Vous suivez déjà " . ALERTES_SERIE_MAX . " séries : retirez-en une dans « Mes alertes » avant d'en ajouter une autre.");
        }
        if ($resultat !== 'ok') {
            finir($retour, 'warning', "Les alertes de série ne sont pas encore disponibles.");
        }
        finir($retour, 'success', "C'est noté : vous serez prévenu (notification et mail) dès qu'un tome de « {$serie['nom']} » arrive ou revient en stock.");

    case 'alerte_retirer':
        $pdo->prepare("DELETE FROM alerte_stock WHERE id_user = ? AND id_livre = ?")->execute([$idUser, $idLivre]);
        finir($retour, 'success', "Alerte supprimée pour « $titre ».");
}

header("Location: $retour");
exit();
