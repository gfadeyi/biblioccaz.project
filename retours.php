<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'retours_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRetour = (int) ($_POST['id_retour'] ?? 0);
    $_SESSION['flash_message'] = !verifierCsrf()
        ? ['danger', 'Session expirée, merci de réessayer.']
        : (($_POST['action'] ?? '') === 'annuler' ? annulerDemandeRetour($pdo, $idRetour, $idUser) : ['warning', 'Action inconnue.']);
    header('Location: retours?id=' . $idRetour);
    exit();
}

$disponible = retoursDisponibles($pdo);
$idRetour = (int) ($_GET['id'] ?? 0);
$retour = $disponible && $idRetour ? retourComplet($pdo, $idRetour, $idUser) : null;
$retours = $disponible && !$retour ? retoursMembre($pdo, $idUser) : [];
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
if ($idRetour && !$retour && !$flash) {
    $flash = ['warning', 'Cette demande n’existe pas ou ne vous concerne pas.'];
}
$titreDocument = $retour ? 'Demande ' . $retour['reference'] : 'Mes retours et remboursements';
include 'header.php';
?>

<div class="container my-4 page-retours" style="max-width: 900px;">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="mon-compte">Mon compte</a>
        <span aria-hidden="true">›</span>
        <?php if ($retour): ?>
            <a href="retours">Mes retours</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars($retour['reference']) ?></span>
        <?php else: ?>
            <span aria-current="page">Mes retours</span>
        <?php endif; ?>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if ($retour): ?>
        <?php $etapes = etapesRetour($retour['type']); $position = array_search($retour['statut'], $etapes, true); $termineMal = in_array($retour['statut'], ['refuse', 'annule'], true); ?>
        <h1 class="titre-page-compte mb-1">Demande <?= htmlspecialchars($retour['reference']) ?></h1>
        <p class="text-muted mb-4"><?= $retour['type'] === 'retour' ? 'Retour' : 'Remboursement' ?> · commande n°<?= (int) $retour['id_commande'] ?> · envoyée le <?= date('d/m/Y à H:i', strtotime($retour['date_demande'])) ?></p>

        <ol class="etapes-retour<?= $termineMal ? ' etapes-retour--arretee' : '' ?>" aria-label="Avancement de la demande">
            <?php foreach ($etapes as $index => $etape): ?>
                <?php $etat = $termineMal ? ($index === 0 ? 'faite' : 'a-venir') : ($position !== false && $index < $position ? 'faite' : ($index === $position ? 'actuelle' : 'a-venir')); ?>
                <li class="etapes-retour__etape etapes-retour__etape--<?= $etat ?>"<?= $etat === 'actuelle' ? ' aria-current="step"' : '' ?>>
                    <span class="etapes-retour__point" aria-hidden="true"><?= $etat === 'faite' ? '✓' : $index + 1 ?></span>
                    <span><?= libelleStatutRetour($etape) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php if ($termineMal): ?>
            <div class="alert alert-<?= $retour['statut'] === 'refuse' ? 'danger' : 'secondary' ?> rounded-3"><strong><?= libelleStatutRetour($retour['statut']) ?>.</strong> <?= $retour['statut'] === 'refuse' && $retour['note_equipe'] ? htmlspecialchars($retour['note_equipe']) : '' ?></div>
        <?php elseif ($retour['statut'] === 'accepte' && $retour['type'] === 'retour'): ?>
            <div class="alert alert-success rounded-3">Demande acceptée : renvoyez les livres dans leur emballage (envoi fictif sur ce site de démonstration). Le remboursement suivra leur réception.</div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-7">
                <section class="carte-compte mb-4">
                    <h2 class="h6 fw-bold">Détail</h2>
                    <dl class="recap-retour">
                        <div><dt>Motif</dt><dd><?= htmlspecialchars(libelleMotifRetour($retour['type'], $retour['motif'])) ?></dd></div>
                        <?php if ($retour['message']): ?><div><dt>Vos précisions</dt><dd><?= nl2br(htmlspecialchars($retour['message'])) ?></dd></div><?php endif; ?>
                        <div><dt>Montant <?= $retour['statut'] === 'rembourse' ? 'remboursé' : 'à rembourser' ?></dt><dd><strong><?= formaterPrix((float) $retour['montant']) ?></strong> <small class="text-muted">(fictif)</small></dd></div>
                        <?php if ((int) $retour['points_retires'] > 0): ?><div><dt>Points retirés</dt><dd><?= (int) $retour['points_retires'] ?></dd></div><?php endif; ?>
                    </dl>
                    <?php if ($retour['lignes']): ?>
                        <ul class="lignes-retour lignes-retour--lecture">
                            <?php foreach ($retour['lignes'] as $ligne): ?>
                                <li>
                                    <img src="<?= htmlspecialchars(urlImage($ligne['couverture'])) ?>" alt="" loading="lazy">
                                    <span class="lignes-retour__titre"><strong><?= htmlspecialchars($ligne['titre']) ?></strong><small><?= htmlspecialchars(libelleEtat($ligne['etat'])) ?> · <?= (int) $ligne['quantite_retour'] ?> × <?= formaterPrix((float) $ligne['prix_unitaire']) ?></small></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($retour['statut'] === 'demande'): ?>
                        <form method="POST" class="mt-3" data-confirmer="Annuler la demande <?= htmlspecialchars($retour['reference']) ?> ?" data-bouton-confirmer="Annuler la demande">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="annuler">
                            <input type="hidden" name="id_retour" value="<?= (int) $retour['id_retour'] ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill">Annuler ma demande</button>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
            <div class="col-md-5">
                <section class="carte-compte">
                    <h2 class="h6 fw-bold">Historique</h2>
                    <ol class="historique-retour">
                        <?php foreach ($retour['historique'] as $etape): ?>
                            <li>
                                <time datetime="<?= date('c', strtotime($etape['date_action'])) ?>"><?= date('d/m/Y H:i', strtotime($etape['date_action'])) ?></time>
                                <strong><?= libelleStatutRetour($etape['statut']) ?></strong>
                                <span class="text-muted small"><?= (int) $etape['id_acteur'] === $idUser ? 'par vous' : 'par l’équipe' ?></span>
                                <?php if ($etape['commentaire'] && (int) $etape['id_acteur'] !== $idUser): ?><p class="small mb-0"><?= htmlspecialchars($etape['commentaire']) ?></p><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            </div>
        </div>
    <?php else: ?>
        <h1 class="titre-page-compte mb-1">Mes retours et remboursements</h1>
        <p class="text-muted mb-4">Un retour se demande depuis « Mes commandes » jusqu’à <?= RETOUR_DELAI_JOURS ?> jours après la livraison ; le suivi d’un remboursement, jusqu’à <?= RETOUR_REMBOURSEMENT_DELAI_JOURS ?> jours après une annulation.</p>
        <?php if (!$disponible): ?>
            <div class="alert alert-warning rounded-3">Les retours ne sont pas encore disponibles.</div>
        <?php elseif (!$retours): ?>
            <div class="carte-compte text-center text-muted py-5">
                <?= iconeDessin('colis') ?>
                <p class="mt-2 mb-3">Aucune demande de retour pour le moment.</p>
                <a href="commandes" class="btn btn-outline-bibli rounded-pill">Voir mes commandes</a>
            </div>
        <?php else: ?>
            <ul class="liste-retours">
                <?php foreach ($retours as $ligne): ?>
                    <li>
                        <a href="retours?id=<?= (int) $ligne['id_retour'] ?>" class="liste-retours__lien">
                            <span><strong><?= htmlspecialchars((string) $ligne['reference']) ?></strong> · <?= $ligne['type'] === 'retour' ? 'Retour' : 'Remboursement' ?> · commande n°<?= (int) $ligne['id_commande'] ?><small class="d-block text-muted"><?= date('d/m/Y', strtotime($ligne['date_demande'])) ?> · <?= formaterPrix((float) $ligne['montant']) ?></small></span>
                            <span class="statut-retour statut-retour--<?= RETOUR_STATUTS[$ligne['statut']][1] ?? 'attente' ?>"><?= libelleStatutRetour($ligne['statut']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
