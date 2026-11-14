<?php

const MOT_DE_PASSE_LONGUEUR_MIN = 8;
const MOT_DE_PASSE_OCTETS_MAX = 72;

function erreurMotDePasse(string $motDePasse): ?string
{
    if (mb_strlen($motDePasse) < MOT_DE_PASSE_LONGUEUR_MIN) {
        return 'Le mot de passe doit contenir au moins ' . MOT_DE_PASSE_LONGUEUR_MIN . ' caractères.';
    }
    if (strlen($motDePasse) > MOT_DE_PASSE_OCTETS_MAX || str_contains($motDePasse, "\0")) {
        return 'Le mot de passe est trop long ou contient un caractère non autorisé.';
    }
    return null;
}

function champMotDePasse(string $id, string $nom, string $libelle, string $autocomplete, bool $avecRegle = false, string $classeLibelle = 'form-label'): string
{
    $idSur = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
    $html = '<label for="' . $idSur . '" class="' . htmlspecialchars($classeLibelle, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '</label>';
    $html .= '<div class="champ-mot-de-passe">';
    $html .= '<input type="password" id="' . $idSur . '" name="' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . '" class="form-control" autocomplete="' . htmlspecialchars($autocomplete, ENT_QUOTES, 'UTF-8') . '" required';
    if ($avecRegle) {
        $html .= ' minlength="' . MOT_DE_PASSE_LONGUEUR_MIN . '" data-longueur-min="' . MOT_DE_PASSE_LONGUEUR_MIN . '" aria-describedby="' . $idSur . '-regle"';
    }
    $html .= '>';
    $html .= '<button type="button" class="champ-mot-de-passe__oeil" aria-controls="' . $idSur . '" aria-pressed="false" aria-label="Afficher le mot de passe" hidden><i class="bi bi-eye" aria-hidden="true"></i></button>';
    $html .= '</div>';
    if ($avecRegle) {
        $html .= '<p class="regle-mot-de-passe" id="' . $idSur . '-regle" data-etat="attente"><i class="bi bi-circle" aria-hidden="true"></i> <span>Au moins ' . MOT_DE_PASSE_LONGUEUR_MIN . ' caractères</span> <span class="regle-mot-de-passe__compteur" aria-hidden="true"></span><span class="visually-hidden regle-mot-de-passe__annonce" aria-live="polite"></span></p>';
    }
    return $html;
}
