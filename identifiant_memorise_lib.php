<?php

const IDENTIFIANT_MEMORISE_COOKIE = 'BIBLIOCCAZ_IDENTIFIANT';
const IDENTIFIANT_MEMORISE_JOURS = 90;
const ANCIEN_COOKIE_CONNEXION_PERSISTANTE = 'BIBLIOCCAZ_SOUVENIR';

function optionsCookieIdentifiant(int $expiration, string $page = 'connexion'): array
{
    $chemin = rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/') . '/';
    return ['expires' => $expiration, 'path' => $chemin . $page, 'secure' => modeProduction() || connexionHttps(), 'httponly' => true, 'samesite' => 'Lax'];
}

function identifiantMemorisable(string $identifiant): bool
{
    return $identifiant !== '' && mb_strlen($identifiant) <= 150 && !preg_match('/[\x00-\x1F\x7F]/', $identifiant);
}

function memoriserIdentifiant(string $identifiant): void
{
    if (!identifiantMemorisable($identifiant) || headers_sent()) { return; }
    setcookie(IDENTIFIANT_MEMORISE_COOKIE, $identifiant, optionsCookieIdentifiant(time() + IDENTIFIANT_MEMORISE_JOURS * 86400));
}

function oublierIdentifiantMemorise(): void
{
    if (!headers_sent()) {
        setcookie(IDENTIFIANT_MEMORISE_COOKIE, '', optionsCookieIdentifiant(time() - 3600));
    }
    unset($_COOKIE[IDENTIFIANT_MEMORISE_COOKIE]);
}

function identifiantMemorise(): string
{
    $valeur = $_COOKIE[IDENTIFIANT_MEMORISE_COOKIE] ?? '';
    return is_string($valeur) && identifiantMemorisable($valeur) ? $valeur : '';
}

function effacerAncienCookiePersistant(): void
{
    if (!isset($_COOKIE[ANCIEN_COOKIE_CONNEXION_PERSISTANTE])) { return; }
    if (!headers_sent()) {
        $chemin = rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/') . '/';
        setcookie(ANCIEN_COOKIE_CONNEXION_PERSISTANTE, '', ['expires' => time() - 3600, 'path' => $chemin, 'secure' => modeProduction() || connexionHttps(), 'httponly' => true, 'samesite' => 'Lax']);
    }
    unset($_COOKIE[ANCIEN_COOKIE_CONNEXION_PERSISTANTE]);
}
