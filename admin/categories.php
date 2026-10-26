<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../categories_lib.php';
exigerDroit('admin');

const ICONES_UNIVERS = [
    'bulle' => 'Bulle de BD', 'livre-ouvert' => 'Livre ouvert', 'plume' => 'Plume', 'mortier' => 'Toque d\'étudiant',
    'lunettes' => 'Lunettes', 'tasse' => 'Tasse', 'etiquette' => 'Étiquette', 'pile' => 'Pile de livres',
    'marque-page' => 'Marque-page', 'loupe' => 'Loupe',
];
const FAMILLES_TAGS_COURTES = ['theme' => 'Thème', 'public' => 'Public', 'particularite' => 'Particularité'];
const COULEURS_UNIVERS = [
    'bd' => 'Violet', 'litterature' => 'Vert', 'art' => 'Framboise', 'scolaire' => 'Bleu', 'savoirs' => 'Bleu-vert', 'pratique' => 'Sauge',
];

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('categories.php');
    $type = $_POST['type'] ?? '';
    $action = $_POST['action'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $idsUnivers = array_map('intval', $pdo->query("SELECT id_categorie FROM categorie WHERE id_parent IS NULL")->fetchAll(PDO::FETCH_COLUMN));
    $idParent = (int) ($_POST['id_parent'] ?? 0);
    $idParent = in_array($idParent, $idsUnivers, true) && $idParent !== $id ? $idParent : null;
    $icone = array_key_exists($_POST['icone'] ?? '', ICONES_UNIVERS) ? $_POST['icone'] : 'livre-ouvert';
    $couleur = array_key_exists($_POST['couleur'] ?? '', COULEURS_UNIVERS) ? $_POST['couleur'] : 'litterature';
    $description = mb_substr(trim($_POST['description'] ?? ''), 0, 255);
    $nomValide = $nom !== '' && mb_strlen($nom) <= 100;
    $familleTag = array_key_exists($_POST['famille'] ?? '', FAMILLES_TAGS) ? $_POST['famille'] : 'theme';

    try {
        if ($type === 'categorie' && $action === 'ajouter') {
            if (!$nomValide) {
                $erreur = "Le nom doit contenir entre 1 et 100 caractères.";
            } else {
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordre), 0) + 1 FROM categorie WHERE id_parent <=> ?");
                $stmt->execute([$idParent]);
                $ordre = (int) $stmt->fetchColumn();
                $pdo->prepare("INSERT INTO categorie (nom, slug, ordre, id_parent, icone, couleur, description) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$nom, creerSlug($nom), $ordre, $idParent, $idParent ? null : $icone, $idParent ? null : $couleur, $idParent ? null : ($description ?: null)]);
                insertLog('CATALOGUE', ($idParent ? "Ajout catégorie" : "Ajout univers") . " : $nom");
                $message = ($idParent ? "Catégorie" : "Univers") . " « $nom » ajouté" . ($idParent ? 'e' : '') . ".";
            }
        } elseif ($type === 'univers' && $action === 'modifier') {
            if (!$nomValide) {
                $erreur = "Le nom doit contenir entre 1 et 100 caractères.";
            } else {
                $pdo->prepare("UPDATE categorie SET nom = ?, icone = ?, couleur = ?, description = ?, ordre = ? WHERE id_categorie = ? AND id_parent IS NULL")
                    ->execute([$nom, $icone, $couleur, $description ?: null, max(0, (int) ($_POST['ordre'] ?? 0)), $id]);
                insertLog('CATALOGUE', "Modification univers #$id : $nom");
                $message = "Univers « $nom » enregistré.";
            }
        } elseif ($type === 'categorie' && $action === 'modifier') {
            if (!$nomValide) {
                $erreur = "Le nom doit contenir entre 1 et 100 caractères.";
            } elseif (!$idParent) {
                $erreur = "Choisissez l'univers de la catégorie.";
            } else {
                $pdo->prepare("UPDATE categorie SET nom = ?, id_parent = ?, ordre = ? WHERE id_categorie = ? AND id_parent IS NOT NULL")
                    ->execute([$nom, $idParent, max(0, (int) ($_POST['ordre'] ?? 0)), $id]);
                insertLog('CATALOGUE', "Modification catégorie #$id : $nom");
                $message = "Catégorie « $nom » enregistrée.";
            }
        } elseif (in_array($type, ['univers', 'categorie'], true) && $action === 'supprimer') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorie WHERE id_parent = ?");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $erreur = "Cet univers contient encore des catégories : déplacez-les ou supprimez-les d'abord.";
            } else {
                $pdo->prepare("DELETE FROM categorie WHERE id_categorie = ?")->execute([$id]);
                insertLog('CATALOGUE', "Suppression $type #$id");
                $message = "Suppression effectuée.";
            }
        } elseif ($type === 'tag' && ($action === 'ajouter' || $action === 'renommer')) {
            if (!$nomValide) {
                $erreur = "Le nom doit contenir entre 1 et 100 caractères.";
            } elseif ($action === 'ajouter') {
                $pdo->prepare("INSERT INTO tag (nom, slug, famille, ordre)
                               SELECT ?, ?, ?, COALESCE(MAX(ordre), 0) + 1 FROM tag WHERE famille = ?")
                    ->execute([$nom, creerSlug($nom), $familleTag, $familleTag]);
                insertLog('CATALOGUE', "Ajout tag : $nom");
                $message = "Tag « $nom » ajouté dans « " . FAMILLES_TAGS[$familleTag] . " ».";
            } else {
                $pdo->prepare("UPDATE tag SET nom = ?, famille = ? WHERE id_tag = ?")->execute([$nom, $familleTag, $id]);
                insertLog('CATALOGUE', "Renommage tag #$id : $nom");
                $message = "Modification enregistrée.";
            }
        } elseif ($type === 'tag' && $action === 'supprimer') {
            $pdo->prepare("DELETE FROM tag WHERE id_tag = ?")->execute([$id]);
            insertLog('CATALOGUE', "Suppression tag #$id");
            $message = "Suppression effectuée.";
        } else {
            $erreur = "Action inconnue.";
        }
    } catch (PDOException $e) {
        $erreur = $e->getCode() == 23000 ? "Ce nom existe déjà." : "Erreur : " . $e->getMessage();
    }
    if ($erreur === '') {
        rediriger('categories.php', 'success', $message);
    }
}

$livresParCategorie = $pdo->query("SELECT id_categorie, COUNT(*) FROM livre WHERE id_categorie IS NOT NULL GROUP BY id_categorie")->fetchAll(PDO::FETCH_KEY_PAIR);
$arbre = arbreCategories($pdo);
$tags = $pdo->query("
    SELECT t.*, COUNT(lt.id_livre) AS nb_livres
    FROM tag t LEFT JOIN livre_tag lt ON lt.id_tag = t.id_tag
    GROUP BY t.id_tag ORDER BY FIELD(t.famille, 'theme', 'public', 'particularite'), t.ordre, t.nom")->fetchAll();
$famillesTags = tagsParFamille($tags);
$sansCategorie = (int) $pdo->query("SELECT COUNT(*) FROM livre WHERE id_categorie IS NULL")->fetchColumn();

$titrePage = 'Univers, catégories & tags';
$sousTitrePage = 'Les rayons du site : chaque univers regroupe des catégories, les tags précisent les thèmes.';
$pageActive = 'categories';
$totalLivresClasses = array_sum($livresParCategorie);
include __DIR__ . '/_haut.php';

function nombreLivres(int $nombre): string
{
    return '<span class="etiquette etiquette-compteur ' . ($nombre === 0 ? 'etiquette-neutre' : '') . '">' . $nombre . ' livre' . ($nombre > 1 ? 's' : '') . '</span>';
}

function boutonSupprimer(string $type, array $element, string $avertissement): void { ?>
    <form method="POST" data-confirmer="<?= e($avertissement) ?>" data-bouton-confirmer="Supprimer">
        <?= champCsrf() ?>
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" value="<?= (int) $element['id_categorie'] ?>">
        <button class="btn btn-outline-danger bouton-action" title="Supprimer"><i class="bi bi-trash" aria-hidden="true"></i></button>
    </form>
<?php }
?>

<?php if ($erreur): ?><div class="alert alert-danger"><i class="bi bi-x-octagon-fill" aria-hidden="true"></i><span><?= e($erreur) ?></span></div><?php endif; ?>
<?php if ($sansCategorie > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <span><?= $sansCategorie ?> livre<?= $sansCategorie > 1 ? 's n\'ont' : ' n\'a' ?> pas de catégorie. Classez-les depuis <a href="inventaire.php">l'inventaire</a> (bouton « Modifier la fiche »).</span>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <span class="etiquette etiquette-neutre"><?= count($arbre) ?> univers</span>
                <span class="etiquette etiquette-neutre"><?= array_sum(array_map(fn($u) => count($u['categories']), $arbre)) ?> catégories</span>
                <span class="etiquette"><?= $totalLivresClasses ?> livres classés</span>
            </div>
            <button class="btn btn-vert btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#ajoutUnivers" aria-expanded="false" aria-controls="ajoutUnivers"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Nouvel univers</button>
        </div>

        <div class="collapse mb-3" id="ajoutUnivers">
            <div class="carte carte-corps">
                <h2 class="h6 fw-bold mb-1">Ajouter un univers</h2>
                <p class="text-muted small mb-3">Un univers est un grand rayon du menu (ex. Littérature). Les livres se classent dans ses catégories.</p>
                <form method="POST" class="row g-2 align-items-end">
                    <?= champCsrf() ?>
                    <input type="hidden" name="type" value="categorie">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="col-md-4"><label class="form-label small mb-1" for="nouvelUnivers">Nom</label><input type="text" id="nouvelUnivers" name="nom" class="form-control form-control-sm" maxlength="100" required></div>
                    <div class="col-md-3"><label class="form-label small mb-1" for="nouvelleIcone">Icône</label>
                        <select id="nouvelleIcone" name="icone" class="form-select form-select-sm"><?php foreach (ICONES_UNIVERS as $cle => $libelle): ?><option value="<?= $cle ?>"><?= e($libelle) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label small mb-1" for="nouvelleCouleur">Couleur</label>
                        <select id="nouvelleCouleur" name="couleur" class="form-select form-select-sm"><?php foreach (COULEURS_UNIVERS as $cle => $libelle): ?><option value="<?= $cle ?>"><?= e($libelle) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><button class="btn btn-vert btn-sm w-100">Ajouter</button></div>
                    <div class="col-12"><input type="text" name="description" class="form-control form-control-sm" maxlength="255" placeholder="Courte description affichée sur l'accueil (ex. Romans, polars, SF, classiques)" aria-label="Description"></div>
                </form>
            </div>
        </div>

        <?php foreach ($arbre as $univers): ?>
            <?php
            $idUnivers = (int) $univers['id_categorie'];
            $nbLivresUnivers = (int) ($livresParCategorie[$idUnivers] ?? 0) + array_sum(array_map(fn($c) => (int) ($livresParCategorie[$c['id_categorie']] ?? 0), $univers['categories']));
            ?>
            <section class="carte mb-3 univers-<?= e($univers['couleur'] ?: 'litterature') ?>" aria-labelledby="univers<?= $idUnivers ?>">
                <div class="entete-univers">
                    <span class="icone-univers-admin"><svg class="icone-dessin" aria-hidden="true" focusable="false"><use href="../img/icones.svg?v=<?= (int) @filemtime(__DIR__ . '/../img/icones.svg') ?>#d-<?= e($univers['icone'] ?: 'livre-ouvert') ?>"></use></svg></span>
                    <div class="flex-grow-1 min-w-0">
                        <h2 id="univers<?= $idUnivers ?>"><?= e($univers['nom']) ?></h2>
                        <p class="mb-0 small text-muted"><?= e($univers['description'] ?? '') ?></p>
                    </div>
                    <span class="d-flex flex-wrap gap-1">
                        <span class="etiquette etiquette-neutre"><?= count($univers['categories']) ?> catégorie<?= count($univers['categories']) > 1 ? 's' : '' ?></span>
                        <?= nombreLivres($nbLivresUnivers) ?>
                    </span>
                    <span class="actions-ligne">
                        <button class="btn btn-outline-secondary bouton-action" type="button" data-bs-toggle="collapse" data-bs-target="#edition<?= $idUnivers ?>" aria-expanded="false" aria-controls="edition<?= $idUnivers ?>" title="Modifier l'univers"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                        <?php if (!$univers['categories']): ?>
                            <?php boutonSupprimer('univers', $univers, "Supprimer l'univers « " . $univers['nom'] . " » ?"); ?>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="collapse" id="edition<?= $idUnivers ?>">
                    <form method="POST" class="row g-2 align-items-end edition-univers">
                        <?= champCsrf() ?>
                        <input type="hidden" name="type" value="univers">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id" value="<?= $idUnivers ?>">
                        <div class="col-md-4"><label class="form-label small mb-1" for="nomUnivers<?= $idUnivers ?>">Nom</label><input type="text" id="nomUnivers<?= $idUnivers ?>" name="nom" class="form-control form-control-sm" value="<?= e($univers['nom']) ?>" maxlength="100" required></div>
                        <div class="col-md-3"><label class="form-label small mb-1" for="iconeUnivers<?= $idUnivers ?>">Icône</label>
                            <select id="iconeUnivers<?= $idUnivers ?>" name="icone" class="form-select form-select-sm"><?php foreach (ICONES_UNIVERS as $cle => $libelle): ?><option value="<?= $cle ?>" <?= $univers['icone'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><label class="form-label small mb-1" for="couleurUnivers<?= $idUnivers ?>">Couleur</label>
                            <select id="couleurUnivers<?= $idUnivers ?>" name="couleur" class="form-select form-select-sm"><?php foreach (COULEURS_UNIVERS as $cle => $libelle): ?><option value="<?= $cle ?>" <?= $univers['couleur'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-1"><label class="form-label small mb-1" for="ordreUnivers<?= $idUnivers ?>">Ordre</label><input type="number" id="ordreUnivers<?= $idUnivers ?>" name="ordre" min="0" class="form-control form-control-sm" value="<?= (int) $univers['ordre'] ?>"></div>
                        <div class="col-md-2"><button class="btn btn-vert btn-sm w-100">Enregistrer</button></div>
                        <div class="col-12"><input type="text" name="description" class="form-control form-control-sm" maxlength="255" value="<?= e($univers['description'] ?? '') ?>" placeholder="Courte description" aria-label="Description"></div>
                    </form>
                </div>

                <?php if ($univers['categories']): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th scope="col" style="width: 34%">Catégorie</th><th scope="col" style="width: 30%">Univers</th><th scope="col" style="width: 84px">Ordre</th><th scope="col" class="text-center">Livres</th><th scope="col" class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($univers['categories'] as $categorie): ?>
                                    <?php $idFormulaire = 'categorie' . (int) $categorie['id_categorie']; ?>
                                    <tr>
                                        <td><input form="<?= $idFormulaire ?>" type="text" name="nom" class="form-control form-control-sm" value="<?= e($categorie['nom']) ?>" maxlength="100" required aria-label="Nom de la catégorie"></td>
                                        <td>
                                            <select form="<?= $idFormulaire ?>" name="id_parent" class="form-select form-select-sm" aria-label="Univers de « <?= e($categorie['nom']) ?> »">
                                                <?php foreach ($arbre as $choixUnivers): ?>
                                                    <option value="<?= (int) $choixUnivers['id_categorie'] ?>" <?= (int) $choixUnivers['id_categorie'] === (int) $categorie['id_parent'] ? 'selected' : '' ?>><?= e($choixUnivers['nom']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td style="width: 90px"><input form="<?= $idFormulaire ?>" type="number" name="ordre" min="0" class="form-control form-control-sm" value="<?= (int) $categorie['ordre'] ?>" aria-label="Ordre"></td>
                                        <td class="text-center"><?= nombreLivres((int) ($livresParCategorie[$categorie['id_categorie']] ?? 0)) ?></td>
                                        <td class="text-end">
                                            <span class="actions-ligne">
                                                <form method="POST" id="<?= $idFormulaire ?>">
                                                    <?= champCsrf() ?>
                                                    <input type="hidden" name="type" value="categorie">
                                                    <input type="hidden" name="action" value="modifier">
                                                    <input type="hidden" name="id" value="<?= (int) $categorie['id_categorie'] ?>">
                                                    <button class="btn btn-outline-secondary bouton-action" title="Enregistrer les changements"><i class="bi bi-check-lg" aria-hidden="true"></i></button>
                                                </form>
                                                <?php boutonSupprimer('categorie', $categorie, "Supprimer « " . $categorie['nom'] . " » ? Les livres concernés seront simplement retirés de ce classement."); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="etat-vide py-3"><strong>Aucune catégorie</strong>Ajoutez-en une ci-dessous.</div>
                <?php endif; ?>

                <form method="POST" class="d-flex gap-2 ajout-categorie">
                    <?= champCsrf() ?>
                    <input type="hidden" name="type" value="categorie">
                    <input type="hidden" name="action" value="ajouter">
                    <input type="hidden" name="id_parent" value="<?= $idUnivers ?>">
                    <input type="text" name="nom" class="form-control form-control-sm" placeholder="Nouvelle catégorie dans <?= e($univers['nom']) ?>…" maxlength="100" required aria-label="Nouvelle catégorie dans <?= e($univers['nom']) ?>">
                    <button class="btn btn-outline-success btn-sm text-nowrap"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter</button>
                </form>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="col-xl-4">
        <div class="carte">
            <div class="p-4">
                <h2 class="h5 fw-bold mb-1">Tags</h2>
                <p class="text-muted small mb-3">Filtres complémentaires du catalogue. Ils ne répètent jamais une catégorie.</p>
                <form method="POST" class="ajout-tag mb-3">
                    <?= champCsrf() ?>
                    <input type="hidden" name="type" value="tag">
                    <input type="hidden" name="action" value="ajouter">
                    <input type="text" name="nom" class="form-control form-control-sm" placeholder="Nouveau tag…" maxlength="100" required aria-label="Nom du nouveau tag">
                    <select name="famille" class="form-select form-select-sm" aria-label="Famille du nouveau tag">
                        <?php foreach (FAMILLES_TAGS as $cleFamille => $nomFamille): ?>
                            <option value="<?= $cleFamille ?>"><?= e($nomFamille) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-success btn-sm text-nowrap"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter</button>
                </form>
                <?php if (!$tags): ?>
                    <div class="etat-vide"><i class="bi bi-tags" aria-hidden="true"></i><p>Aucun tag pour l'instant.</p></div>
                <?php endif; ?>
                <?php foreach ($famillesTags as $cleFamille => $tagsFamille): ?>
                    <h3 class="titre-famille-tags"><?= e(FAMILLES_TAGS[$cleFamille]) ?> <span class="etiquette etiquette-neutre"><?= count($tagsFamille) ?></span></h3>
                    <?php foreach ($tagsFamille as $tag): ?>
                        <?php $idFormulaireTag = 'tag' . (int) $tag['id_tag']; ?>
                        <div class="ligne-tag">
                            <form method="POST" id="<?= $idFormulaireTag ?>">
                                <?= champCsrf() ?>
                                <input type="hidden" name="type" value="tag">
                                <input type="hidden" name="action" value="renommer">
                                <input type="hidden" name="id" value="<?= (int) $tag['id_tag'] ?>">
                            </form>
                            <input type="text" name="nom" form="<?= $idFormulaireTag ?>" class="form-control form-control-sm nom-tag" value="<?= e($tag['nom']) ?>" maxlength="100" required aria-label="Nom du tag">
                            <select name="famille" form="<?= $idFormulaireTag ?>" class="form-select form-select-sm" aria-label="Famille du tag <?= e($tag['nom']) ?>">
                                <?php foreach (FAMILLES_TAGS_COURTES as $cleOption => $nomFamille): ?>
                                    <option value="<?= $cleOption ?>" <?= $cleOption === $tag['famille'] ? 'selected' : '' ?>><?= e($nomFamille) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= nombreLivres((int) $tag['nb_livres']) ?>
                            <button form="<?= $idFormulaireTag ?>" class="btn btn-outline-secondary bouton-action" title="Enregistrer"><i class="bi bi-check-lg" aria-hidden="true"></i></button>
                            <form method="POST" data-confirmer="Supprimer le tag « <?= e($tag['nom']) ?> » ? Il sera retiré des <?= (int) $tag['nb_livres'] ?> livre(s) concerné(s)." data-bouton-confirmer="Supprimer">
                                <?= champCsrf() ?>
                                <input type="hidden" name="type" value="tag">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int) $tag['id_tag'] ?>">
                                <button class="btn btn-outline-danger bouton-action" title="Supprimer"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
