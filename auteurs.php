<?php
require_once 'config.php';
require_once 'auteurs_lib.php';
require_once 'recherche_lib.php';

$recherche = texteRecherche($_GET['q'] ?? '', 80);
$fiches = fichesPubliques($pdo, $recherche);
$titreDocument = 'Nos auteurs';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-auteurs">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="titre-page-compte mb-1">Nos auteurs</h1>
            <p class="text-muted mb-0">Les auteurs qui présentent leurs livres et leurs rencontres sur BIBLIOccaz.</p>
        </div>
        <form method="GET" class="recherche-auteurs" role="search">
            <label for="recherche-auteur" class="visually-hidden">Rechercher un auteur ou un titre</label>
            <input type="search" id="recherche-auteur" name="q" class="form-control" maxlength="80" placeholder="Nom ou titre d'ouvrage" value="<?= htmlspecialchars($recherche) ?>">
            <button type="submit" class="bouton bouton-principal bouton-petit"><i class="bi bi-search" aria-hidden="true"></i> Rechercher</button>
        </form>
    </div>

    <?php if ($recherche !== ''): ?>
        <p class="small text-muted" role="status"><?= count($fiches) ?> résultat<?= count($fiches) > 1 ? 's' : '' ?> pour « <?= htmlspecialchars($recherche) ?> » · <a href="auteurs.php">Voir tous les auteurs</a></p>
    <?php endif; ?>

    <?php if (!$fiches): ?>
        <div class="carte-compte text-center p-5">
            <p class="text-muted mb-0"><?= $recherche !== '' ? 'Aucun auteur ne correspond à cette recherche.' : 'Aucune page auteur n\'est encore publiée.' ?></p>
        </div>
    <?php else: ?>
        <ul class="grille-auteurs">
            <?php foreach ($fiches as $ficheAuteur): ?>
                <?php $photoAuteur = urlPhotoAuteur($ficheAuteur['photo']); ?>
                <li class="carte-auteur">
                    <span class="carte-auteur__portrait" aria-hidden="true">
                        <?php if ($photoAuteur): ?>
                            <img src="<?= htmlspecialchars($photoAuteur) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($ficheAuteur['nom_plume'], 0, 1))) ?>
                        <?php endif; ?>
                    </span>
                    <div class="carte-auteur__texte">
                        <h2><a href="auteur.php?a=<?= rawurlencode($ficheAuteur['slug']) ?>"><?= htmlspecialchars($ficheAuteur['nom_plume']) ?></a></h2>
                        <p><?= htmlspecialchars(extraitBiographie($ficheAuteur['biographie'])) ?></p>
                        <small><?= (int) $ficheAuteur['nb_ouvrages'] ?> ouvrage<?= $ficheAuteur['nb_ouvrages'] > 1 ? 's' : '' ?> présenté<?= $ficheAuteur['nb_ouvrages'] > 1 ? 's' : '' ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
