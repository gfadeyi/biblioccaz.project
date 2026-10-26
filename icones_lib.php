<?php
function iconeDessin(string $nom, string $classe = ''): string
{
    static $version = null;
    $version ??= (string) @filemtime(__DIR__ . '/img/icones.svg');
    return '<svg class="icone-dessin ' . htmlspecialchars($classe) . '" aria-hidden="true" focusable="false"><use href="img/icones.svg?v='
        . $version . '#d-' . htmlspecialchars($nom) . '"></use></svg>';
}
