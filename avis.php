<?php
require_once 'config.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT a.*, l.titre AS titre_livre, l.auteur, l.couverture,
           (SELECT COUNT(*) FROM avis_signalement s WHERE s.id_avis = a.id_avis AND s.traite = 0) AS nb_signalements
    FROM avis a INNER JOIN livre l ON l.id_livre = a.id_livre
    WHERE a.id_user = ?
    ORDER BY a.date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$mesAvis = $stmt->fetchAll();

$statutsPoints = [];
try {
    $stmt = $pdo->prepare("SELECT reference, statut FROM point_mouvement WHERE id_user = ? AND type = 'avis'");
    $stmt->execute([$_SESSION['user_id']]);
    $statutsPoints = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
}
$libellesPoints = [
    'en_attente' => ['hourglass-split', AVIS_POINTS . ' points en attente de validation', ''],
    'valide' => ['check2-circle', AVIS_POINTS . ' points crédités', ''],
    'refuse' => ['x-circle', 'Points refusés par la modération', 'refuse'],
];

$flash = $_SESSION['flash_avis'] ?? null;
unset($_SESSION['flash_avis']);
$csrf = jetonCsrf();

include 'header.php';
echo etoilesStyle();
?>

<div class="container mt-4 mb-5 page-mes-avis">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes avis</span>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
        <h1 class="titre-page-compte">Mes avis</h1>
        <?php if ($mesAvis): ?><span class="text-muted"><?= count($mesAvis) ?> avis publié<?= count($mesAvis) > 1 ? 's' : '' ?></span><?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> rounded-3"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!$mesAvis): ?>
        <div class="carte-compte etat-vide-compte">
            <?= iconeDessin('plume') ?>
            <h2 class="h5">Vous n'avez encore donné aucun avis</h2>
            <p>Ouvrez la fiche d'un livre que vous avez lu pour le noter : chaque avis validé vous rapporte <?= AVIS_POINTS ?> points.</p>
            <a href="catalogue.php" class="bouton bouton-principal bouton-petit">Parcourir le catalogue</a>
        </div>
    <?php endif; ?>

    <div class="liste-mes-avis">
        <?php foreach ($mesAvis as $a): ?>
            <?php $statutPoints = $statutsPoints['livre:' . $a['id_livre']] ?? null; ?>
            <article class="carte-compte mon-avis<?= $a['statut'] === 'masque' ? ' masque' : '' ?>">
                <a href="detail_livre.php?id=<?= (int) $a['id_livre'] ?>" class="mon-avis__couverture" tabindex="-1" aria-hidden="true">
                    <img src="img/<?= rawurlencode($a['couverture'] ?: 'default.png') ?>" alt="" loading="lazy">
                </a>
                <div class="mon-avis__contenu">
                    <div class="mon-avis__entete">
                        <div>
                            <h2 class="mon-avis__livre"><a href="detail_livre.php?id=<?= (int) $a['id_livre'] ?>"><?= htmlspecialchars($a['titre_livre']) ?></a></h2>
                            <span class="mon-avis__auteur"><?= htmlspecialchars($a['auteur']) ?></span>
                        </div>
                        <small class="mon-avis__date">
                            <?= date('d/m/Y', strtotime($a['date_creation'])) ?><?= $a['date_modification'] ? ' · modifié le ' . date('d/m/Y', strtotime($a['date_modification'])) : '' ?>
                        </small>
                    </div>
                    <div class="mon-avis__note"><?= etoiles((float) $a['note']) ?><?php if ($a['titre']): ?> <strong><?= htmlspecialchars($a['titre']) ?></strong><?php endif; ?></div>
                    <p class="mon-avis__texte"><?= htmlspecialchars($a['commentaire']) ?></p>

                    <div class="mon-avis__etats">
                        <?php if ($a['statut'] === 'masque'): ?>
                            <span class="pastille-etat refuse"><i class="bi bi-eye-slash" aria-hidden="true"></i> Masqué par la modération</span>
                        <?php endif; ?>
                        <?php if ($statutPoints && isset($libellesPoints[$statutPoints])): ?>
                            <?php [$iconePoints, $textePoints, $classePoints] = $libellesPoints[$statutPoints]; ?>
                            <span class="pastille-etat <?= $classePoints ?>"><i class="bi bi-<?= $iconePoints ?>" aria-hidden="true"></i> <?= $textePoints ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="mon-avis__actions">
                        <a href="detail_livre.php?id=<?= (int) $a['id_livre'] ?>#avis" class="bouton bouton-contour bouton-petit">
                            <i class="bi bi-pencil" aria-hidden="true"></i> Modifier
                        </a>
                        <form action="action_avis.php" method="POST"
                              data-confirmer="Supprimer votre avis sur « <?= htmlspecialchars($a['titre_livre']) ?> » ? <?= $statutPoints === 'en_attente' ? 'Les points en attente seront annulés.' : 'Cette action est définitive.' ?>"
                              data-titre-confirmer="Supprimer cet avis" data-bouton-confirmer="Supprimer">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="retour" value="mes_avis">
                            <input type="hidden" name="id_livre" value="<?= (int) $a['id_livre'] ?>">
                            <input type="hidden" name="id_avis" value="<?= (int) $a['id_avis'] ?>">
                            <button class="bouton bouton-danger bouton-petit"><i class="bi bi-trash3" aria-hidden="true"></i> Supprimer</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
