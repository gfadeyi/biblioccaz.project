<?php
require_once 'config.php';
require_once 'livres_lib.php';
require_once 'series_lib.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$offre_index = max(0, filter_input(INPUT_GET, 'offre', FILTER_VALIDATE_INT) ?: 0);

$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ? AND is_valide = 1");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (!$livre) {
    include 'header.php';
    echo "<div class='container mt-5'>Livre introuvable.</div>";
    include 'footer.php'; exit;
}

$stmtEx = $pdo->prepare("SELECT id_exemplaire, etat, prix, quantite FROM exemplaire e WHERE e.id_livre = ? AND " . conditionOffreMagasin() . " ORDER BY prix ASC");
$stmtEx->execute([$id]);
$offres = array_values(array_filter(offresMagasinDuLivre($pdo, (int) $id), fn($offre) => $offre['libre'] > 0));
foreach ($offres as &$offreLibre) { $offreLibre['quantite'] = $offreLibre['libre']; }
unset($offreLibre);

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

require_once 'recommandations_lib.php';
require_once 'auteurs_lib.php';
$idLecteurSuggestions = $idUserConnecte && in_array($_SESSION['role'] ?? '', ROLES_SUGGESTIONS, true) ? $idUserConnecte : 0;
if ($idLecteurSuggestions && (int) $livre['is_valide'] === 1) {
    enregistrerConsultation($pdo, $idLecteurSuggestions, (int) $livre['id_livre']);
}
$livresProches = (int) $livre['is_valide'] === 1 ? livresSimilaires($pdo, $livre, $idLecteurSuggestions) : [];

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
    if ($e == 'use' || $e == 'usé') return 'Correct';
    return ucfirst($e);
}

$toutesOffres = [];
foreach ($offres as $index => $offre) {
    $toutesOffres[] = ['origine' => 'magasin', 'etat' => $offre['etat'], 'prix' => (float) $offre['prix'], 'index' => $index,
                       'detail' => stockEstBas((int) $offre['quantite']) ? 'Stock bas' : 'En stock', 'id_exemplaire' => (int) $offre['id_exemplaire']];
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
$triOffres = is_string($_GET['tri_offres'] ?? null) && isset(TRIS_OFFRES[$_GET['tri_offres']]) ? $_GET['tri_offres'] : 'prix';
usort($toutesOffres, fn($a, $b) => match ($triOffres) {
    'etat' => [RANG_ETATS[$a['etat']] ?? 9, $a['prix']] <=> [RANG_ETATS[$b['etat']] ?? 9, $b['prix']],
    'type' => [RANG_ORIGINES[$a['origine']], $a['prix']] <=> [RANG_ORIGINES[$b['origine']], $b['prix']],
    default => [$a['prix'], RANG_ETATS[$a['etat']] ?? 9] <=> [$b['prix'], RANG_ETATS[$b['etat']] ?? 9],
});
$peutAcheterEnMagasin = !isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'client';
?>

<?php
require_once 'categories_lib.php';
$noteEntete = noteDuLivre($pdo, (int) $id);
$tagsLivre = tagsDuLivre($pdo, (int) $id);
$publicsLivre = array_values(array_filter($tagsLivre, fn($tag) => ($tag['famille'] ?? $tag['type'] ?? '') === 'public'));
$catLivre = null;
if (!empty($livre['id_categorie'])) {
    $requeteCategorie = $pdo->prepare('SELECT nom, slug FROM categorie WHERE id_categorie = ?');
    $requeteCategorie->execute([(int) $livre['id_categorie']]);
    $catLivre = $requeteCategorie->fetch() ?: null;
}
$slugAuteur = slugFichePublique($pdo, isset($livre['id_user_auteur']) ? (int) $livre['id_user_auteur'] : null);
$serieLivre = serieDuLivre($pdo, (int) $id);
$selectionSerie = $serieLivre ? selectionSerieDisponible($pdo, (int) $serieLivre['id_serie']) : null;
$etatsMagasin = etatsMagasinDuLivre($pdo, (int) $id);
$selectionMagasin = $offre_selectionnee ?: ($offres[0] ?? null);
$prixNeufLivre = prixNeufReference($pdo, $livre);
$requeteEditions = $pdo->prepare('SELECT COUNT(*) FROM livre WHERE titre = ? AND auteur = ? AND id_livre <> ? AND is_valide = 1');
$requeteEditions->execute([$livre['titre'], (string) $livre['auteur'], (int) $id]);
$nombreEditions = (int) $requeteEditions->fetchColumn();
$caracteristiquesLivre = array_filter([
    'Éditeur' => $livre['editeur'] ?? '', 'Collection' => $livre['collection'] ?? '', 'Année' => $livre['annee_parution'] ?? '',
    'ISBN' => $livre['isbn'] ?? '', 'Niveau scolaire' => $livre['niveau_scolaire'] ?? '', 'Matière' => $livre['matiere'] ?? '',
    'Nombre de pages' => $livre['nb_pages'] ?? '', 'Format' => trim(($livre['reliure'] ?? '') . ' ' . ($livre['dimensions'] ?? '')), 'Poids' => !empty($livre['poids']) ? $livre['poids'] . ' g' : '', 'Langue' => $livre['langue'] ?? '',
], fn($valeur) => trim((string) $valeur) !== '');
$titreDocument = $livre['titre'];
include 'header.php';
include 'partie_fiche_livre.php';
?>

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

<?php if ($livresProches): ?>
    <section class="container mb-5 section-accueil" aria-labelledby="titre-proches">
        <div class="entete-section">
            <div>
                <h2 id="titre-proches">Vous aimerez aussi</h2>
                <p>Des livres disponibles proches de celui-ci<?= $idLecteurSuggestions ? ', choisis aussi selon vos goûts' : '' ?>.</p>
            </div>
            <?php if ($idLecteurSuggestions): ?>
                <a href="suggestions.php" class="lien-voir-tout">Mes suggestions <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            <?php endif; ?>
        </div>
        <div class="carrousel">
            <?php foreach ($livresProches as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php include 'footer.php'; ?>
