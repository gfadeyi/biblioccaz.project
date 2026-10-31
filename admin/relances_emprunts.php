<?php
require_once __DIR__ . '/_init.php';
exigerDroit('catalogue');
$executions = [];
$suiviDisponible = true;
try {
    $requete = $pdo->prepare('SELECT * FROM relance_execution ORDER BY id_execution DESC LIMIT 50');
    $requete->execute();
    $executions = $requete->fetchAll();
} catch (PDOException $erreur) { $suiviDisponible = false; }
$requete = $pdo->prepare("SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
$requete->execute();
$enRetard = (int) $requete->fetchColumn();
$titrePage = 'Suivi des relances';
$pageActive = 'relances_emprunts';
include __DIR__ . '/_haut.php';
?>
<p class="text-muted">Les relances sont exécutées par la tâche planifiée des emprunts. Ce suivi permet de vérifier les passages et de repérer un problème d’envoi.</p>
<a href="emprunts.php" class="btn btn-outline-success mb-3">Voir les emprunts · <?= $enRetard ?> en retard</a>
<?php if (!$suiviDisponible): ?><div class="alert alert-warning">Installez <code>sql/maj_suivi_relances.sql</code> pour enregistrer les prochains passages.</div><?php endif; ?>
<?php if (!$executions): ?><div class="carte p-4">Aucun passage enregistré depuis l’installation du suivi.</div><?php endif; ?>
<?php if ($executions): ?>
    <?php $dernier = $executions[0]; ?>
    <?php if ($dernier['statut'] === 'erreur' || strtotime($dernier['date_debut']) < time() - 36 * 3600): ?><div class="alert alert-warning">Le dernier passage nécessite une vérification : consultez le journal et la configuration de la tâche planifiée.</div><?php endif; ?>
    <div class="carte table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Début</th><th>Fin</th><th>Résultat</th><th>Rappels</th><th>Expirations</th><th>Erreurs</th></tr></thead>
            <tbody>
                <?php foreach ($executions as $execution): ?>
                    <tr>
                        <td><?= e($execution['date_debut']) ?></td><td><?= e($execution['date_fin'] ?? '—') ?></td>
                        <td><?= e(['terminee' => 'Terminé', 'en_cours' => 'En cours', 'erreur' => 'À vérifier'][$execution['statut']] ?? $execution['statut']) ?></td>
                        <td><?= (int) $execution['rappels'] ?></td><td><?= (int) $execution['expirations'] ?></td><td><?= (int) $execution['erreurs'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/_bas.php'; ?>
