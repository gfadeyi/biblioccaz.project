<?php
require_once 'config.php';
require_once 'evenements_lib.php';

if (($_SESSION['role'] ?? '') !== 'auteur') {
    header("Location: login.php");
    exit();
}
$idAuteur = (int) $_SESSION['user_id'];

$mesEvenements = [];
try {
    $stmt = $pdo->prepare("
        SELECT e.*,
               COALESCE(SUM(i.statut = 'inscrit'), 0) AS nb_inscrits,
               COALESCE(SUM(i.statut = 'attente'), 0) AS nb_attente
        FROM evenement e
        LEFT JOIN evenement_inscription i ON i.id_evenement = e.id_evenement
        WHERE e.id_createur = ?
        GROUP BY e.id_evenement
        ORDER BY e.date_debut DESC");
    $stmt->execute([$idAuteur]);
    $mesEvenements = $stmt->fetchAll();
} catch (PDOException $e) {
}

$maintenant = date('Y-m-d H:i:s');
$ordreStatut = fn(array $e) => match (true) {
    $e['statut'] === 'en_attente' => 0,
    $e['statut'] === 'publie' && $e['date_fin'] >= $maintenant => 1,
    default => 2,
};
usort($mesEvenements, function ($a, $b) use ($ordreStatut) {
    $ecart = $ordreStatut($a) <=> $ordreStatut($b);
    if ($ecart !== 0) {
        return $ecart;
    }
    return $ordreStatut($a) === 2 ? strcmp($b['date_debut'], $a['date_debut']) : strcmp($a['date_debut'], $b['date_debut']);
});
$nbEnAttente = count(array_filter($mesEvenements, fn($e) => $e['statut'] === 'en_attente'));
$nbPublies = count(array_filter($mesEvenements, fn($e) => $e['statut'] === 'publie' && $e['date_fin'] >= $maintenant));

$stmt = $pdo->prepare("SELECT id_livre, titre, auteur, couverture, is_valide FROM livre WHERE id_user_auteur = ? ORDER BY id_livre DESC");
$stmt->execute([$idAuteur]);
$mesLivres = $stmt->fetchAll();

$flash = $_SESSION['flash_auteur'] ?? null;
unset($_SESSION['flash_auteur']);

include 'header.php';
?>

<div class="container mt-4 mb-5 page-createur">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Espace créateur</span>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="titre-page-compte mb-1">Espace créateur</h1>
            <p class="text-muted mb-0">Proposez vos rencontres et vos livres, et suivez leur validation par l'équipe.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="proposer_evenement.php" class="bouton bouton-principal bouton-petit"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Proposer un événement</a>
            <a href="proposer_ouvrage.php" class="bouton bouton-contour bouton-petit"><i class="bi bi-journal-plus" aria-hidden="true"></i> Proposer un livre</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <section class="carte-compte mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="carte-compte__titre mb-0"><?= iconeDessin('ticket') ?> Mes événements</h2>
            <?php if ($mesEvenements): ?>
                <span class="small text-muted"><?= $nbEnAttente ?> en attente · <?= $nbPublies ?> à venir</span>
            <?php endif; ?>
        </div>

        <?php if (!$mesEvenements): ?>
            <div class="etat-vide-compte py-4">
                <?= iconeDessin('ticket') ?>
                <h3 class="h5">Aucun événement proposé pour l'instant</h3>
                <p>Une dédicace, une lecture, un atelier d'écriture ? Proposez-le : l'équipe le valide puis il apparaît dans le calendrier.</p>
                <a href="proposer_evenement.php" class="bouton bouton-principal bouton-petit">Proposer mon premier événement</a>
            </div>
        <?php else: ?>
            <ul class="liste-propositions">
                <?php foreach ($mesEvenements as $e): ?>
                    <?php
                    $t = strtotime($e['date_debut']);
                    $aVenir = $e['statut'] === 'publie' && $e['date_fin'] >= $maintenant;
                    $publieOuPasse = in_array($e['statut'], ['publie', 'annule'], true) && !empty($e['date_validation']);
                    ?>
                    <li class="proposition<?= in_array($e['statut'], ['refuse', 'retire', 'annule'], true) ? ' close' : '' ?>">
                        <div class="proposition__date" aria-hidden="true">
                            <span><?= mb_substr(EVT_JOURS[(int) date('w', $t)], 0, 3) ?></span>
                            <strong><?= date('j', $t) ?></strong>
                            <span><?= EVT_MOIS_COURTS[(int) date('n', $t) - 1] ?></span>
                        </div>
                        <div class="proposition__contenu">
                            <div class="proposition__entete">
                                <h3 class="proposition__titre"><?= htmlspecialchars($e['titre']) ?></h3>
                                <?= libelleStatutEvenement($e) ?>
                            </div>
                            <p class="proposition__infos">
                                <?= EVT_TYPES[$e['type']][0] ?? htmlspecialchars($e['type']) ?> · <?= ucfirst(horaireEvenement($e)) ?> · <?= htmlspecialchars($e['lieu']) ?>
                            </p>
                            <?php if ($e['statut'] === 'en_attente'): ?>
                                <p class="proposition__note">Envoyée le <?= date('d/m/Y', strtotime($e['date_creation'])) ?>. L'équipe va l'examiner, vous recevrez un mail.</p>
                            <?php elseif ($e['statut'] === 'refuse'): ?>
                                <div class="proposition__motif">
                                    <strong>Motif du refus :</strong> <?= nl2br(htmlspecialchars($e['motif_refus'] ?? '')) ?>
                                </div>
                            <?php elseif ($publieOuPasse): ?>
                                <p class="proposition__note">
                                    <strong><?= (int) $e['nb_inscrits'] ?></strong> inscrit<?= $e['nb_inscrits'] > 1 ? 's' : '' ?> sur <?= (int) $e['places_max'] ?> places<?= $e['nb_attente'] > 0 ? ' · ' . (int) $e['nb_attente'] . " en liste d'attente" : '' ?>
                                </p>
                            <?php endif; ?>
                            <div class="proposition__actions">
                                <?php if ($e['statut'] === 'en_attente'): ?>
                                    <a href="proposer_evenement.php?id=<?= (int) $e['id_evenement'] ?>" class="bouton bouton-contour bouton-petit"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier</a>
                                    <a href="evenement.php?id=<?= (int) $e['id_evenement'] ?>" class="bouton bouton-discret bouton-petit"><i class="bi bi-eye" aria-hidden="true"></i> Aperçu</a>
                                <?php elseif ($publieOuPasse): ?>
                                    <a href="evenement_inscrits.php?id=<?= (int) $e['id_evenement'] ?>" class="bouton <?= $aVenir ? 'bouton-principal' : 'bouton-contour' ?> bouton-petit"><i class="bi bi-people" aria-hidden="true"></i> Voir les inscrits</a>
                                    <a href="evenement.php?id=<?= (int) $e['id_evenement'] ?>" class="bouton bouton-discret bouton-petit"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Page publique</a>
                                <?php elseif ($e['statut'] === 'refuse'): ?>
                                    <a href="proposer_evenement.php" class="bouton bouton-discret bouton-petit"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Proposer un autre événement</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="carte-compte">
        <h2 class="carte-compte__titre"><?= iconeDessin('plume') ?> Mes livres proposés</h2>
        <?php if (!$mesLivres): ?>
            <p class="text-muted mb-0">Vous n'avez pas encore proposé de livre au catalogue. <a href="proposer_ouvrage.php" class="lien-voir-tout">Proposer un livre <i class="bi bi-chevron-right" aria-hidden="true"></i></a></p>
        <?php else: ?>
            <ul class="liste-profil">
                <?php foreach ($mesLivres as $livre): ?>
                    <li>
                        <span class="d-flex align-items-center gap-3">
                            <img src="img/<?= rawurlencode($livre['couverture'] ?: 'default.png') ?>" alt="" class="miniature-livre" loading="lazy">
                            <span>
                                <?php if ($livre['is_valide']): ?>
                                    <a href="detail_livre.php?id=<?= (int) $livre['id_livre'] ?>"><?= htmlspecialchars($livre['titre']) ?></a>
                                <?php else: ?>
                                    <strong><?= htmlspecialchars($livre['titre']) ?></strong>
                                <?php endif; ?>
                                <br><small><?= htmlspecialchars($livre['auteur'] ?? '') ?></small>
                            </span>
                        </span>
                        <?php if ($livre['is_valide']): ?>
                            <span class="etiquette-profil"><i class="bi bi-check2-circle" aria-hidden="true"></i> En ligne</span>
                        <?php else: ?>
                            <span class="etiquette-profil attente"><i class="bi bi-hourglass-split" aria-hidden="true"></i> En attente</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
