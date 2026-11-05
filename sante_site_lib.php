<?php
require_once __DIR__ . '/sessions_lib.php';

const SANTE_EXTRAIT_JOURNAL_OCTETS = 524288;
const SANTE_RELANCES_RETARD_SECONDES = 36 * 3600;
const SANTE_RELANCES_DUREE_SECONDES = 15 * 60;
const SANTE_SAUVEGARDE_AGE_SECONDES = 7 * 86400;

function dateSante(string $date): ?int
{
    $instant = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date);
    return $instant && $instant->format('Y-m-d H:i:s') === $date ? $instant->getTimestamp() : null;
}

function santeRelances(PDO $pdo, int $maintenant): array
{
    try {
        $requete = $pdo->prepare('SELECT date_debut, date_fin, statut, rappels, expirations, erreurs FROM relance_execution ORDER BY id_execution DESC LIMIT 1');
        $requete->execute();
        $execution = $requete->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $erreur) {
        return ['etat' => 'inconnu', 'libelle' => 'Suivi indisponible', 'date' => null, 'detail' => 'Le suivi des relances ne peut pas être lu.'];
    }
    if (!$execution) { return ['etat' => 'inconnu', 'libelle' => 'Aucun passage enregistré', 'date' => null, 'detail' => 'Consultez le suivi et la programmation de la tâche.']; }
    $debut = dateSante((string) $execution['date_debut']);
    $fin = $execution['date_fin'] !== null ? dateSante((string) $execution['date_fin']) : null;
    $resume = max(0, (int) $execution['rappels']) . ' rappel(s), ' . max(0, (int) $execution['expirations']) . ' expiration(s), ' . max(0, (int) $execution['erreurs']) . ' erreur(s).';
    if ($debut === null || $debut > $maintenant + 300 || ($fin !== null && ($fin < $debut || $fin > $maintenant + 300))) {
        return ['etat' => 'attention', 'libelle' => 'Dates à vérifier', 'date' => $debut, 'detail' => 'Les dates du passage sont incohérentes ou dans le futur.'];
    }
    if ($execution['statut'] === 'en_cours' && (int) $execution['erreurs'] === 0 && $fin === null && $maintenant - $debut <= SANTE_RELANCES_DUREE_SECONDES) {
        return ['etat' => 'en_cours', 'libelle' => 'Passage en cours', 'date' => $debut, 'detail' => 'Un passage vient de démarrer. Revenez vérifier sa fin.'];
    }
    if ($execution['statut'] !== 'terminee' || (int) $execution['erreurs'] > 0 || $fin === null) {
        return ['etat' => 'attention', 'libelle' => 'Passage à vérifier', 'date' => $debut, 'detail' => $resume . ' Le passage est incomplet, en erreur ou dure depuis plus de quinze minutes.'];
    }
    if ($maintenant - $debut > SANTE_RELANCES_RETARD_SECONDES) {
        return ['etat' => 'attention', 'libelle' => 'Passage ancien', 'date' => $debut, 'detail' => 'Aucun nouveau début de passage depuis plus de 36 heures. ' . $resume];
    }
    return ['etat' => 'ok', 'libelle' => 'Dernier passage terminé', 'date' => $debut, 'detail' => $resume];
}

function extraitJournalSante(string $chemin): array
{
    if (!file_exists($chemin)) { return ['lecture' => 'absent', 'tronque' => false, 'lignes' => []]; }
    if (!is_file($chemin) || is_link($chemin) || !is_readable($chemin)) { return ['lecture' => 'illisible', 'tronque' => false, 'lignes' => []]; }
    $fichier = @fopen($chemin, 'rb');
    if ($fichier === false) { return ['lecture' => 'illisible', 'tronque' => false, 'lignes' => []]; }
    try {
        $stat = fstat($fichier);
        if ($stat === false) { return ['lecture' => 'illisible', 'tronque' => false, 'lignes' => []]; }
        $debut = max(0, (int) $stat['size'] - SANTE_EXTRAIT_JOURNAL_OCTETS);
        if (fseek($fichier, $debut) !== 0) { return ['lecture' => 'illisible', 'tronque' => false, 'lignes' => []]; }
        $contenu = stream_get_contents($fichier, SANTE_EXTRAIT_JOURNAL_OCTETS);
        if ($contenu === false) { return ['lecture' => 'illisible', 'tronque' => false, 'lignes' => []]; }
        if ($debut > 0) { $nouvelleLigne = strpos($contenu, "\n"); $contenu = $nouvelleLigne === false ? '' : substr($contenu, $nouvelleLigne + 1); }
        if ($contenu !== '' && !str_ends_with($contenu, "\n")) { $derniereLigne = strrpos($contenu, "\n"); $contenu = $derniereLigne === false ? '' : substr($contenu, 0, $derniereLigne + 1); }
        return ['lecture' => 'disponible', 'tronque' => $debut > 0, 'lignes' => explode("\n", $contenu)];
    } finally { fclose($fichier); }
}

function santeJournal(string $chemin, string $type, int $maintenant): array
{
    $extrait = extraitJournalSante($chemin);
    $resultat = ['etat' => 'inconnu', 'nombre' => 0, 'dernier' => null, 'tronque' => $extrait['tronque'], 'lecture' => $extrait['lecture'], 'dates_invalides' => 0];
    if ($extrait['lecture'] !== 'disponible') { return $resultat; }
    foreach ($extrait['lignes'] as $ligne) {
        $date = null;
        if ($type === 'courriels') {
            if (!preg_match('/^\[([0-9-]{10} [0-9:]{8})\] \[ERREUR\] \[IP: [^\]]*\] \[USER_ID: [^\]]*\] - (.*)$/u', $ligne, $m)
                || !preg_match('/(?:mail|courriel|smtp)/iu', $m[2]) || !preg_match('/(?:non envoy|échou|échec|impossible)/iu', $m[2])) { continue; }
            $date = dateSante($m[1]);
        } else {
            if (!preg_match('/^\[([0-9]{2}-[A-Za-z]{3}-[0-9]{4} [0-9:]{8} [A-Za-z_\/+:0-9-]+)\] /', $ligne, $m)) { continue; }
            $instant = DateTimeImmutable::createFromFormat('!d-M-Y H:i:s e', $m[1]);
            if ($instant && $instant->format('d-M-Y H:i:s e') === $m[1]) { $date = $instant->getTimestamp(); }
        }
        if ($date === null || $date > $maintenant + 300) { $resultat['dates_invalides']++; continue; }
        $resultat['dernier'] = max($resultat['dernier'] ?? 0, $date);
        if ($date >= $maintenant - 86400) { $resultat['nombre']++; }
    }
    $resultat['etat'] = $resultat['nombre'] > 0 || $resultat['dates_invalides'] > 0 ? 'attention' : 'ok';
    return $resultat;
}

function santeSauvegardes(string $dossierPrive, int $maintenant): array
{
    $export = null;
    $code = null;
    $illisible = false;
    $partiel = false;
    $vus = 0;
    foreach (['sauvegardes', 'sauvegardes-locales'] as $nomDossier) {
        $dossier = rtrim($dossierPrive, '/\\') . '/' . $nomDossier;
        if (!file_exists($dossier)) { continue; }
        if (!is_dir($dossier) || is_link($dossier) || !is_readable($dossier)) { $illisible = true; continue; }
        try {
            foreach (new DirectoryIterator($dossier) as $fichier) {
                if (++$vus > 2000) { $partiel = true; break 2; }
                if ($fichier->isDot() || $fichier->isLink()) { continue; }
                $nom = $fichier->getFilename();
                if ($fichier->isDir() && preg_match('/^code-avant-[A-Za-z0-9_.-]+$/D', $nom)) {
                    $date = $fichier->getMTime();
                    if ($code === null || $date > $code) { $code = $date; }
                }
                if (!$fichier->isFile() || !preg_match('/^(?:base-avant-|biblioccaz-avant-|avant-)[A-Za-z0-9_.-]+\.sql$/D', $nom)) { continue; }
                $date = $fichier->getMTime();
                if ($export === null || [$date, $nom] > [$export['date'], $export['nom']]) {
                    $export = ['date' => $date, 'nom' => $nom, 'chemin' => $fichier->getPathname(), 'octets' => $fichier->getSize()];
                }
            }
        } catch (RuntimeException $erreur) { $illisible = true; }
    }
    $resultat = ['etat' => 'inconnu', 'libelle' => 'Aucun export détecté', 'date' => null, 'octets' => null, 'fin_export' => false, 'date_code' => $code, 'partiel' => $partiel || $illisible];
    if ($export === null) { return $resultat; }
    $resultat['date'] = $export['date'];
    $resultat['octets'] = $export['octets'];
    $fichier = @fopen($export['chemin'], 'rb');
    if ($fichier === false) { $resultat['libelle'] = 'Export non lisible'; return $resultat; }
    try {
        if (fseek($fichier, max(0, $export['octets'] - 2048)) !== 0) { $resultat['libelle'] = 'Export non lisible'; return $resultat; }
        $fin = stream_get_contents($fichier, 2048);
        $resultat['fin_export'] = is_string($fin) && preg_match('/(?:^|\n)-- Dump completed on [0-9-]{10}\s+[0-9]{1,2}:[0-9]{2}:[0-9]{2}\s*\z/', $fin) === 1;
    } finally { fclose($fichier); }
    $resultat['etat'] = 'attention';
    $resultat['libelle'] = 'Export à vérifier';
    if ($export['octets'] === 0 || !$resultat['fin_export']) { return $resultat; }
    if ($export['date'] > $maintenant + 300) { $resultat['libelle'] = 'Date de fichier à vérifier'; return $resultat; }
    if ($maintenant - $export['date'] > SANTE_SAUVEGARDE_AGE_SECONDES) { $resultat['libelle'] = 'Export de plus de sept jours'; return $resultat; }
    $resultat['etat'] = $resultat['partiel'] ? 'inconnu' : 'ok';
    $resultat['libelle'] = $resultat['partiel'] ? 'Inventaire partiel' : 'Export récent détecté';
    return $resultat;
}

function syntheseSanteSite(PDO $pdo, ?string $dossier = null, ?int $maintenant = null): array
{
    $maintenant ??= time();
    $dossier ??= dossierPrive();
    return ['date' => $maintenant, 'relances' => santeRelances($pdo, $maintenant), 'courriels' => santeJournal($dossier . '/logs.txt', 'courriels', $maintenant), 'php' => santeJournal($dossier . '/php_erreurs.log', 'php', $maintenant), 'sauvegardes' => santeSauvegardes($dossier, $maintenant)];
}
