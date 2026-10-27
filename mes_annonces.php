<?php
require_once 'config.php';
require_once 'annonces_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT e.*, l.titre, l.auteur, l.couverture, l.is_valide AS livre_valide
    FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre
    WHERE e.id_proprietaire = ? AND e.type_offre = 'vente'
    ORDER BY FIELD(e.statut_annonce, 'refusee', 'en_validation', 'publiee', 'reservee', 'retiree', 'vendue'), e.date_creation DESC");
$stmt->execute([$idUser]);
$annonces = $stmt->fetchAll();
$photos = premieresPhotos($pdo, array_column($annonces, 'id_exemplaire'));
$nbActives = count(array_filter($annonces, fn($a) => in_array($a['statut_annonce'], ANNONCE_STATUTS_ACTIFS, true)));
$raisonInterdiction = raisonInterdictionVente($pdo, $idUser);

$flash = $_SESSION['flash_annonce'] ?? null;
unset($_SESSION['flash_annonce']);
$csrf = jetonCsrf();
$titreDocument = 'Mes annonces';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-mes-annonces">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes annonces</span>
    </nav>

    <div class="entete-mes-annonces">
        <div>
            <h1 class="titre-page-compte mb-1">Mes annonces</h1>
            <p class="text-muted mb-0"><?= $nbActives ?> / <?= ANNONCES_ACTIVES_MAX ?> annonces en cours · vos livres vendus entre lecteurs</p>
        </div>
        <?php if ($raisonInterdiction === ''): ?>
            <a href="annonce_creer.php" class="bouton bouton-principal bouton-petit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Vendre un livre</a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>
    <?php if ($raisonInterdiction !== ''): ?>
        <div class="alert alert-warning rounded-3"><?= htmlspecialchars($raisonInterdiction) ?></div>
    <?php endif; ?>

    <?php if (!$annonces): ?>
        <div class="carte-compte etat-vide-compte">
            <?= iconeDessin('etiquette') ?>
            <h2 class="h5">Vous n'avez encore rien mis en vente</h2>
            <p>Des livres qui dorment sur vos étagères ? Fixez votre prix, l'équipe vérifie l'annonce, et les lecteurs vous font leurs demandes.</p>
            <?php if ($raisonInterdiction === ''): ?>
                <a href="annonce_creer.php" class="bouton bouton-principal bouton-petit">Vendre mon premier livre</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="liste-mes-annonces">
        <?php foreach ($annonces as $a): ?>
            <?php
            $id = (int) $a['id_exemplaire'];
            $statut = (string) $a['statut_annonce'];
            $retireeParEquipe = $statut === 'retiree' && $a['motif_moderation'];
            ?>
            <article class="carte-compte mon-annonce mon-annonce--<?= htmlspecialchars($statut) ?>">
                <a href="annonce.php?id=<?= $id ?>" class="mon-annonce__visuel" tabindex="-1" aria-hidden="true">
                    <img src="<?= htmlspecialchars(urlPhotoAnnonce($photos[$id] ?? null, $a['couverture'])) ?>" alt="" loading="lazy">
                </a>
                <div class="mon-annonce__contenu">
                    <div class="mon-annonce__entete">
                        <div>
                            <h2 class="mon-annonce__titre"><a href="annonce.php?id=<?= $id ?>"><?= htmlspecialchars($a['titre']) ?></a></h2>
                            <span class="mon-annonce__auteur"><?= htmlspecialchars((string) $a['auteur']) ?></span>
                        </div>
                        <span class="mon-annonce__prix"><?= formaterPrix($a['prix']) ?></span>
                    </div>
                    <div class="mon-annonce__etats">
                        <?= pastilleStatutAnnonce($retireeParEquipe ? 'retiree' : $statut) ?>
                        <span class="pastille-etat neutre"><?= htmlspecialchars(libelleEtat($a['etat'])) ?></span>
                        <span class="pastille-etat neutre"><i class="bi bi-<?= MODES_REMISE[$a['mode_remise']][1] ?? 'question' ?>" aria-hidden="true"></i> <?= htmlspecialchars(libelleModeRemise($a['mode_remise'], $a['ville_remise'])) ?></span>
                        <?php if (!(int) $a['livre_valide']): ?>
                            <span class="pastille-etat attente"><i class="bi bi-journal-plus" aria-hidden="true"></i> Nouvelle fiche à créer</span>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array($statut, ['refusee', 'retiree'], true) && $a['motif_moderation']): ?>
                        <p class="mon-annonce__motif"><strong><?= $statut === 'refusee' ? 'Motif du refus' : "Retirée par l'équipe" ?> :</strong> <?= htmlspecialchars($a['motif_moderation']) ?></p>
                    <?php elseif ($statut === 'en_validation'): ?>
                        <p class="mon-annonce__aide">L'équipe vérifie votre annonce : vous recevrez un mail dès sa mise en ligne.</p>
                    <?php endif; ?>
                    <div class="mon-annonce__actions">
                        <?php if (in_array($statut, ANNONCE_STATUTS_MODIFIABLES, true)): ?>
                            <a href="annonce_creer.php?id=<?= $id ?>" class="bouton bouton-contour bouton-petit"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier</a>
                        <?php endif; ?>
                        <?php if (in_array($statut, ['en_validation', 'publiee'], true)): ?>
                            <form action="annonce_action.php" method="POST"
                                  data-confirmer="Retirer l'annonce « <?= htmlspecialchars($a['titre']) ?> » ? Elle ne sera plus visible, vous pourrez la remettre en ligne plus tard."
                                  data-titre-confirmer="Retirer cette annonce" data-bouton-confirmer="Retirer">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="retirer">
                                <input type="hidden" name="id_annonce" value="<?= $id ?>">
                                <button class="bouton bouton-contour bouton-petit"><i class="bi bi-archive" aria-hidden="true"></i> Retirer</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($statut === 'retiree' && !$retireeParEquipe && $raisonInterdiction === ''): ?>
                            <form action="annonce_action.php" method="POST">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="remettre">
                                <input type="hidden" name="id_annonce" value="<?= $id ?>">
                                <button class="bouton bouton-contour bouton-petit"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Remettre en ligne</button>
                            </form>
                        <?php endif; ?>
                        <?php if (!in_array($statut, ['reservee', 'vendue'], true)): ?>
                            <form action="annonce_action.php" method="POST"
                                  data-confirmer="Supprimer définitivement l'annonce « <?= htmlspecialchars($a['titre']) ?> » et ses photos ?"
                                  data-titre-confirmer="Supprimer cette annonce" data-bouton-confirmer="Supprimer">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_annonce" value="<?= $id ?>">
                                <button class="bouton bouton-danger bouton-petit"><i class="bi bi-trash3" aria-hidden="true"></i> Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
