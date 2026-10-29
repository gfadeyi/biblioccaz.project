<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'compte_lib.php';
require_once 'recommandations_lib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$idUser = (int) $_SESSION['user_id'];
$roleLecteur = $_SESSION['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!verifierCsrf()) {
        $_SESSION['flash_suggestions'] = ['danger', "Votre session a expiré, merci de réessayer."];
    } else {
        try {
            if ($action === 'effacer_historique') {
                effacerHistorique($pdo, $idUser);
                insertLog('PROFIL', "Historique de consultation effacé");
                $_SESSION['flash_suggestions'] = ['success', "Votre historique de consultation est effacé."];
            } elseif ($action === 'historique') {
                $activer = ($_POST['actif'] ?? '') === '1';
                definirHistoriqueActif($pdo, $idUser, $activer);
                insertLog('PROFIL', $activer ? "Historique de consultation réactivé" : "Historique de consultation suspendu");
                $_SESSION['flash_suggestions'] = ['success', $activer ? "L'historique est réactivé : les fiches consultées affineront vos suggestions." : "L'historique est suspendu : les fiches consultées ne sont plus enregistrées ni utilisées."];
            }
        } catch (PDOException $e) {
            $_SESSION['flash_suggestions'] = ['danger', "Cette option n'est pas encore disponible."];
        }
    }
    header("Location: suggestions.php#historique");
    exit();
}

$autorise = in_array($roleLecteur, ROLES_SUGGESTIONS, true);
$suggestions = $autorise ? recommandationsPour($pdo, $idUser) : [];
$personnalise = $autorise && !donneesRecommandation($pdo, $idUser)['repli'];
$historiqueEnregistre = $autorise && historiqueActif($pdo, $idUser);
$historique = $autorise ? historiqueConsultations($pdo, $idUser) : [];
$flashSuggestions = $_SESSION['flash_suggestions'] ?? null;
unset($_SESSION['flash_suggestions']);
$csrf = jetonCsrf();

include 'header.php';
echo etoilesStyle();
?>

<div class="container mt-4 mb-5 page-suggestions">
    <h1 class="titre-page-compte"><?= iconeDessin('lunettes') ?> Suggestions pour vous</h1>

    <?php if ($flashSuggestions): ?>
        <div class="alert alert-<?= $flashSuggestions[0] === 'success' ? 'success' : 'danger' ?> rounded-3" role="status"><?= htmlspecialchars($flashSuggestions[1]) ?></div>
    <?php endif; ?>

    <?php if (!$autorise): ?>
        <div class="carte-compte">
            <p class="texte-profil mb-0">Les suggestions personnalisées sont réservées aux comptes lecteurs et auteurs.</p>
        </div>
    <?php else: ?>
        <p class="page-suggestions__intro">
            <?php if ($personnalise): ?>
                Une sélection de livres disponibles choisie d'après vos préférences, vos lectures et vos notes. Sous chaque livre, nous vous disons pourquoi.
            <?php else: ?>
                Nous ne connaissons pas encore vos goûts : voici les livres disponibles les plus appréciés.
                <a href="modifier_profil.php#preferences">Indiquez vos préférences de lecture</a> pour une sélection qui vous ressemble.
            <?php endif; ?>
        </p>

        <?php if ($suggestions): ?>
            <div class="grille-suggestions">
                <?php foreach ($suggestions as $l): include 'partie_carte_livre.php'; endforeach; ?>
            </div>
        <?php else: ?>
            <div class="carte-compte">
                <p class="texte-profil mb-2">Aucun livre à vous proposer pour le moment : vous avez peut-être déjà tout lu !</p>
                <a href="catalogue.php" class="lien-voir-tout">Parcourir le catalogue <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
        <?php endif; ?>

        <div class="grille-profil mt-4">
            <section class="carte-compte" id="historique" aria-labelledby="titre-historique">
                <h2 class="carte-compte__titre" id="titre-historique"><?= iconeDessin('marque-page') ?> Vu récemment</h2>
                <?php if (!$historiqueEnregistre): ?>
                    <p class="texte-profil">L'historique est suspendu : les fiches que vous consultez ne sont ni enregistrées ni utilisées.</p>
                <?php elseif (!$historique): ?>
                    <p class="texte-profil">Les <?= HISTORIQUE_TAILLE_MAX ?> dernières fiches que vous consultez apparaîtront ici.</p>
                <?php endif; ?>
                <?php if ($historique): ?>
                    <ul class="historique-consultation">
                        <?php foreach ($historique as $consulte): ?>
                            <li>
                                <a href="detail_livre.php?id=<?= (int) $consulte['id_livre'] ?>">
                                    <img src="<?= htmlspecialchars(urlImage($consulte['couverture'] ?: 'default.png')) ?>" alt="" loading="lazy">
                                    <span><?= htmlspecialchars($consulte['titre']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="historique-consultation__actions">
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="historique">
                        <input type="hidden" name="actif" value="<?= $historiqueEnregistre ? '0' : '1' ?>">
                        <button class="bouton bouton-contour bouton-petit"><i class="bi bi-<?= $historiqueEnregistre ? 'pause-circle' : 'play-circle' ?>" aria-hidden="true"></i> <?= $historiqueEnregistre ? "Suspendre l'historique" : "Réactiver l'historique" ?></button>
                    </form>
                    <?php if ($historique): ?>
                        <form method="POST" data-confirmer="Effacer les fiches consultées ? Vos suggestions ne les prendront plus en compte." data-titre-confirmer="Effacer mon historique" data-bouton-confirmer="Effacer">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="effacer_historique">
                            <button class="bouton bouton-discret bouton-petit"><i class="bi bi-trash3" aria-hidden="true"></i> Effacer mon historique</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="carte-compte" aria-labelledby="titre-fonctionnement">
                <h2 class="carte-compte__titre" id="titre-fonctionnement"><?= iconeDessin('loupe') ?> Comment nous choisissons</h2>
                <ul class="liste-explications">
                    <li>Vos <a href="modifier_profil.php#preferences">préférences de lecture</a>, si vous en avez indiqué.</li>
                    <li>Vos achats, emprunts, favoris et les notes de vos avis.</li>
                    <li>Les <?= HISTORIQUE_TAILLE_MAX ?> dernières fiches consultées, gardées <?= HISTORIQUE_DUREE_JOURS ?> jours au plus.</li>
                    <li>La popularité et la note moyenne de chaque livre.</li>
                </ul>
                <p class="texte-profil mb-0">Vos préférences et votre historique restent dans votre compte : ils ne sont montrés ni aux autres membres ni à l'équipe, et ne sont jamais transmis à des tiers. Les livres que vous avez déjà achetés ou empruntés, et les vôtres, ne vous sont pas proposés.</p>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
