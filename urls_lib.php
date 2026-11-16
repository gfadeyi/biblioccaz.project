<?php

const URLS_ALIAS = [
    'index.php' => '',
    'login.php' => 'connexion',
    'profil.php' => 'mon-compte',
    'detail_livre.php' => 'livre',
    'qui_sommes_nous.php' => 'qui-sommes-nous',
    'nouveauté.php' => 'nouveautes',
    'dernière_chance.php' => 'derniere-chance',
];
const URLS_AVEC_EXTENSION = ['connexion_google.php', 'liaison_google.php'];

function cheminPropre(string $page): string
{
    if (isset(URLS_ALIAS[$page])) {
        return URLS_ALIAS[$page] === '' ? './' : URLS_ALIAS[$page];
    }
    if (in_array($page, URLS_AVEC_EXTENSION, true) || !str_ends_with($page, '.php')) {
        return $page;
    }
    return substr($page, 0, -4);
}

function urlPropre(string $lien): string
{
    if (!preg_match('~\A([^?#]*)([?#].*)?\z~s', $lien, $morceaux)) {
        return $lien;
    }
    $chemin = $morceaux[1];
    $suite = $morceaux[2] ?? '';
    $dossier = '';
    if (($position = strrpos($chemin, '/')) !== false) {
        $dossier = substr($chemin, 0, $position + 1);
        $chemin = substr($chemin, $position + 1);
    }
    $page = rawurldecode($chemin);
    if (!str_ends_with($page, '.php')) {
        return $lien;
    }
    $propre = cheminPropre($page);
    if ($propre === './' && $dossier !== '') {
        $propre = '';
    }
    $propre = $propre === $page ? $chemin : str_replace('%2F', '/', rawurlencode($propre === './' ? '' : $propre));
    $resultat = $dossier . $propre;
    return ($resultat === '' ? './' : $resultat) . $suite;
}

function pageDepuisChemin(string $chemin): ?string
{
    $chemin = rawurldecode($chemin);
    if ($chemin === '' || $chemin === './') {
        return 'index.php';
    }
    if (str_ends_with($chemin, '.php')) {
        return $chemin;
    }
    $page = array_search($chemin, URLS_ALIAS, true);
    if ($page !== false) {
        return $page;
    }
    return preg_match('/\A[\p{Ll}0-9_]+\z/u', $chemin) ? $chemin . '.php' : null;
}
