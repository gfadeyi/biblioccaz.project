<?php
require_once __DIR__ . '/recompenses_regles_lib.php';

function participationMembre(PDO $pdo, int $idUser, ?DateTimeImmutable $instant = null): array
{
    $regles = chargerReglesRecompenses($pdo)['regles']['profil_actif'];
    $maintenant = $instant ?? new DateTimeImmutable();
    $debut = $maintenant->modify('-' . $regles['periode_jours'] . ' days')->format('Y-m-d H:i:s');
    $fin = $maintenant->format('Y-m-d H:i:s');
    $resultat = ['disponible' => true, 'actif' => false, 'connexion_recente' => false, 'actions' => 0, 'detail' => [], 'periode_jours' => $regles['periode_jours'], 'actions_min' => $regles['actions_min']];
    try {
        $requete = $pdo->prepare('SELECT role, statut, email_verifie FROM user WHERE id = ?');
        $requete->execute([$idUser]);
        $membre = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$membre || !in_array($membre['role'], ['client', 'auteur'], true) || $membre['statut'] !== 'actif' || !(int) $membre['email_verifie']) { return $resultat; }
        $requete = $pdo->prepare("SELECT MAX(date_connexion) FROM session_utilisateur WHERE id_user = ? AND espace = 'site' AND date_connexion BETWEEN ? AND ?");
        $requete->execute([$idUser, $debut, $fin]);
        $resultat['connexion_recente'] = (bool) $requete->fetchColumn();
        $requete = $pdo->prepare("SELECT type_action, COUNT(*) AS nombre FROM (
            SELECT 'achat' AS type_action FROM commande WHERE id_user = ? AND statut = 'validee' AND date_commande BETWEEN ? AND ?
            UNION ALL SELECT 'don' FROM don WHERE id_donneur = ? AND statut = 'recupere' AND date_recuperation BETWEEN ? AND ?
            UNION ALL SELECT 'emprunt' FROM emprunt WHERE id_user = ? AND statut = 'rendu' AND date_retour BETWEEN ? AND ?
            UNION ALL SELECT 'avis' FROM avis WHERE id_user = ? AND statut = 'visible' AND date_validation BETWEEN ? AND ?
            UNION ALL SELECT 'presence' FROM evenement_inscription WHERE id_user = ? AND statut = 'inscrit' AND present = 1 AND date_presence BETWEEN ? AND ?
        ) participation GROUP BY type_action");
        $parametres = [];
        for ($i = 0; $i < 5; $i++) { array_push($parametres, $idUser, $debut, $fin); }
        $requete->execute($parametres);
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $resultat['detail'][$ligne['type_action']] = (int) $ligne['nombre'];
            $resultat['actions'] += (int) $ligne['nombre'];
        }
        $resultat['actif'] = $resultat['connexion_recente'] && $resultat['actions'] >= $resultat['actions_min'];
    } catch (PDOException $erreur) {
        if (!in_array((int) ($erreur->errorInfo[1] ?? 0), [1146, 1054], true)) { throw $erreur; }
        $resultat['disponible'] = false;
        $resultat['actif'] = false;
    }
    return $resultat;
}
