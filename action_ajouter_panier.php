<?php
require_once __DIR__ . '/sessions_lib.php';
demarrerSession();
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'livres_lib.php';

$estPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$estAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$estPost || !verifierCsrf()) {
    if ($estAjax) { http_response_code(403); exit(); }
    header("Location: panier.php?error=" . urlencode("Session expirée, merci de réessayer."));
    exit();
}

$idExemplaire = (int) ($_POST['id_exemplaire'] ?? 0);
$action = $_POST['action'] ?? 'ajouter';

$stmt = $pdo->prepare("
    SELECT e.id_exemplaire, e.id_livre, e.etat, e.prix, e.quantite, l.titre, l.couverture
    FROM exemplaire e
    INNER JOIN livre l ON l.id_livre = e.id_livre
    WHERE e.id_exemplaire = ? AND " . conditionOffreMagasin());
$stmt->execute([$idExemplaire]);
$ex = $stmt->fetch();

if (!$ex || $ex['quantite'] <= 0) {
    if ($estAjax) { http_response_code(409); exit(); }
    header("Location: panier.php?error=" . urlencode("Cet exemplaire n'est plus disponible."));
    exit();
}

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$cleUnique = 'ex_' . $ex['id_exemplaire'];
$dejaDansPanier = $_SESSION['panier'][$cleUnique]['quantite'] ?? 0;

if ($dejaDansPanier >= $ex['quantite']) {
    if ($estAjax) { http_response_code(409); exit(); }
    header("Location: panier.php?error=" . urlencode("Stock maximum atteint pour « " . $ex['titre'] . " »."));
    exit();
}

$_SESSION['panier'][$cleUnique] = [
    'id_livre'      => $ex['id_livre'],
    'id_exemplaire' => $ex['id_exemplaire'],
    'titre'         => $ex['titre'],
    'etat'          => $ex['etat'],
    'prix'          => (float) $ex['prix'],
    'quantite'      => $dejaDansPanier + 1,
    'image'         => $ex['couverture'] ?: 'default.png',
];
$_SESSION['panier_ajout'] = $ex['titre'];

if ($action === 'acheter') {
    header("Location: panier.php");
    exit();
}

if ($estAjax) {
    http_response_code(200);
    exit();
}

$retour = $_SERVER['HTTP_REFERER'] ?? '';
$hoteSite = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
if (!$retour || parse_url($retour, PHP_URL_HOST) !== $hoteSite) {
    $retour = 'index.php';
}
header("Location: " . $retour);
exit();
