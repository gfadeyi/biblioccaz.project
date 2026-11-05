<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lecture_lib.php';
$choix = choixLecture($pdo);
$erreur = '';
$selection = [];
$demande = isset($_GET['selection']);
try {
    $criteres = criteresLecture($_GET, $choix);
    if ($demande) { $selection = selectionLecture($pdo, $criteres, graineDecouverte()); }
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    $erreur = $e->getMessage();
    $criteres = ['univers' => '', 'ambiance' => '', 'budget' => ''];
}
$titrePage = 'Trouver ma prochaine lecture';
include __DIR__ . '/header.php';
?>
<main class="container-xxl py-5">
    <div class="entete-section mb-4">
        <div>
            <span class="surtitre">Une envie, quelques livres</span>
            <h1>Trouver ma prochaine lecture</h1>
            <p class="chapo">Choisissez un univers, une ambiance et un budget. Nous croisons vos envies avec les livres disponibles en magasin.</p>
        </div>
    </div>
    <?php if ($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <form method="get" action="trouver_lecture.php#selection" class="lecture-choix mb-5">
        <input type="hidden" name="selection" value="1">
        <div class="row g-3 align-items-end">
            <?php foreach (['univers' => '1. Quel univers ?', 'ambiance' => '2. Quelle ambiance ?'] as $cle => $libelle): ?>
                <div class="col-md-4">
                    <label for="lecture-<?= $cle ?>" class="form-label fw-semibold"><?= $libelle ?></label>
                    <select id="lecture-<?= $cle ?>" name="<?= $cle ?>" class="form-select">
                        <option value="">Je me laisse surprendre</option>
                        <?php foreach ($choix[$cle] as $option): ?>
                            <option value="<?= htmlspecialchars($option['slug']) ?>" <?= $criteres[$cle] === $option['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($option['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="col-md-4">
                <label for="lecture-budget" class="form-label fw-semibold">3. Quel budget par livre ?</label>
                <select id="lecture-budget" name="budget" class="form-select" aria-describedby="lecture-prix">
                    <?php foreach (BUDGETS_LECTURE as $valeur => $libelle): ?>
                        <option value="<?= $valeur ?>" <?= $criteres['budget'] === (string) $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                <button class="bouton bouton-action" type="submit">Découvrir ma sélection <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                <a href="trouver_lecture.php" class="lien-voir-tout">Recommencer</a>
                <p id="lecture-prix" class="small mb-0">Prix du livre, hors livraison et remises. Aucun compte nécessaire.</p>
            </div>
        </div>
    </form>
    <?php if ($demande && !$erreur): ?>
        <section id="selection" aria-labelledby="titre-selection" class="lecture-resultats">
            <div class="entete-section">
                <div><h2 id="titre-selection">Votre piste de lecture</h2><p>Les critères choisis sont tous respectés. Un livre par série pour varier les découvertes.</p></div>
                <a href="<?= htmlspecialchars(lienCatalogueLecture($criteres)) ?>" class="lien-voir-tout">Voir tous les résultats <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <?php if ($selection): ?>
                <div class="grille-livres"><?php foreach ($selection as $l): include __DIR__ . '/partie_carte_livre.php'; endforeach; ?></div>
                <p class="small mt-3">Disponibilités et prix vérifiés lors de la recherche, puis contrôlés à nouveau dans le panier. Le choix reste stable pendant votre visite tant que les offres disponibles ne changent pas.</p>
            <?php else: ?>
                <div class="alert alert-secondary"><h3 class="h5">Pas de livre disponible avec cette combinaison</h3><p class="mb-0">Essayez une autre ambiance ou un budget plus large. Aucun livre hors de vos critères n’a été ajouté.</p></div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <p>Envie de flâner ? <a href="catalogue.php">Parcourez tout le catalogue</a>. Pour donner ou emprunter, retrouvez les offres dans le catalogue.</p>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
