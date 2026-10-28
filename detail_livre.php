<?php
require_once 'config.php';
require_once 'livres_lib.php';
include 'header.php';

$id = $_GET['id'] ?? null;
$offre_index = $_GET['offre'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (!$livre) {
    echo "<div class='container mt-5'>Livre introuvable.</div>";
    include 'footer.php'; exit;
}

$stmtEx = $pdo->prepare("SELECT id_exemplaire, etat, prix, quantite FROM exemplaire e WHERE e.id_livre = ? AND " . conditionOffreMagasin() . " ORDER BY prix ASC");
$stmtEx->execute([$id]);
$offres = $stmtEx->fetchAll();

$offre_selectionnee = $offres[$offre_index] ?? null;

require_once 'compte_lib.php';
require_once 'avis_lib.php';
$idUserConnecte = (int) ($_SESSION['user_id'] ?? 0);
$stockTotal = array_sum(array_map(fn($o) => (int) $o['quantite'], $offres));
$enFavori = false;
$alerteActive = false;
try {
    if ($idUserConnecte) {
        $enFavori = estFavori($pdo, $idUserConnecte, (int) $livre['id_livre']);
        $alerteActive = alerteEnAttente($pdo, $idUserConnecte, (int) $livre['id_livre']);
    }
} catch (PDOException $e) {
}
$flashCompte = $_SESSION['flash_compte'] ?? null;
unset($_SESSION['flash_compte']);

function formulaireCompte(string $action, int $idLivre, string $contenu, string $classe, string $titre = ''): string
{
    return '<form action="compte_action.php" method="POST" class="d-inline">'
        . '<input type="hidden" name="csrf" value="' . jetonCsrf() . '">'
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<input type="hidden" name="id_livre" value="' . $idLivre . '">'
        . '<button class="' . $classe . '"' . ($titre ? ' title="' . htmlspecialchars($titre) . '" aria-label="' . htmlspecialchars($titre) . '"' : '') . '>' . $contenu . '</button></form>';
}

function formaterEtat($etat) {
    $e = strtolower($etat);
    if ($e == 'tres bon' || $e == 'très bon') return 'Très bon';
    if ($e == 'bon') return 'Bon';
    if ($e == 'neuf') return 'Neuf';
    if ($e == 'use' || $e == 'usé') return 'Usé';
    return ucfirst($e);
}

$toutesOffres = [];
foreach ($offres as $index => $offre) {
    $toutesOffres[] = ['origine' => 'magasin', 'etat' => $offre['etat'], 'prix' => (float) $offre['prix'], 'index' => $index,
                       'detail' => $offre['quantite'] . ' en stock', 'id_exemplaire' => (int) $offre['id_exemplaire']];
}
try {
    $stmtAnnoncesLivre = $pdo->prepare("
        SELECT e.id_exemplaire, e.etat, e.prix, e.mode_remise, e.ville_remise, u.pseudo
        FROM exemplaire e INNER JOIN user u ON u.id = e.id_proprietaire
        WHERE e.id_livre = ? AND " . conditionAnnoncePubliee());
    $stmtAnnoncesLivre->execute([$id]);
    require_once 'annonces_lib.php';
    foreach ($stmtAnnoncesLivre->fetchAll() as $annonceLivre) {
        $toutesOffres[] = ['origine' => 'particulier', 'etat' => $annonceLivre['etat'], 'prix' => (float) $annonceLivre['prix'], 'id_annonce' => (int) $annonceLivre['id_exemplaire'],
                           'detail' => 'Par @' . $annonceLivre['pseudo'] . ' · ' . libelleModeRemise($annonceLivre['mode_remise'], $annonceLivre['ville_remise'])];
    }
    $stmtDonsLivre = $pdo->prepare("
        SELECT d.id_don, d.etat, d.lieu_retrait, d.date_limite, u.pseudo
        FROM don d INNER JOIN user u ON u.id = d.id_donneur
        WHERE d.id_livre = ? AND d.statut = 'disponible' AND d.date_limite >= CURDATE()");
    $stmtDonsLivre->execute([$id]);
    foreach ($stmtDonsLivre->fetchAll() as $donLivre) {
        $toutesOffres[] = ['origine' => 'don', 'etat' => $donLivre['etat'], 'prix' => 0.0, 'id_don' => (int) $donLivre['id_don'],
                           'detail' => 'Par @' . $donLivre['pseudo'] . ' · ' . $donLivre['lieu_retrait'] . ' · avant le ' . date('d/m', strtotime($donLivre['date_limite']))];
    }
} catch (PDOException $e) {
}

require_once 'emprunts_lib.php';
verifierEmpruntsSiBesoin($pdo);
$etatEmprunt = etatEmpruntLivre($pdo, (int) $livre['id_livre']);
$peutReserverEmprunt = false;
if ($etatEmprunt['libres'] > 0 && $idUserConnecte) {
    try {
        $peutReserverEmprunt = !empruntActifLivre($pdo, (int) $livre['id_livre'], $idUserConnecte) && raisonInterdictionEmprunt($pdo, $idUserConnecte) === '';
    } catch (PDOException $e) {
    }
}
foreach ($etatEmprunt['exemplaires'] as $exemplaireEmprunt) {
    $toutesOffres[] = ['origine' => 'emprunt', 'etat' => $exemplaireEmprunt['etat'], 'prix' => 0.0, 'libre' => $exemplaireEmprunt['statut_pret'] === 'disponible',
                       'detail' => ($exemplaireEmprunt['statut_pret'] === 'disponible' ? 'Disponible' : ($exemplaireEmprunt['statut_pret'] === 'reserve' ? 'Réservé' : 'Emprunté, retour prévu le ' . date('d/m', strtotime((string) $exemplaireEmprunt['retour_prevu']))))
                           . ' · ' . (int) $exemplaireEmprunt['duree_emprunt_jours'] . ' jours, retrait en magasin'];
}

const RANG_ETATS = ['neuf' => 0, 'tres bon' => 1, 'bon' => 2, 'use' => 3];
const TRIS_OFFRES = ['prix' => 'Prix', 'etat' => 'État', 'type' => 'Type'];
const RANG_ORIGINES = ['magasin' => 0, 'particulier' => 1, 'don' => 2, 'emprunt' => 3];
$triOffres = isset(TRIS_OFFRES[$_GET['tri_offres'] ?? '']) ? $_GET['tri_offres'] : 'prix';
usort($toutesOffres, fn($a, $b) => match ($triOffres) {
    'etat' => [RANG_ETATS[$a['etat']] ?? 9, $a['prix']] <=> [RANG_ETATS[$b['etat']] ?? 9, $b['prix']],
    'type' => [RANG_ORIGINES[$a['origine']], $a['prix']] <=> [RANG_ORIGINES[$b['origine']], $b['prix']],
    default => [$a['prix'], RANG_ETATS[$a['etat']] ?? 9] <=> [$b['prix'], RANG_ETATS[$b['etat']] ?? 9],
});
$peutAcheterEnMagasin = ($_SESSION['role'] ?? '') === 'client';
?>

<style>
    .btn-custom-green { background-color: #274e13; color: white; font-weight: bold; border-radius: 50px; padding: 12px; border: none; }
    .btn-custom-green:hover { background-color: #1a330d; color: white; }
    .btn-admin-green { background-color: #274e13; color: white; border-radius: 50px; font-size: 0.85rem; padding: 10px; }
    .btn-state-active { background-color: #274e13 !important; color: white !important; border-radius: 50px; padding: 8px 20px; transition: 0.3s; }
    .btn-state-inactive { border: 1px solid #274e13; color: #274e13; border-radius: 50px; padding: 8px 20px; transition: 0.3s; }
    .btn-state-inactive:hover { background-color: #e8f5e9; }
    .bouton-favori { width: 44px; height: 44px; flex-shrink: 0; }
</style>

<div class="container mt-5 mb-5">
    <div class="row g-4 g-lg-5">
        <div class="col-md-4 text-center">
            <img src="<?= htmlspecialchars(urlImage($livre['couverture'])) ?>" alt="Couverture de <?= htmlspecialchars($livre['titre']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 450px;">
        </div>

        <div class="col-md-5">
            <?php if ($flashCompte): ?>
                <div class="alert alert-<?= htmlspecialchars($flashCompte[0]) ?> rounded-3 py-2 small"><?= htmlspecialchars($flashCompte[1]) ?></div>
            <?php endif; ?>
            <div class="d-flex align-items-start gap-3">
                <h1 class="fw-bold flex-grow-1"><?= htmlspecialchars($livre['titre']) ?></h1>
                <?php if ($idUserConnecte): ?>
                    <?= $enFavori
                        ? formulaireCompte('favori_retirer', (int) $livre['id_livre'], '<i class="bi bi-heart-fill"></i>', 'btn btn-outline-danger rounded-circle bouton-favori', "Retirer de mes favoris")
                        : formulaireCompte('favori_ajouter', (int) $livre['id_livre'], '<i class="bi bi-heart"></i>', 'btn btn-outline-secondary rounded-circle bouton-favori', "Ajouter à mes favoris") ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-secondary rounded-circle bouton-favori" title="Connectez-vous pour ajouter ce livre à vos favoris"><i class="bi bi-heart"></i></a>
                <?php endif; ?>
            </div>
            <p class="text-muted h5 mb-2">Par <?= htmlspecialchars($livre['auteur']) ?></p>
            <?php
            require_once 'avis_lib.php';
            $noteEntete = noteDuLivre($pdo, (int) $livre['id_livre']);
            ?>
            <a href="#avis" class="d-inline-flex align-items-center gap-2 text-decoration-none text-muted small mb-2">
                <?= etoilesStyle() ?>
                <?php if ($noteEntete['nb']): ?>
                    <?= etoiles($noteEntete['moyenne']) ?>
                    <span><?= number_format($noteEntete['moyenne'], 1, ',', '') ?> · <?= $noteEntete['nb'] ?> avis</span>
                <?php else: ?>
                    <span><i class="bi bi-chat-square-text"></i> Donner le premier avis</span>
                <?php endif; ?>
            </a>
            <?php
            require_once 'categories_lib.php';
            $catLivre = null;
            $tagsLivre = [];
            try {
                if (!empty($livre['id_categorie'])) {
                    $stmtCat = $pdo->prepare("SELECT nom, slug FROM categorie WHERE id_categorie = ?");
                    $stmtCat->execute([$livre['id_categorie']]);
                    $catLivre = $stmtCat->fetch();
                }
                $tagsLivre = tagsDuLivre($pdo, (int) $livre['id_livre']);
            } catch (PDOException $e) {
            }
            ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php if ($catLivre): ?>
                    <a href="catalogue.php?categorie=<?= urlencode($catLivre['slug']) ?>" class="badge rounded-pill text-decoration-none" style="background:#274e13;"><?= htmlspecialchars($catLivre['nom']) ?></a>
                <?php endif; ?>
                <?php foreach ($tagsLivre as $tl): ?>
                    <a href="catalogue.php?tag=<?= urlencode($tl['slug']) ?>" class="badge rounded-pill bg-light text-success border border-success text-decoration-none">#<?= htmlspecialchars($tl['nom']) ?></a>
                <?php endforeach; ?>
            </div>
            
            <div class="mb-4">
                <h6 class="fw-bold mb-3">État des exemplaires</h6>
                <div class="d-flex flex-wrap gap-2 pb-2">
                    <?php foreach ($offres as $index => $offre): 
                        $is_active = ($index == $offre_index);
                        $etat_label = formaterEtat($offre['etat']);
                    ?>
                        <a href="detail_livre.php?id=<?= $id ?>&offre=<?= $index ?>" 
                           class="<?= $is_active ? 'btn-state-active' : 'btn-state-inactive' ?> text-decoration-none d-flex flex-column align-items-center" 
                           style="min-width: 90px;">
                            <span class="fw-bold text-nowrap"><?= $etat_label ?></span>
                            <span class="small" style="font-size: 0.75rem;"><?= formaterPrix($offre['prix']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4">
                <h5 class="fw-bold border-bottom pb-2">Descriptif</h5>
                <p class="text-secondary" style="text-align: justify;"><?= nl2br(htmlspecialchars($livre['description'])) ?></p>
            </div>

            <div class="mt-4">
                <h5 class="fw-bold border-bottom pb-2">Caractéristiques</h5>
                <table class="table table-sm mt-3">
                    <?php foreach ([
                        'Éditeur' => $livre['editeur'] ?? '',
                        'Collection' => $livre['collection'] ?? '',
                        'Année' => $livre['annee_parution'] ?? '',
                        'ISBN' => $livre['isbn'] ?? '',
                        'Nombre de pages' => $livre['nb_pages'] ?? '',
                        'Format' => trim(($livre['reliure'] ?? '') . (!empty($livre['reliure']) && !empty($livre['dimensions']) ? ' · ' : '') . ($livre['dimensions'] ?? '')),
                        'Poids' => !empty($livre['poids']) ? $livre['poids'] . ' g' : '',
                        'Langue' => $livre['langue'] ?? '',
                    ] as $libelleCaracteristique => $valeurCaracteristique): ?>
                        <?php if ((string) $valeurCaracteristique !== ''): ?>
                            <tr><th><?= $libelleCaracteristique ?></th><td><?= htmlspecialchars((string) $valeurCaracteristique) ?></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 shadow-sm border-0 bg-white" style="border-radius: 15px;">
                <?php if ($offre_selectionnee): ?>
                    <h2 class="fw-bold text-success"><?= formaterPrix($offre_selectionnee['prix']) ?></h2>
                    <p class="text-muted small">Stock : <?= $offre_selectionnee['quantite'] ?></p>
                    
                    <?php 
                    $estConnecte = isset($_SESSION['user_id']);
                    $estClient = ($estConnecte && isset($_SESSION['role']) && $_SESSION['role'] === 'client');
                    ?>

                    <?php if ($estClient && $offre_selectionnee['quantite'] > 0): ?>
                        <form action="action_ajouter_panier.php" method="POST">
                            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                            <input type="hidden" name="id_livre" value="<?= $id ?>">
                            <input type="hidden" name="id_exemplaire" value="<?= $offre_selectionnee['id_exemplaire'] ?>">
                            <input type="hidden" name="titre" value="<?= htmlspecialchars($livre['titre']) ?>">
                            <input type="hidden" name="etat" value="<?= htmlspecialchars($offre_selectionnee['etat']) ?>">
                            <input type="hidden" name="prix" value="<?= $offre_selectionnee['prix'] ?>">
                            <input type="hidden" name="image" value="<?= htmlspecialchars($livre['couverture'] ?: 'default.png') ?>">
                            <button type="submit" name="action" value="ajouter" class="btn btn-custom-green w-100 mb-2">Ajouter au panier</button>
                            <button type="submit" name="action" value="acheter" class="btn btn-outline-dark w-100 rounded-pill">Acheter</button>
                        </form>
                    <?php elseif (!$estConnecte): ?>
                        <a href="login.php" class="btn btn-custom-green w-100 rounded-pill py-2">Connexion pour acheter</a>
                    <?php elseif ($offre_selectionnee['quantite'] <= 0): ?>
                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Cet état est épuisé</button>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary w-100 rounded-pill" disabled>Achat réservé aux comptes lecteurs</button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($stockTotal <= 0): ?>
                    <?php if (!$offre_selectionnee): ?>
                        <h2 class="h5 fw-bold text-secondary">Épuisé</h2>
                    <?php endif; ?>
                    <p class="small text-muted mb-2">Ce livre n'est plus en stock pour le moment.</p>
                    <?php if (!$idUserConnecte): ?>
                        <a href="login.php" class="btn btn-outline-success w-100 rounded-pill"><i class="bi bi-bell"></i> Se connecter pour être averti</a>
                    <?php elseif ($alerteActive): ?>
                        <div class="small text-success mb-2"><i class="bi bi-bell-fill"></i> Vous serez prévenu par mail dès son retour.</div>
                        <?= formulaireCompte('alerte_retirer', (int) $livre['id_livre'], 'Annuler l\'alerte', 'btn btn-link btn-sm text-muted p-0') ?>
                    <?php else: ?>
                        <?= formulaireCompte('alerte_ajouter', (int) $livre['id_livre'], '<i class="bi bi-bell"></i> M\'avertir quand il revient', 'btn btn-outline-success w-100 rounded-pill') ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($etatEmprunt['total'] > 0): ?>
                    <hr class="my-3">
                    <?php include 'partie_emprunt_livre.php'; ?>
                <?php endif; ?>

                <?php
                $nbDonsLivre = 0;
                try {
                    $stmtDons = $pdo->prepare("SELECT COUNT(*) FROM don WHERE id_livre = ? AND statut = 'disponible' AND date_limite >= CURDATE()");
                    $stmtDons->execute([$id]);
                    $nbDonsLivre = (int) $stmtDons->fetchColumn();
                } catch (PDOException $e) {
                }
                ?>
                <hr class="my-3">
                <?php if ($nbDonsLivre > 0): ?>
                    <a href="dons.php?q=<?= urlencode($livre['titre']) ?>" class="d-block small fw-bold text-success text-decoration-none mb-2">
                        <i class="bi bi-gift"></i> <?= $nbDonsLivre ?> exemplaire<?= $nbDonsLivre > 1 ? 's' : '' ?> à récupérer gratuitement
                    </a>
                <?php endif; ?>
                <p class="small text-muted mb-0">
                    <i class="bi bi-heart" aria-hidden="true"></i> Vous avez ce livre ?
                    <a href="annonce_creer.php?livre=<?= $id ?>" class="text-muted">Vendez-le</a> ou
                    <a href="don_creer.php?livre=<?= $id ?>" class="text-muted">donnez-le</a>
                </p>

                <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'gestionnaire'], true)): ?>
                    <hr class="my-3">
                    <div class="d-grid gap-2">
                        <a href="admin/livre.php?id=<?= $id ?>" class="btn btn-admin-green">Modifier la fiche</a>
                        <a href="admin/exemplaires.php?id=<?= $id ?>" class="btn btn-outline-success rounded-pill">Gérer le stock</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($toutesOffres): ?>
    <section class="container mb-5 offres-section" id="offres" aria-labelledby="titre-offres">
        <div class="carte-compte offres-livre">
            <div class="offres-livre__entete">
                <h2 class="carte-compte__titre mb-0" id="titre-offres">Toutes les offres <span class="offres-livre__nombre"><?= count($toutesOffres) ?></span></h2>
                <nav class="offres-livre__tris" aria-label="Trier les offres">
                    <span class="small text-muted">Trier par</span>
                    <?php foreach (TRIS_OFFRES as $cleTri => $libelleTri): ?>
                        <a href="detail_livre.php?id=<?= (int) $id ?>&offre=<?= (int) $offre_index ?>&tri_offres=<?= $cleTri ?>#offres"
                           class="puce-univers <?= $triOffres === $cleTri ? 'actif' : '' ?>" <?= $triOffres === $cleTri ? 'aria-current="true"' : '' ?>><?= $libelleTri ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <ul class="offres-livre__liste">
                <?php foreach ($toutesOffres as $offreListe): ?>
                    <li class="ligne-offre ligne-offre--<?= $offreListe['origine'] ?>">
                        <span class="ligne-offre__type">
                            <?php if ($offreListe['origine'] === 'don'): ?>
                                <i class="bi bi-gift" aria-hidden="true"></i> Don
                            <?php elseif ($offreListe['origine'] === 'emprunt'): ?>
                                <i class="bi bi-book" aria-hidden="true"></i> Emprunt
                            <?php elseif ($offreListe['origine'] === 'particulier'): ?>
                                <i class="bi bi-people" aria-hidden="true"></i> Particulier
                            <?php else: ?>
                                <i class="bi bi-shop" aria-hidden="true"></i> Magasin
                            <?php endif; ?>
                        </span>
                        <span class="ligne-offre__etat"><?= htmlspecialchars(libelleEtat($offreListe['etat'])) ?></span>
                        <span class="ligne-offre__detail"><?= htmlspecialchars($offreListe['detail']) ?></span>
                        <span class="ligne-offre__prix"><?= $offreListe['origine'] === 'don' ? 'Gratuit' : ($offreListe['origine'] === 'emprunt' ? 'Prêt gratuit' : formaterPrix($offreListe['prix'])) ?></span>
                        <span class="ligne-offre__action">
                            <?php if ($offreListe['origine'] === 'don'): ?>
                                <a href="don_detail.php?id=<?= $offreListe['id_don'] ?>" class="bouton bouton-contour bouton-petit">Voir le don</a>
                            <?php elseif ($offreListe['origine'] === 'emprunt' && $offreListe['libre'] && $peutReserverEmprunt): ?>
                                <form action="emprunt_action.php" method="POST">
                                    <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                                    <input type="hidden" name="action" value="reserver">
                                    <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
                                    <input type="hidden" name="retour" value="livre">
                                    <button class="bouton bouton-principal bouton-petit"><i class="bi bi-bookmark-plus" aria-hidden="true"></i> Emprunter</button>
                                </form>
                            <?php elseif ($offreListe['origine'] === 'emprunt'): ?>
                                <a href="#emprunt" class="bouton bouton-contour bouton-petit"><?= $offreListe['libre'] ? 'Emprunter' : "Liste d'attente" ?></a>
                            <?php elseif ($offreListe['origine'] === 'particulier'): ?>
                                <a href="annonce.php?id=<?= $offreListe['id_annonce'] ?>" class="bouton bouton-contour bouton-petit">Voir l'annonce</a>
                            <?php elseif ($peutAcheterEnMagasin): ?>
                                <form action="action_ajouter_panier.php" method="POST">
                                    <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                                    <input type="hidden" name="id_exemplaire" value="<?= $offreListe['id_exemplaire'] ?>">
                                    <button type="submit" name="action" value="ajouter" class="bouton bouton-principal bouton-petit">
                                        <i class="bi bi-bag-plus" aria-hidden="true"></i> Ajouter<span class="visually-hidden"> au panier : <?= htmlspecialchars(libelleEtat($offreListe['etat'])) ?>, <?= formaterPrix($offreListe['prix']) ?></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="detail_livre.php?id=<?= (int) $id ?>&offre=<?= $offreListe['index'] ?>" class="bouton bouton-contour bouton-petit">Choisir</a>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<?php include 'partie_avis.php'; ?>

<?php include 'footer.php'; ?>
