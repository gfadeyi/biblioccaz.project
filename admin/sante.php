<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');
require_once __DIR__ . '/../sante_site_lib.php';

$sante = syntheseSanteSite($pdo);
$indicateurs = array_intersect_key($sante, array_flip(['relances', 'courriels', 'php', 'sauvegardes']));
$aVerifier = count(array_filter($indicateurs, fn($i) => $i['etat'] === 'attention'));
$inconnus = count(array_filter($indicateurs, fn($i) => $i['etat'] === 'inconnu'));
$dateLisible = fn(?int $date) => $date !== null ? date('d/m/Y à H:i', $date) : 'Non disponible';
$classes = ['ok' => 'bg-success', 'attention' => 'bg-dark', 'inconnu' => 'bg-secondary', 'en_cours' => 'bg-secondary'];
$titrePage = 'Santé du site';
$pageActive = 'sante';
$actionsPage = [['sante.php', 'arrow-clockwise', 'Actualiser', 'btn-outline-success']];
include __DIR__ . '/_haut.php';
?>

<p class="text-muted">Un aperçu des relances, des erreurs signalées et des sauvegardes présentes. Consultation en lecture seule : aucun envoi de mail, lancement de tâche ou restauration depuis cet écran.</p>
<div class="carte p-3 mb-4" role="status">
    <strong><?= $aVerifier ? $aVerifier . ' point' . ($aVerifier > 1 ? 's' : '') . ' à vérifier' : ($inconnus ? 'Informations à compléter' : 'Aucune alerte détectée sur ces indicateurs') ?></strong>
    <?php if ($inconnus): ?><span class="d-block small mt-1"><?= $inconnus ?> information<?= $inconnus > 1 ? 's' : '' ?> indisponible<?= $inconnus > 1 ? 's' : '' ?> : les cartes concernées précisent la limite.</span><?php endif; ?>
    <span class="d-block small text-muted mt-1">Lecture du <?= e($dateLisible($sante['date'])) ?> · Actualisation manuelle.</span>
</div>

<div class="row g-4">
    <section class="col-lg-6" aria-labelledby="titreRelancesSante">
        <div class="carte p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <h2 class="h5 mb-0" id="titreRelancesSante"><i class="bi bi-bell me-2" aria-hidden="true"></i>Relances des emprunts</h2>
                <span class="badge <?= $classes[$sante['relances']['etat']] ?>"><?= e($sante['relances']['libelle']) ?></span>
            </div>
            <p class="mb-2"><strong>Dernier début :</strong> <?= e($dateLisible($sante['relances']['date'])) ?></p>
            <p><?= e($sante['relances']['detail']) ?></p>
            <p class="small text-muted">Un début de passage de plus de 36 heures ou un passage en cours depuis plus de quinze minutes est signalé. Cet écran ne modifie pas la tâche planifiée.</p>
            <a href="relances_emprunts.php" class="btn btn-outline-success btn-sm">Voir le suivi des relances</a>
        </div>
    </section>
    <?php foreach (['courriels' => ['Courriels', 'envelope-exclamation', 'Échecs d’envoi repérés', 'Dernier échec repéré'], 'php' => ['Journal PHP', 'code-square', 'Entrées datées repérées', 'Dernière entrée repérée']] as $type => [$titre, $icone, $mesure, $dernier]): ?>
        <?php $journal = $sante[$type]; $mesureLisible = $journal['nombre'] === 1 ? ($type === 'courriels' ? 'échec d’envoi repéré' : 'entrée datée repérée') : mb_strtolower($mesure); ?>
        <section class="col-lg-6" aria-labelledby="titreSante-<?= e($type) ?>">
            <div class="carte p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <h2 class="h5 mb-0" id="titreSante-<?= e($type) ?>"><i class="bi bi-<?= e($icone) ?> me-2" aria-hidden="true"></i><?= e($titre) ?></h2>
                    <span class="badge <?= $classes[$journal['etat']] ?>"><?= $journal['etat'] === 'inconnu' ? 'Information indisponible' : ($journal['etat'] === 'attention' ? 'À vérifier' : 'Aucun signal récent') ?></span>
                </div>
                <?php if ($journal['lecture'] === 'disponible'): ?>
                    <p class="mb-2"><strong><?= (int) $journal['nombre'] ?></strong> <?= e($mesureLisible) ?> sur les dernières 24 heures, dans l’extrait lu.</p>
                    <p><?= e($dernier) ?> : <?= e($dateLisible($journal['dernier'])) ?>.</p>
                    <?php if ($journal['dates_invalides']): ?><p class="small">Des dates non reconnues ou futures sont présentes : les compteurs demandent une vérification.</p><?php endif; ?>
                    <?php if ($journal['tronque']): ?><p class="small">Le journal dépasse 512 Kio. L’extrait peut ne pas couvrir les 24 dernières heures.</p><?php endif; ?>
                <?php else: ?>
                    <p><?= $journal['lecture'] === 'absent' ? 'Aucun fichier de journal présent. Un journal peut ne pas encore avoir été créé.' : 'Le fichier du journal ne peut pas être lu.' ?></p>
                <?php endif; ?>
                <p class="small text-muted">Lecture limitée aux 512 Kio les plus récents. Aucun contenu du journal, adresse email ou détail de configuration n’est affiché ici. <?= $type === 'courriels' ? 'Un compteur à zéro ne confirme pas la réception des messages.' : 'Les traces techniques complètes restent dans le dossier privé.' ?></p>
                <?php if ($type === 'courriels'): ?><a href="logs.php" class="btn btn-outline-success btn-sm">Ouvrir le journal d’activité</a><?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <section class="col-lg-6" aria-labelledby="titreSauvegardesSante">
        <?php $sauvegarde = $sante['sauvegardes']; ?>
        <div class="carte p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <h2 class="h5 mb-0" id="titreSauvegardesSante"><i class="bi bi-database-check me-2" aria-hidden="true"></i>Sauvegardes détectées</h2>
                <span class="badge <?= $classes[$sauvegarde['etat']] ?>"><?= e($sauvegarde['libelle']) ?></span>
            </div>
            <p class="mb-2"><strong>Dernier fichier SQL :</strong> <?= e($dateLisible($sauvegarde['date'])) ?>.</p>
            <?php if ($sauvegarde['octets'] !== null): ?><p class="mb-2">Taille : <?= e(number_format($sauvegarde['octets'] / 1024, 1, ',', ' ')) ?> Kio · Fin d’export <?= $sauvegarde['fin_export'] ? 'repérée' : 'non confirmée' ?>.</p><?php endif; ?>
            <p>Dossier de copie du code repéré : <?= e($dateLisible($sauvegarde['date_code'])) ?>.</p>
            <?php if ($sauvegarde['partiel']): ?><p class="small">L’inventaire est partiel : un dossier est illisible ou contient trop d’entrées.</p><?php endif; ?>
            <p class="small text-muted">Seuls les exports SQL et dossiers de copie privés du site sont recensés. Un export de plus de sept jours est signalé ; cela ne programme aucune sauvegarde. La présence et la fin d’export ne remplacent pas un test de restauration. Les sauvegardes externes de l’hébergeur ne sont pas recensées.</p>
        </div>
    </section>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
