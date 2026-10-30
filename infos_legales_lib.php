<?php

function valeurLegale(string $cle): string
{
    $infos = defined('INFOS_LEGALES') && is_array(INFOS_LEGALES) ? INFOS_LEGALES : [];
    return is_string($infos[$cle] ?? null) ? trim($infos[$cle]) : '';
}

function infoLegale(string $cle, string $libelle): string
{
    $valeur = valeurLegale($cle);
    if ($valeur === '') {
        return '<mark class="a-completer">[À compléter : ' . htmlspecialchars($libelle) . ']</mark>';
    }
    return nl2br(htmlspecialchars($valeur));
}

function emailContactLegal(): string
{
    $email = valeurLegale('editeur_email');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return infoLegale('editeur_email', 'adresse email de contact');
    }
    $email = htmlspecialchars($email);
    return '<a href="mailto:' . $email . '">' . $email . '</a>';
}
