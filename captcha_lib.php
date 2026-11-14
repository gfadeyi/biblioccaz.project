<?php

const CAPTCHA_VALIDITE_SECONDES = 1800;
const CAPTCHA_ECHECS_CONNEXION_MAX = 3;
const CAPTCHA_MESSAGE_REQUIS = 'Veuillez compléter le puzzle de sécurité.';

function captchaRecent(): bool
{
    $reussiLe = (int) ($_SESSION['captcha_reussi_le'] ?? 0);
    return $reussiLe > 0 && time() - $reussiLe < CAPTCHA_VALIDITE_SECONDES;
}

function enregistrerCaptchaReussi(): void
{
    $_SESSION['captcha_reussi_le'] = time();
    $_SESSION['captcha_echecs_connexion'] = 0;
    unset($_SESSION['captcha']);
}

function compterEchecConnexionCaptcha(): void
{
    $_SESSION['captcha_echecs_connexion'] = (int) ($_SESSION['captcha_echecs_connexion'] ?? 0) + 1;
    if ($_SESSION['captcha_echecs_connexion'] >= CAPTCHA_ECHECS_CONNEXION_MAX) {
        unset($_SESSION['captcha_reussi_le']);
        $_SESSION['captcha_echecs_connexion'] = 0;
    }
}
