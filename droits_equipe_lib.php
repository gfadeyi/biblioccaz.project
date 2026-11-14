<?php
require_once __DIR__ . '/parametres_site_lib.php';

const DROITS_EQUIPE_DEFAUT = [
    'equipe' => ['admin', 'gestionnaire', 'moderateur'],
    'catalogue' => ['admin', 'gestionnaire'],
    'dons' => ['admin', 'gestionnaire'],
    'dons_lecture' => ['admin', 'gestionnaire', 'moderateur'],
    'moderation' => ['admin', 'moderateur'],
    'ventes' => ['admin', 'gestionnaire', 'moderateur'],
    'evenements' => ['admin', 'gestionnaire'],
    'admin' => ['admin'],
    'candidatures_auteur' => ['admin'],
];

const FAMILLES_DROITS_CONFIGURABLES = [
    'catalogue' => 'Catalogue, stock, fiches proposées, commandes, retours et emprunts',
    'dons' => 'Dons : gestion complète (validation, correction, annulation, réservations et litiges de retrait)',
    'dons_lecture' => 'Dons : consultation et signalement à l’équipe',
    'moderation' => 'Modération : avis, annonces entre particuliers, conversations et suspensions',
    'ventes' => 'Ventes entre lecteurs et litiges',
    'evenements' => 'Événements et présences',
    'candidatures_auteur' => 'Candidatures et pages auteur',
];
const DROITS_EQUIPE_INCLUS = ['dons_lecture' => ['dons']];
const DROITS_EQUIPE_LECTURE = ['dons' => 'dons_lecture'];
const ROLES_DROITS_CONFIGURABLES = ['gestionnaire', 'moderateur'];

function droitsEquipeParDefaut(array $droits): array
{
    $configuration = [];
    foreach (FAMILLES_DROITS_CONFIGURABLES as $famille => $libelle) {
        foreach (ROLES_DROITS_CONFIGURABLES as $role) { $configuration[$famille][$role] = in_array($role, $droits[$famille] ?? [], true); }
    }
    return $configuration;
}

function validerDroitsEquipe(array $saisie): array
{
    if (array_diff_key($saisie, FAMILLES_DROITS_CONFIGURABLES)) { throw new DomainException('Famille de droits inconnue.'); }
    $droits = [];
    foreach (FAMILLES_DROITS_CONFIGURABLES as $famille => $libelle) {
        $valeurs = $saisie[$famille] ?? [];
        if (!is_array($valeurs) || array_diff_key($valeurs, array_flip(ROLES_DROITS_CONFIGURABLES))) { throw new DomainException('Rôle non configurable.'); }
        foreach (ROLES_DROITS_CONFIGURABLES as $role) {
            if (isset($valeurs[$role]) && !in_array($valeurs[$role], [true, false, '1', '0'], true)) { throw new DomainException('Valeur de droit invalide.'); }
            $droits[$famille][$role] = in_array($valeurs[$role] ?? false, [true, '1'], true);
        }
    }
    return $droits;
}

function completerDroitsEquipe(array $valeurs, array $defaut): array
{
    return array_replace(droitsEquipeParDefaut($defaut), array_intersect_key($valeurs, FAMILLES_DROITS_CONFIGURABLES));
}

function configurationDroitsEquipe(PDO $pdo, array $defaut): array
{
    $etat = chargerParametreSite($pdo, 'droits_equipe', droitsEquipeParDefaut($defaut));
    $etat['valeurs'] = completerDroitsEquipe($etat['valeurs'], $defaut);
    return $etat;
}

function rolePossedeDroit(PDO $pdo, string $role, string $droit, array $defaut): bool
{
    if ($role === 'admin') { return array_key_exists($droit, $defaut); }
    if (!in_array($role, ROLES_DROITS_CONFIGURABLES, true)) { return false; }
    if ($droit === 'equipe') { return true; }
    if (!isset(FAMILLES_DROITS_CONFIGURABLES[$droit])) { return false; }
    static $configuration = null;
    if ($configuration === null) {
        $configuration = validerDroitsEquipe(configurationDroitsEquipe($pdo, $defaut)['valeurs']);
    }
    if ($configuration[$droit][$role]) { return true; }
    foreach (DROITS_EQUIPE_INCLUS[$droit] ?? [] as $droitSuperieur) {
        if ($configuration[$droitSuperieur][$role]) { return true; }
    }
    return false;
}

function droitLectureEquipe(string $droit): string
{
    return DROITS_EQUIPE_LECTURE[$droit] ?? $droit;
}
