<?php

function autoriserActionPublique(string $action, int $maximum, int $fenetre): bool
{
    $fichier = @fopen(dossierPrive('limites_formulaires.json'), 'c+');
    if (!$fichier) { return false; }
    try {
        if (!flock($fichier, LOCK_EX)) { return false; }
        $texte = stream_get_contents($fichier);
        $limites = $texte === '' ? [] : json_decode($texte, true);
        if (!is_array($limites)) { return false; }
        $maintenant = time();
        foreach ($limites as $cle => $limite) {
            if (($limite['fin'] ?? 0) <= $maintenant) { unset($limites[$cle]); }
        }
        $cle = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue'));
        $limite = $limites[$cle] ?? ['fin' => $maintenant + $fenetre, 'nombre' => 0];
        if ($limite['nombre'] >= $maximum || count($limites) >= 5000 && !isset($limites[$cle])) { return false; }
        $limite['nombre']++;
        $limites[$cle] = $limite;
        $json = json_encode($limites, JSON_THROW_ON_ERROR);
        rewind($fichier);
        if (!ftruncate($fichier, 0) || fwrite($fichier, $json) !== strlen($json)) { return false; }
        return fflush($fichier);
    } finally {
        flock($fichier, LOCK_UN);
        fclose($fichier);
    }
}
