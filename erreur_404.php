<?php
require_once 'config.php';

http_response_code(404);
$adresseBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
$titreDocument = 'Page introuvable';

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--large text-center page-introuvable">
        <p class="page-introuvable__code" aria-hidden="true">404</p>
        <h1>Ce livre n'est pas dans nos rayons</h1>
        <p class="carte-formulaire__intro">La page que vous cherchez a peut-être été déplacée, retirée du catalogue ou n'a jamais existé.</p>

        <form action="catalogue.php" method="GET" class="page-introuvable__recherche" role="search">
            <label for="recherche-404" class="visually-hidden">Rechercher un livre</label>
            <input type="search" id="recherche-404" name="q" class="form-control" placeholder="Titre, auteur, ISBN…">
            <button type="submit" class="bouton bouton-principal"><?= iconeDessin('loupe') ?> Rechercher</button>
        </form>

        <div class="page-introuvable__liens">
            <a href="index.php" class="bouton bouton-contour bouton-petit">Retour à l'accueil</a>
            <a href="catalogue.php" class="bouton bouton-contour bouton-petit">Voir le catalogue</a>
            <a href="dons.php" class="bouton bouton-contour bouton-petit">Livres à donner</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
