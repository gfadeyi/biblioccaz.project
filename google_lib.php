<?php
require_once __DIR__ . '/connexion_lib.php';

const GOOGLE_URL_AUTORISATION = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_URL_JETON = 'https://oauth2.googleapis.com/token';
const GOOGLE_EMETTEURS = ['https://accounts.google.com', 'accounts.google.com'];
const GOOGLE_DUREE_DEMANDE = 600;
const GOOGLE_ROLES_AUTORISES = ['client', 'auteur'];
const GOOGLE_ESSAIS_LIAISON = 5;

function connexionGoogleActive(): bool
{
    return function_exists('curl_init') && defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET')
        && is_string(GOOGLE_CLIENT_ID) && is_string(GOOGLE_CLIENT_SECRET)
        && GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
}

function urlRetourGoogle(): string
{
    return rtrim(SITE_URL, '/') . '/connexion_google.php';
}

function base64UrlEncoder(string $donnees): string
{
    return rtrim(strtr(base64_encode($donnees), '+/', '-_'), '=');
}

function base64UrlDecoder(string $donnees): string
{
    $decode = base64_decode(strtr($donnees, '-_', '+/') . str_repeat('=', (4 - strlen($donnees) % 4) % 4), true);
    if ($decode === false) {
        throw new RuntimeException('Jeton Google illisible.');
    }
    return $decode;
}

function urlConnexionGoogle(): string
{
    $verificateur = base64UrlEncoder(random_bytes(48));
    $_SESSION['google_oauth'] = [
        'state' => base64UrlEncoder(random_bytes(32)),
        'nonce' => base64UrlEncoder(random_bytes(32)),
        'verificateur' => $verificateur,
        'cree_le' => time(),
    ];
    return GOOGLE_URL_AUTORISATION . '?' . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => urlRetourGoogle(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $_SESSION['google_oauth']['state'],
        'nonce' => $_SESSION['google_oauth']['nonce'],
        'code_challenge' => base64UrlEncoder(hash('sha256', $verificateur, true)),
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
    ], '', '&', PHP_QUERY_RFC3986);
}

function demandeGoogleValide(array $demande, string $state): bool
{
    return isset($demande['state'], $demande['cree_le'])
        && time() - (int) $demande['cree_le'] <= GOOGLE_DUREE_DEMANDE
        && hash_equals($demande['state'], $state);
}

function echangerCodeGoogle(string $code, string $verificateur): string
{
    $requete = curl_init(GOOGLE_URL_JETON);
    curl_setopt_array($requete, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => urlRetourGoogle(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $verificateur,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $reponse = curl_exec($requete);
    $statut = curl_getinfo($requete, CURLINFO_RESPONSE_CODE);
    $jetons = is_string($reponse) ? json_decode($reponse, true) : null;
    if ($statut !== 200 || !is_array($jetons) || !is_string($jetons['id_token'] ?? null)) {
        throw new RuntimeException('Échange du code Google refusé (HTTP ' . $statut . ').');
    }
    return $jetons['id_token'];
}

function identiteGoogle(string $jetonIdentite, string $nonce): array
{
    $parties = explode('.', $jetonIdentite);
    if (count($parties) !== 3) {
        throw new RuntimeException('Jeton Google mal formé.');
    }
    $revendications = json_decode(base64UrlDecoder($parties[1]), true);
    if (!is_array($revendications)
        || !in_array($revendications['iss'] ?? '', GOOGLE_EMETTEURS, true)
        || !hash_equals(GOOGLE_CLIENT_ID, (string) ($revendications['aud'] ?? ''))
        || (int) ($revendications['exp'] ?? 0) < time() - 60
        || (int) ($revendications['iat'] ?? 0) > time() + 300
        || !hash_equals($nonce, (string) ($revendications['nonce'] ?? ''))
        || !preg_match('/^[0-9]{1,64}$/', (string) ($revendications['sub'] ?? ''))) {
        throw new RuntimeException('Jeton Google invalide.');
    }
    $email = mb_strtolower(trim((string) ($revendications['email'] ?? '')));
    $emailVerifie = ($revendications['email_verified'] ?? false) === true || ($revendications['email_verified'] ?? '') === 'true';
    if (!$emailVerifie || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        throw new DomainException('email_non_verifie');
    }
    return [
        'sub' => (string) $revendications['sub'],
        'email' => $email,
        'prenom' => mb_substr(trim((string) ($revendications['given_name'] ?? '')), 0, 100),
        'nom' => mb_substr(trim((string) ($revendications['family_name'] ?? '')), 0, 100),
    ];
}

function pseudoLibreGoogle(PDO $pdo, array $identite): string
{
    $base = $identite['prenom'] !== '' ? $identite['prenom'] : strstr($identite['email'], '@', true);
    $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '.', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: ''), '.'));
    $base = mb_substr($base !== '' ? $base : 'lecteur', 0, 40);
    $verification = $pdo->prepare('SELECT 1 FROM user WHERE pseudo = ?');
    for ($essai = 0; $essai < 20; $essai++) {
        $pseudo = $essai === 0 ? $base : $base . '.' . random_int(100, 99999);
        $verification->execute([$pseudo]);
        if (!$verification->fetchColumn()) {
            return $pseudo;
        }
    }
    throw new RuntimeException('Aucun pseudo disponible.');
}

function creerCompteGoogle(PDO $pdo, array $identite): array
{
    $pseudo = pseudoLibreGoogle($pdo, $identite);
    $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, google_sub) VALUES (?, ?, ?, ?, ?, 'client', 'actif', 1, ?)")
        ->execute([$pseudo, $identite['email'], password_hash(base64UrlEncoder(random_bytes(32)), PASSWORD_DEFAULT), $identite['nom'], $identite['prenom'], $identite['sub']]);
    $requete = $pdo->prepare('SELECT * FROM user WHERE id = ?');
    $requete->execute([(int) $pdo->lastInsertId()]);
    insertLog('INSCRIPTION', "Compte créé avec Google : @$pseudo");
    return $requete->fetch(PDO::FETCH_ASSOC);
}

function refusConnexionGoogle(array $user): string
{
    if (!in_array($user['role'], GOOGLE_ROLES_AUTORISES, true)) {
        return 'google_equipe';
    }
    return refusConnexionMembre($user);
}
