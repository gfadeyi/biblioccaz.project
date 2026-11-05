<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/scolaire_lib.php';
$niveau = is_string($_GET['niveau'] ?? null) ? mb_substr(trim($_GET['niveau']),0,50) : '';
$matiere = is_string($_GET['matiere'] ?? null) ? mb_substr(trim($_GET['matiere']),0,80) : '';
$options = optionsParcoursScolaire($pdo,$niveau);
$selection = [];
$erreur = '';
if (isset($_GET['niveau']) || isset($_GET['matiere'])) {
    try {
        if ((isset($_GET['niveau']) && (!is_string($_GET['niveau']) || mb_strlen($_GET['niveau'])>50)) || (isset($_GET['matiere']) && (!is_string($_GET['matiere']) || mb_strlen($_GET['matiere'])>80))) { throw new InvalidArgumentException('Choisissez un niveau et une matière valides.'); }
        $selection = livresParcoursScolaire($pdo,$options,$niveau,$matiere); }
    catch (InvalidArgumentException $e) { http_response_code(400); $erreur=$e->getMessage(); }
}
$titreDocument = 'Trouver un manuel scolaire';
include __DIR__ . '/header.php';
?>
<main class="container-xxl py-5 parcours-scolaire">
    <span class="surtitre">Un coup de pouce pour apprendre</span>
    <h1>Le bon livre pour vos études</h1>
    <p class="chapo">Choisissez votre niveau, puis votre matière. Retrouvez les exemplaires disponibles et leur année d’édition.</p>
    <p class="small">Les programmes évoluent : vérifiez l’édition demandée par votre établissement et comparez son ISBN.</p>
    <?php if($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <div class="parcours-scolaire__etapes">
        <form method="get" action="scolaire.php#choix-matiere" class="carte-compte">
            <h2 class="h4">1. Votre niveau</h2>
            <label for="scolaire-niveau" class="form-label">Niveau scolaire</label>
            <select id="scolaire-niveau" name="niveau" class="form-select mb-3" required>
                <option value="">Choisir un niveau</option>
                <?php foreach($options['niveaux'] as $choix): ?><option value="<?= htmlspecialchars($choix) ?>" <?= $choix===$niveau?'selected':'' ?>><?= htmlspecialchars($choix) ?></option><?php endforeach; ?>
            </select>
            <button class="bouton bouton-action" type="submit">Choisir ce niveau</button>
        </form>
        <section class="carte-compte" id="choix-matiere" aria-labelledby="titre-matiere">
            <h2 id="titre-matiere" class="h4">2. Votre matière</h2>
            <?php if(in_array($niveau,$options['niveaux'],true)): ?>
                <form method="get" action="scolaire.php#livres-scolaires">
                    <input type="hidden" name="niveau" value="<?= htmlspecialchars($niveau) ?>">
                    <label for="scolaire-matiere" class="form-label">Matière pour <?= htmlspecialchars($niveau) ?></label>
                    <select id="scolaire-matiere" name="matiere" class="form-select mb-3"><option value="">Toutes les matières de ce niveau</option><?php foreach($options['matieres'] as $choix): ?><option value="<?= htmlspecialchars($choix) ?>" <?= $choix===$matiere?'selected':'' ?>><?= htmlspecialchars($choix) ?></option><?php endforeach; ?></select>
                    <button class="bouton bouton-action" type="submit">Voir les livres disponibles</button>
                </form>
            <?php else: ?><p>Choisissez d’abord votre niveau : les matières correspondantes apparaîtront ici.</p><?php endif; ?>
        </section>
    </div>
    <?php if($niveau!=='' && !$erreur): ?>
        <section id="livres-scolaires" class="lecture-resultats mt-5" aria-labelledby="titre-scolaires">
            <h2 id="titre-scolaires"><?= htmlspecialchars($niveau) ?><?= $matiere!==''?' — '.htmlspecialchars($matiere):' : vos pistes de révision' ?></h2>
            <p>Une première sélection disponible en magasin. Les prix et les stocks sont revérifiés dans le panier.</p>
            <?php if(!$selection): ?><p role="status">Aucun exemplaire disponible pour ce choix pour le moment. Essayez une autre matière ou consultez le rayon complet.</p><?php endif; ?>
            <div class="grille-livres"><?php foreach($selection as $l): ?><div class="selection-scolaire"><p class="selection-scolaire__annee">Édition <?= $l['annee_parution']?(int)$l['annee_parution']:'non renseignée' ?></p><?php include __DIR__.'/partie_carte_livre.php'; ?></div><?php endforeach; ?></div>
            <a class="bouton bouton-contour mt-4" href="<?= htmlspecialchars('catalogue.php?'.http_build_query(['categorie'=>'scolaire-etudes','niveau'=>$niveau,'matiere'=>$matiere,'offre'=>'magasin'])) ?>">Tous les résultats avec ces filtres</a>
        </section>
    <?php endif; ?>
    <p class="mt-4"><a href="catalogue.php?categorie=scolaire-etudes">Explorer tout le rayon Scolaire &amp; Études</a></p>
</main>
<?php include __DIR__.'/footer.php'; ?>
