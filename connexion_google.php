<?php
require_once 'config.php';
require_once 'google_lib.php';

header('Cache-Control: no-store');

if (!connexionGoogleActive()) {
    http_response_code(404);
    exit();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['state'])) {
    unset($_SESSION['liaison_google']);
    header('Location: ' . urlConnexionGoogle());
    exit();
}

$demande = $_SESSION['google_oauth'] ?? [];
unset($_SESSION['google_oauth']);
if (!is_string($_GET['state']) || !demandeGoogleValide($demande, $_GET['state'])) {
    header('Location: login.php?error=google_expire');
    exit();
}
if (!is_string($_GET['code'] ?? null) || $_GET['code'] === '') {
    header('Location: login.php?error=google_annule');
    exit();
}

try {
    $identite = identiteGoogle(echangerCodeGoogle($_GET['code'], $demande['verificateur']), $demande['nonce']);

    $requete = $pdo->prepare('SELECT * FROM user WHERE google_sub = ?');
    $requete->execute([$identite['sub']]);
    $user = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $requete = $pdo->prepare('SELECT * FROM user WHERE email = ?');
        $requete->execute([$identite['email']]);
        $existant = $requete->fetch(PDO::FETCH_ASSOC);
        if ($existant) {
            $refus = refusConnexionGoogle(array_merge($existant, ['email_verifie' => 1]));
            if ($refus !== '') {
                header("Location: login.php?error=$refus");
                exit();
            }
            if ($existant['google_sub'] !== null) {
                header('Location: login.php?error=google_autre_compte');
                exit();
            }
            $_SESSION['liaison_google'] = ['id_user' => (int) $existant['id'], 'sub' => $identite['sub'], 'email' => $identite['email'], 'essais' => 0, 'cree_le' => time()];
            header('Location: liaison_google.php');
            exit();
        }
        $user = creerCompteGoogle($pdo, $identite);
    }

    $refus = refusConnexionGoogle($user);
    if ($refus !== '') {
        header("Location: login.php?error=$refus");
        exit();
    }
    header('Location: ' . ouvrirSessionMembre($pdo, $user, 'Connexion réussie avec Google'));
    exit();
} catch (DomainException $e) {
    header('Location: login.php?error=google_email');
    exit();
} catch (Throwable $e) {
    insertLog('ERREUR', 'Connexion Google impossible : ' . $e->getMessage());
    header('Location: login.php?error=technique');
    exit();
}
