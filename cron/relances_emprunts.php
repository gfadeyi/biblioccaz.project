<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../emprunts_lib.php';

$verrou = $pdo->query("SELECT GET_LOCK('biblioccaz_relances_emprunts', 0)")->fetchColumn();
if ((int) $verrou !== 1) {
    fwrite(STDERR, date('Y-m-d H:i:s') . ' Relances déjà en cours, passage ignoré.' . PHP_EOL);
    exit(0);
}

$etatRappels = fn(): array => $pdo->query("SELECT id_emprunt, CONCAT(statut, '|', rappel_avant, '|', rappel_jour, '|', nb_relances) FROM emprunt WHERE statut IN ('reserve', 'en_cours', 'expire')")->fetchAll(PDO::FETCH_KEY_PAIR);

$idExecution = null;
try {
    $pdo->prepare("INSERT INTO relance_execution (statut) VALUES ('en_cours')")->execute();
    $idExecution = (int) $pdo->lastInsertId();
} catch (PDOException $erreur) {
    insertLog('ERREUR', 'Suivi des relances indisponible : installez la migration du suivi.');
}
$erreurs = 0;
try {
    $avant = $etatRappels();
    $enRetard = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()")->fetchColumn();

    $bilan = verifierEmprunts($pdo);
    $erreurs = $bilan['erreurs'];

    $apres = $etatRappels();
    $rappels = 0;
    $expirations = 0;
    foreach ($avant as $idEmprunt => $etat) {
        $nouvelEtat = $apres[$idEmprunt] ?? null;
        if ($nouvelEtat === null || $nouvelEtat === $etat) {
            continue;
        }
        str_starts_with($etat, 'reserve|') && str_starts_with($nouvelEtat, 'expire|') ? $expirations++ : $rappels++;
    }
    $resume = "Relances des emprunts : $rappels rappel(s) ou relance(s) envoyé(s), $expirations réservation(s) expirée(s), $enRetard emprunt(s) en retard.";
    insertLog('CRON', $resume);
    echo date('Y-m-d H:i:s') . ' ' . $resume . PHP_EOL;
    $codeSortie = $erreurs > 0 ? 1 : 0;
} catch (Throwable $e) {
    insertLog('ERREUR', 'Tâche planifiée des relances impossible : ' . $e->getMessage());
    fwrite(STDERR, date('Y-m-d H:i:s') . ' Erreur pendant les relances, voir le journal.' . PHP_EOL);
    $erreurs++;
    $codeSortie = 1;
} finally {
    if ($idExecution !== null) {
        try {
            $pdo->prepare('UPDATE relance_execution SET date_fin = NOW(), statut = ?, rappels = ?, expirations = ?, erreurs = ? WHERE id_execution = ?')
                ->execute([$erreurs > 0 ? 'erreur' : 'terminee', $rappels ?? 0, $expirations ?? 0, $erreurs, $idExecution]);
        } catch (Throwable $erreur) {
            insertLog('ERREUR', 'Le bilan des relances n’a pas pu être enregistré.');
            $codeSortie = 1;
        }
    }
    $pdo->query("SELECT RELEASE_LOCK('biblioccaz_relances_emprunts')");
}

exit($codeSortie);
