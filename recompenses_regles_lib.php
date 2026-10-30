<?php

const BAREME_RECOMPENSES = [
    'achat' => ['Points par euro payé', 1, 10],
    'avis' => ['Avis validé', 5, 1000],
    'premier_avis' => ['Premier avis validé', 10, 1000],
    'premier_achat' => ['Premier achat', 20, 1000],
    'don' => ['Don récupéré', 20, 1000],
    'presence' => ['Présence à un événement', 10, 1000],
    'vente' => ['Vente confirmée', 10, 1000],
    'emprunt' => ['Emprunt rendu à l’heure', 5, 1000],
    'badge_bronze' => ['Bonus badge bronze', 5, 1000],
    'badge_argent' => ['Bonus badge argent', 15, 1000],
    'badge_or' => ['Bonus badge or', 30, 1000],
    'badge_rare' => ['Bonus badge rare', 30, 1000],
];
const QUOTAS_RECOMPENSES = ['avis' => 4, 'vente' => 5, 'emprunt' => 5];
const SEUILS_BADGES_RECOMPENSES = [
    'lecteur_fidele' => [1, 10, 50], 'plume_critique' => [1, 10, 30],
    'coeur_genereux' => [1, 5, 20], 'habitue_rencontres' => [1, 5, 15],
    'seconde_main' => [1, 5, 15], 'habitue_bibliotheque' => [1, 10, 30],
    'un_an' => [5], 'explorateur' => [6], 'collectionneur' => [25], 'pilier' => [4],
];

function reglesRecompensesParDefaut(): array
{
    return ['points' => array_map(fn($regle) => $regle[1], BAREME_RECOMPENSES), 'quotas' => QUOTAS_RECOMPENSES,
        'niveaux' => [0, 150, 400, 900, 1800, 3500], 'badges' => SEUILS_BADGES_RECOMPENSES, 'profil_actif' => ['periode_jours' => 30, 'actions_min' => 1]];
}

function entierRegle(mixed $valeur, int $minimum, int $maximum): int
{
    if ((!is_int($valeur) && !is_string($valeur)) || !preg_match('/\A[0-9]{1,7}\z/', (string) $valeur) || (int) $valeur < $minimum || (int) $valeur > $maximum) {
        throw new DomainException('Une valeur est absente, invalide ou hors des limites autorisées.');
    }
    return (int) $valeur;
}

function validerReglesRecompenses(array $saisie, bool $configurationHistorique = false): array
{
    $defauts = reglesRecompensesParDefaut();
    if ($configurationHistorique && !array_key_exists('profil_actif', $saisie)) { $saisie['profil_actif'] = $defauts['profil_actif']; }
    if (array_diff(array_keys($saisie), array_keys($defauts)) || array_diff(array_keys($defauts), array_keys($saisie))) { throw new DomainException('Configuration incomplète ou inconnue.'); }
    foreach ($defauts as $section => $champs) {
        if (!is_array($saisie[$section]) || array_diff(array_keys($saisie[$section]), array_keys($champs)) || array_diff(array_keys($champs), array_keys($saisie[$section]))) { throw new DomainException('Champs de configuration invalides.'); }
    }
    $defauts['profil_actif']['periode_jours'] = entierRegle($saisie['profil_actif']['periode_jours'], 1, 365);
    $defauts['profil_actif']['actions_min'] = entierRegle($saisie['profil_actif']['actions_min'], 1, 100);
    foreach (BAREME_RECOMPENSES as $cle => $regle) { $defauts['points'][$cle] = entierRegle($saisie['points'][$cle], 0, $regle[2]); }
    foreach (QUOTAS_RECOMPENSES as $cle => $valeur) { $defauts['quotas'][$cle] = entierRegle($saisie['quotas'][$cle], 0, 100); }
    ksort($saisie['niveaux']);
    foreach ($saisie['niveaux'] as $index => $valeur) {
        $seuil = entierRegle($valeur, 0, 1000000);
        if (($index === 0 && $seuil !== 0) || ($index > 0 && $seuil <= $defauts['niveaux'][$index - 1])) { throw new DomainException('Les niveaux commencent à zéro et leurs seuils doivent être strictement croissants.'); }
        $defauts['niveaux'][$index] = $seuil;
    }
    foreach (SEUILS_BADGES_RECOMPENSES as $code => $paliers) {
        if (!is_array($saisie['badges'][$code]) || array_keys($saisie['badges'][$code]) !== array_keys($paliers)) { throw new DomainException('Paliers de badge invalides.'); }
        $precedent = 0;
        foreach ($paliers as $index => $valeur) {
            $maximum = in_array($code, ['explorateur', 'pilier'], true) ? 6 : 100000;
            $seuil = entierRegle($saisie['badges'][$code][$index], 1, $maximum);
            if ($seuil <= $precedent) { throw new DomainException('Les paliers de chaque badge doivent être strictement croissants.'); }
            $defauts['badges'][$code][$index] = $seuil;
            $precedent = $seuil;
        }
    }
    return $defauts;
}

function lireReglesRecompenses(PDO $pdo): array
{
    try {
        $stmt = $pdo->prepare('SELECT version, configuration FROM regle_recompense WHERE id_regle = 1');
        $stmt->execute();
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ligne) { return ['version' => 0, 'disponible' => true, 'regles' => reglesRecompensesParDefaut()]; }
        return ['version' => (int) $ligne['version'], 'disponible' => true, 'regles' => validerReglesRecompenses(json_decode($ligne['configuration'], true, 512, JSON_THROW_ON_ERROR), true)];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) !== 1146) { throw $e; }
        return ['version' => 0, 'disponible' => false, 'regles' => reglesRecompensesParDefaut()];
    }
}

function chargerReglesRecompenses(PDO $pdo, bool $recharger = false): array
{
    static $cache = [];
    $cle = spl_object_id($pdo);
    if ($recharger || !isset($cache[$cle])) { $cache[$cle] = lireReglesRecompenses($pdo); }
    return $cache[$cle];
}

function pointsRegle(PDO $pdo, string $action): int
{
    $regles = chargerReglesRecompenses($pdo)['regles'];
    return $regles['points'][$action];
}

function quotaRegle(PDO $pdo, string $action): int
{
    return chargerReglesRecompenses($pdo)['regles']['quotas'][$action];
}

function enregistrerReglesRecompenses(PDO $pdo, int $idAdministrateur, array $saisie, int $version): bool
{
    $regles = validerReglesRecompenses($saisie);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE id = ? AND role = 'admin' AND statut = 'actif' AND email_verifie = 1 FOR UPDATE");
        $stmt->execute([$idAdministrateur]);
        if (!$stmt->fetchColumn()) { throw new DomainException('Administration réservée aux administrateurs actifs.'); }
        $stmt = $pdo->prepare('SELECT version, configuration FROM regle_recompense WHERE id_regle = 1 FOR UPDATE');
        $stmt->execute();
        $avant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$avant || (int) $avant['version'] !== $version) { throw new DomainException('Les règles ont changé. Rechargez la page avant de réessayer.'); }
        if (validerReglesRecompenses(json_decode($avant['configuration'], true, 512, JSON_THROW_ON_ERROR), true) === $regles) { $pdo->commit(); return false; }
        $json = json_encode($regles, JSON_THROW_ON_ERROR);
        $pdo->prepare('UPDATE regle_recompense SET configuration = ?, version = version + 1, id_modifie_par = ?, date_modification = NOW() WHERE id_regle = 1')->execute([$json, $idAdministrateur]);
        $pdo->prepare('INSERT INTO regle_recompense_historique (version, ancienne_configuration, nouvelle_configuration, id_modifie_par) VALUES (?, ?, ?, ?)')->execute([$version + 1, $avant['configuration'], $json, $idAdministrateur]);
        $pdo->commit();
        chargerReglesRecompenses($pdo, true);
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}
