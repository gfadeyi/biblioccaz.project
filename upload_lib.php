<?php

function enregistrerCouverture(string $champ = 'image', string $sousDossier = ''): array
{
    if (empty($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }
    return enregistrerImageEnvoyee($_FILES[$champ], $sousDossier);
}

function enregistrerImageEnvoyee(array $f, string $sousDossier = ''): array
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, "L'envoi de l'image a échoué."];
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return [null, "L'image est trop lourde (2 Mo maximum)."];
    }
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($types[$info[2]])) {
        return [null, "Format d'image non supporté (JPG, PNG ou WEBP)."];
    }
    $nom = time() . '_' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
    $dossier = __DIR__ . '/img/' . $sousDossier;
    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }
    if (!move_uploaded_file($f['tmp_name'], $dossier . $nom)) {
        return [null, "Impossible d'enregistrer l'image (droits du dossier img/ ?)."];
    }
    return [$nom, ''];
}

function fichiersEnvoyes(string $champ): array
{
    if (empty($_FILES[$champ]) || !is_array($_FILES[$champ]['name'])) {
        return [];
    }
    $fichiers = [];
    foreach ($_FILES[$champ]['name'] as $index => $nom) {
        if ($_FILES[$champ]['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $fichiers[] = [
            'name'     => $nom,
            'tmp_name' => $_FILES[$champ]['tmp_name'][$index],
            'error'    => $_FILES[$champ]['error'][$index],
            'size'     => $_FILES[$champ]['size'][$index],
        ];
    }
    return $fichiers;
}
