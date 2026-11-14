<?php
require_once __DIR__ . '/sessions_lib.php';

const CAPTCHA_VALIDITE_SECONDES = 1800;
const CAPTCHA_ECHECS_CONNEXION_MAX = 3;
const CAPTCHA_MESSAGE_REQUIS = 'Veuillez compléter le puzzle de sécurité.';
const CAPTCHA_COOKIE = 'BIBLIOCCAZ_CAPTCHA';

function cleSignatureCaptcha(): string
{
    static $cle = null;
    if ($cle !== null) {
        return $cle;
    }
    $fichier = dossierPrive('cle_captcha.txt');
    $contenu = is_file($fichier) ? trim((string) @file_get_contents($fichier)) : '';
    if (strlen($contenu) < 64) {
        $contenu = bin2hex(random_bytes(32));
        if (@file_put_contents($fichier, $contenu, LOCK_EX) === false) {
            $contenu = '';
        } else {
            @chmod($fichier, 0600);
        }
    }
    return $cle = $contenu;
}

function signatureCaptcha(int $horodatage): string
{
    return hash_hmac('sha256', $horodatage . '|' . substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300), cleSignatureCaptcha());
}

function ecrireCookieCaptcha(string $valeur, int $expiration): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    $parametres = session_get_cookie_params();
    setcookie(CAPTCHA_COOKIE, $valeur, ['expires' => $expiration, 'path' => '/', 'secure' => (bool) $parametres['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    if ($valeur === '') {
        unset($_COOKIE[CAPTCHA_COOKIE]);
    } else {
        $_COOKIE[CAPTCHA_COOKIE] = $valeur;
    }
}

function horodatageCookieCaptcha(): int
{
    $valeur = $_COOKIE[CAPTCHA_COOKIE] ?? '';
    if (!is_string($valeur) || !preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $valeur, $morceaux) || cleSignatureCaptcha() === '') {
        return 0;
    }
    if (!hash_equals(signatureCaptcha((int) $morceaux[1]), $morceaux[2])) {
        return 0;
    }
    return echecsCookieCaptcha($valeur, 0) >= CAPTCHA_ECHECS_CONNEXION_MAX ? 0 : (int) $morceaux[1];
}

function echecsCookieCaptcha(string $valeur, int $ajout): int
{
    $fichier = @fopen(dossierPrive('captcha_echecs.json'), 'c+');
    if (!$fichier) {
        return 0;
    }
    try {
        if (!flock($fichier, LOCK_EX)) {
            return 0;
        }
        $echecs = json_decode((string) stream_get_contents($fichier), true);
        $echecs = is_array($echecs) ? $echecs : [];
        $maintenant = time();
        foreach ($echecs as $cle => $echec) {
            if (($echec['fin'] ?? 0) <= $maintenant) {
                unset($echecs[$cle]);
            }
        }
        $cle = hash('sha256', $valeur);
        $nombre = (int) ($echecs[$cle]['nombre'] ?? 0);
        if ($ajout > 0 && count($echecs) < 5000) {
            $nombre += $ajout;
            $echecs[$cle] = ['nombre' => $nombre, 'fin' => $maintenant + CAPTCHA_VALIDITE_SECONDES];
            rewind($fichier);
            ftruncate($fichier, 0);
            fwrite($fichier, json_encode($echecs));
            fflush($fichier);
        }
        return $nombre;
    } finally {
        flock($fichier, LOCK_UN);
        fclose($fichier);
    }
}

function captchaRecent(): bool
{
    $reussiLe = max((int) ($_SESSION['captcha_reussi_le'] ?? 0), horodatageCookieCaptcha());
    $valide = $reussiLe > 0 && $reussiLe <= time() && time() - $reussiLe < CAPTCHA_VALIDITE_SECONDES;
    if ($valide && session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['captcha_reussi_le'] = $reussiLe;
    }
    return $valide;
}

function enregistrerCaptchaReussi(): void
{
    $maintenant = time();
    $_SESSION['captcha_reussi_le'] = $maintenant;
    $_SESSION['captcha_echecs_connexion'] = 0;
    unset($_SESSION['captcha']);
    if (cleSignatureCaptcha() !== '') {
        ecrireCookieCaptcha($maintenant . '.' . signatureCaptcha($maintenant), $maintenant + CAPTCHA_VALIDITE_SECONDES);
    }
}

function oublierCaptcha(): void
{
    unset($_SESSION['captcha_reussi_le']);
    if (isset($_COOKIE[CAPTCHA_COOKIE])) {
        ecrireCookieCaptcha('', time() - 3600);
    }
}

function compterEchecConnexionCaptcha(): void
{
    $_SESSION['captcha_echecs_connexion'] = (int) ($_SESSION['captcha_echecs_connexion'] ?? 0) + 1;
    $cookie = $_COOKIE[CAPTCHA_COOKIE] ?? '';
    $echecsCookie = is_string($cookie) && $cookie !== '' ? echecsCookieCaptcha($cookie, 1) : 0;
    if ($_SESSION['captcha_echecs_connexion'] >= CAPTCHA_ECHECS_CONNEXION_MAX || $echecsCookie >= CAPTCHA_ECHECS_CONNEXION_MAX) {
        oublierCaptcha();
        $_SESSION['captcha_echecs_connexion'] = 0;
    }
}
