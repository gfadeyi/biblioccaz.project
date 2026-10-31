<?php
require_once __DIR__ . '/parametres_site_lib.php';

const FAMILLES_DROITS_CONFIGURABLES = [
    'catalogue' => 'Catalogue, stock, commandes et emprunts',
    'moderation' => 'Modération des contenus, messages et ventes',
    'evenements' => 'Événements et présences',
    'candidatures_auteur' => 'Candidatures et pages auteur',
];
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

function rolePossedeDroit(PDO $pdo, string $role, string $droit, array $defaut): bool
{
    if ($role === 'admin') { return array_key_exists($droit, $defaut); }
    if (!in_array($role, ROLES_DROITS_CONFIGURABLES, true)) { return false; }
    if ($droit === 'equipe') { return true; }
    if (!isset(FAMILLES_DROITS_CONFIGURABLES[$droit])) { return false; }
    static $configuration = null;
    if ($configuration === null) {
        $etat = chargerParametreSite($pdo, 'droits_equipe', droitsEquipeParDefaut($defaut));
        $configuration = validerDroitsEquipe($etat['valeurs']);
    }
    return $configuration[$droit][$role];
}
