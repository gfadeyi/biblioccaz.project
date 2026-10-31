<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/panier_minute_lib.php';
$ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'ajouter';
$retour = 'panier.php';
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['articles' => array_sum(array_column($_SESSION['panier'] ?? [], 'quantite')), 'expiration' => $_SESSION['panier_expiration'] ?? null, 'maintenant' => time(), 'message' => $_SESSION['panier_message'] ?? ''], JSON_THROW_ON_ERROR);
    exit();
}
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
        if ($ajax) { http_response_code(403); }
        throw new DomainException('Session expirée. Actualisez la page avant de recommencer.');
    }
    $id = filter_var($_POST['id_exemplaire'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    if (!$id && is_string($_POST['key'] ?? null) && preg_match('/\Aex_([1-9][0-9]*)\z/', $_POST['key'], $correspondance)) { $id = (int) $correspondance[1]; }
    if (in_array($action, ['ajouter', 'acheter'], true) && isset($_POST['condition'])) { $id = filter_var($_POST['condition'], FILTER_VALIDATE_INT) ?: $id; }
    $cible = filter_var($_POST['nouvel_exemplaire'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $liste = [];
    $totalSerie = 0;
    if ($action === 'serie') {
        require_once __DIR__ . '/series_lib.php';
        $idSerie = filter_var($_POST['id_serie'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $selection = selectionSerieDisponible($pdo, $idSerie);
        $liste = $selection['offres'];
        $totalSerie = count($selection['tomes']);
        if (!$liste) { throw new DomainException('Aucun tome de cette série n’est actuellement disponible en magasin.'); }
    }
    $resultat = modifierPanierMinute($pdo, $action === 'acheter' ? 'ajouter' : $action, $id, $cible, $liste);
    $resultat['message'] = $action === 'serie'
        ? $resultat['ajoutes'] . ' tome(s) ajouté(s) sur ' . $totalSerie . '. ' . ($totalSerie - $resultat['ajoutes']) . ' tome(s) indisponible(s) ou non ajouté(s).'
        : (in_array($action, ['supprimer', 'moins'], true) ? 'Panier mis à jour.' : 'Panier mis à jour. Vos articles sont réservés pendant 15 minutes.');
    $_SESSION['panier_message'] = $resultat['message'];
    if (in_array($action, ['ajouter', 'acheter', 'plus'], true)) { $_SESSION['panier_ajout'] = $_SESSION['panier']['ex_' . $id]['titre'] ?? ''; }
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, ...$resultat], JSON_THROW_ON_ERROR);
        exit();
    }
    if ($action === 'ajouter') {
        $referent = $_SERVER['HTTP_REFERER'] ?? '';
        if (parse_url($referent, PHP_URL_HOST) === parse_url(SITE_URL, PHP_URL_HOST) && in_array(parse_url($referent, PHP_URL_SCHEME), ['http', 'https'], true)) { $retour = $referent; }
    }
} catch (DomainException $erreur) {
    if ($ajax) {
        if (http_response_code() !== 403) { http_response_code(409); }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $erreur->getMessage()], JSON_THROW_ON_ERROR);
        exit();
    }
    $_SESSION['panier_message'] = $erreur->getMessage();
} catch (Throwable $erreur) {
    error_log('Panier minute : ' . $erreur->getMessage());
    if ($ajax) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Le panier est temporairement indisponible. Réessayez dans un instant.']);
        exit();
    }
    $_SESSION['panier_message'] = 'Le panier n’a pas pu être modifié. Réessayez dans un instant.';
}
header('Location: ' . $retour);
exit();
