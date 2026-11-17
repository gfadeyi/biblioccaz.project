<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/boutiques_lib.php';
$rechercheBoutique = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 80) : '';
$villeBoutique = is_string($_GET['ville'] ?? null) && isset(BOUTIQUES[$_GET['ville']]) ? $_GET['ville'] : '';
$boutiques = boutiquesRecherche($rechercheBoutique);
if ($villeBoutique !== '') { $boutiques = array_intersect_key($boutiques, [$villeBoutique => true]); }
$rencontres = nombresRencontresBoutiques($pdo);
$titrePage = 'Nos boutiques';
$descriptionDocument = 'Découvrez le réseau fictif BibliOccaz dans six grandes villes de France et les rencontres autour du livre proposées dans ces villes.';
include __DIR__ . '/header.php';
?>
<div class="container boutiques-page my-4" id="boutiques">
    <nav class="fil-ariane" aria-label="Fil d’Ariane"><a href="./">Accueil</a><span aria-hidden="true">›</span><span aria-current="page">Nos boutiques</span></nav>
    <header class="boutiques-intro">
        <div>
            <p class="boutiques-surtitre">Des livres, des lieux, des liens</p>
            <h1>Votre prochaine histoire<br>commence près de chez vous.</h1>
            <p>Un comptoir pour partager vos lectures, un lieu pour se rencontrer. Découvrez les six villes de notre réseau imaginé pour BibliOccaz.</p>
            <a href="#trouver-boutique" class="bouton bouton-principal"><i class="bi bi-geo-alt" aria-hidden="true"></i> Trouver ma ville</a>
        </div>
        <div class="boutiques-enseigne" aria-hidden="true"><i class="bi bi-shop-window"></i><strong>BIBLIOccaz</strong><span>Le livre continue son histoire.</span><div>Lire · Partager · Se rencontrer</div></div>
    </header>
    <p class="boutiques-demonstration"><i class="bi bi-info-circle" aria-hidden="true"></i><span><strong>Un réseau fictif pour notre projet étudiant.</strong> Ces boutiques ne sont pas des commerces ouverts au public : aucune adresse ni aucun horaire réel n’est annoncé. Les lieux des événements restent indiqués sur leurs fiches.</span></p>
    <section class="boutiques-recherche" id="trouver-boutique" aria-labelledby="titre-trouver-boutique">
        <h2 id="titre-trouver-boutique">Quelle sera votre escale ?</h2>
        <form method="GET" action="boutiques" role="search" aria-label="Rechercher une boutique" class="boutiques-formulaire">
            <div><label for="boutique-recherche">Ville, région ou code postal</label><input class="form-control" id="boutique-recherche" type="search" name="q" maxlength="80" value="<?= htmlspecialchars($rechercheBoutique, ENT_QUOTES, 'UTF-8') ?>" placeholder="Paris, Lyon, 33000…"></div>
            <button class="bouton bouton-principal" type="submit">Rechercher</button>
            <?php if ($rechercheBoutique !== '' || $villeBoutique !== ''): ?><a href="boutiques#trouver-boutique" class="bouton bouton-contour">Toutes les villes</a><?php endif; ?>
        </form>
        <p class="small text-muted mt-2 mb-0">Le code postal permet de retrouver le pôle de votre département ; il ne calcule pas une distance.</p>
    </section>
    <div class="boutiques-resultats" role="status"><?= count($boutiques) ?> ville<?= count($boutiques) > 1 ? 's' : '' ?> dans votre sélection</div>
    <?php if (!$boutiques): ?>
        <div class="carte-compte text-center my-3"><h2 class="h4">Pas encore d’escale ici</h2><p>Notre réseau de démonstration compte six villes. Essayez une autre ville ou découvrez l’ensemble du réseau.</p><a class="bouton bouton-contour" href="boutiques">Voir les six villes</a></div>
    <?php else: ?>
        <div class="boutiques-grille">
            <?php foreach ($boutiques as $slug => $boutique): ?>
                <article class="boutique-carte" id="boutique-<?= $slug ?>" aria-labelledby="titre-boutique-<?= $slug ?>">
                    <div class="boutique-carte__repere"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?= htmlspecialchars($boutique['region']) ?></span><span class="boutique-carte__code"><?= $boutique['postal'] ?></span></div>
                    <h2 id="titre-boutique-<?= $slug ?>"><?= htmlspecialchars($boutique['ville']) ?></h2>
                    <p class="boutique-carte__accroche"><?= htmlspecialchars($boutique['accroche']) ?></p>
                    <?php if ($rencontres !== null): ?><p class="boutique-carte__agenda"><i class="bi bi-calendar-event" aria-hidden="true"></i> <?= $rencontres[$slug] > 0 ? $rencontres[$slug] . ' rencontre' . ($rencontres[$slug] > 1 ? 's' : '') . ' à venir' : 'Aucune rencontre à venir pour le moment' ?></p><?php endif; ?>
                    <a class="bouton bouton-contour" href="evenements?ville=<?= $slug ?>" aria-label="Voir les rencontres autour de <?= htmlspecialchars($boutique['ville'], ENT_QUOTES, 'UTF-8') ?>">Voir les rencontres <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($rencontres === null): ?><p role="status" class="mt-3">L’agenda est temporairement indisponible. Les informations sur les villes restent accessibles.</p><?php endif; ?>
    <section class="boutiques-suite carte-compte mt-4" aria-labelledby="titre-boutiques-suite"><h2 id="titre-boutiques-suite">Et une fois votre livre trouvé ?</h2><p>Choisissez votre boutique sur la fiche du livre pour consulter ses exemplaires et réserver. Retrouvez ensuite votre code et votre date limite dans « Mes emprunts ». Le prêt commence au retrait, validé par l’équipe ; rapportez le livre dans la même boutique. Si tous ses exemplaires sont empruntés, rejoignez sa liste d’attente. Aucun transfert entre villes n’est proposé.</p><div class="d-flex flex-wrap gap-2"><a class="bouton bouton-principal" href="catalogue?offre=emprunt">Livres à emprunter</a><a class="bouton bouton-contour" href="mes_emprunts">Mes emprunts</a><a class="bouton bouton-contour" href="service_client?categorie=emprunt">Une question ?</a></div></section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
