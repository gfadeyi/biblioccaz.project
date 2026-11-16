<?php

function repondreErreurTechnique(int $statut = 500): void
{
    if (PHP_SAPI === 'cli') { return; }
    while (ob_get_level() > 0) { if (!@ob_end_clean()) { break; } }
    if (!headers_sent()) {
        header_remove('Location');
        http_response_code($statut);
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
    $json = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
    foreach (headers_list() as $entete) {
        if (stripos($entete, 'Content-Type: application/json') === 0) { $json = true; }
    }
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erreur' => 'indisponible', 'message' => 'Le service est momentanément indisponible. Merci de réessayer.']);
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    $racine = htmlspecialchars(rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/', ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Service indisponible — BIBLIOccaz</title><link rel="stylesheet" href="' . $racine . 'style.css"></head><body><main class="page-formulaire"><section class="carte-formulaire"><h1>Une pause imprévue</h1><p>Le service est momentanément indisponible. Merci de réessayer dans quelques minutes.</p><a class="bouton bouton-principal" href="' . $racine . '">Retour à l’accueil</a></section></main></body></html>';
}

function installerGestionErreurs(): void
{
    if (!modeProduction() || PHP_SAPI === 'cli') { return; }
    set_exception_handler(function (Throwable $erreur): void {
        error_log('Erreur non interceptée : ' . get_class($erreur) . ' dans ' . basename($erreur->getFile()) . ':' . $erreur->getLine());
        repondreErreurTechnique();
    });
    register_shutdown_function(function (): void {
        $erreur = error_get_last();
        if ($erreur && in_array($erreur['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            repondreErreurTechnique();
        }
    });
}
