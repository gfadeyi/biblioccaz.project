<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../commandes_lib.php';
exigerDroit('catalogue');

$idCommande = (int) ($_GET['id'] ?? 0);
$retour = "commande.php?id=$idCommande";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    if (($_POST['action'] ?? '') === 'annuler') {
        try {
            $annulation = annulerCommande($pdo, $idCommande, (int) $_SESSION['user_id'], (string) ($_POST['motif'] ?? ''));
        } catch (RuntimeException $e) {
            rediriger($retour, 'warning', $e->getMessage());
        }
        if (!empty($_POST['prevenir_client'])) {
            mailAnnulationCommande($annulation);
        }
        $message = "Commande n° $idCommande annulée : stock remis";
        if ($annulation['points']['rendus'] > 0) {
            $message .= ", {$annulation['points']['rendus']} points rendus";
        }
        if ($annulation['points']['retires'] > 0) {
            $message .= ", {$annulation['points']['retires']} points retirés";
        }
        $message .= '.';
        if ($annulation['clients_prevenus'] > 0) {
            $message .= " {$annulation['clients_prevenus']} client(s) en attente prévenu(s) du retour en stock.";
        }
        if ($annulation['titres_sans_fiche']) {
            $message .= " Livre(s) supprimé(s) du catalogue, non remis en stock : " . implode(', ', $annulation['titres_sans_fiche']) . '.';
        }
        rediriger($retour, 'success', $message);
    }
    rediriger($retour);
}

$stmt = $pdo->prepare("
    SELECT c.*, u.pseudo, u.email, u.date_inscription, a.pseudo AS pseudo_annulateur
    FROM commande c
    INNER JOIN `user` u ON u.id = c.id_user
    LEFT JOIN `user` a ON a.id = c.id_annulateur
    WHERE c.id_commande = ?");
$stmt->execute([$idCommande]);
$commande = $stmt->fetch();
if (!$commande) {
    rediriger('commandes.php', 'warning', "Cette commande n'existe pas.");
}
$lignes = lignesDeCommande($pdo, $idCommande);
$annulee = $commande['statut'] === 'annulee';

$stmt = $pdo->prepare("SELECT * FROM point_mouvement WHERE id_user = ? AND reference = ? ORDER BY date_mouvement, id_mouvement");
$stmt->execute([$commande['id_user'], 'commande:' . $idCommande]);
$mouvements = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_user = ? AND statut <> 'annulee'");
$stmt->execute([$commande['id_user']]);
$nbCommandesClient = (int) $stmt->fetchColumn();

$titrePage = 'Commande n° ' . $idCommande;
$sousTitrePage = 'Passée le ' . date('d/m/Y à H:i', strtotime($commande['date_commande'])) . ' par @' . $commande['pseudo'];
$pageActive = 'commandes';
$filAriane = [[null, 'Ventes'], ['commandes.php', 'Commandes']];
$actionsPage = [['commandes.php', 'arrow-left', 'Toutes les commandes', 'btn-outline-secondary']];
include __DIR__ . '/_haut.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="carte mb-4">
            <div class="carte-entete d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0">Livres commandés</h2>
                <?php if ($annulee): ?>
                    <span class="etiquette etiquette-alerte"><i class="bi bi-x-circle" aria-hidden="true"></i> Annulée</span>
                <?php else: ?>
                    <span class="etiquette"><i class="bi bi-check2" aria-hidden="true"></i> Validée</span>
                <?php endif; ?>
            </div>
            <div class="carte-corps">
                <ul class="lignes-commande">
                    <?php foreach ($lignes as $ligne): ?>
                        <li>
                            <?php if (!empty($ligne['couverture'])): ?>
                                <img src="<?= imageLivre($ligne['couverture']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="lignes-commande__sans-image" aria-hidden="true"><i class="bi bi-book"></i></span>
                            <?php endif; ?>
                            <div class="lignes-commande__infos">
                                <?php if ($ligne['id_livre']): ?>
                                    <a href="../detail_livre.php?id=<?= (int) $ligne['id_livre'] ?>" target="_blank" rel="noopener" class="fw-bold"><?= e($ligne['titre']) ?></a>
                                <?php else: ?>
                                    <strong><?= e($ligne['titre']) ?></strong> <span class="etiquette etiquette-neutre">retiré du catalogue</span>
                                <?php endif; ?>
                                <small class="text-muted d-block"><?= e(libelleEtat($ligne['etat'])) ?> · <?= (int) $ligne['quantite'] ?> × <?= formaterPrix((float) $ligne['prix_unitaire']) ?></small>
                                <?php if ($ligne['id_livre'] && $ligne['stock_offre'] !== null): ?>
                                    <a href="exemplaires.php?id=<?= (int) $ligne['id_livre'] ?>" class="small lignes-commande__stock"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Stock actuel de cette offre : <?= (int) $ligne['stock_offre'] ?></a>
                                <?php endif; ?>
                            </div>
                            <strong class="lignes-commande__prix"><?= formaterPrix((float) $ligne['prix_unitaire'] * (int) $ligne['quantite']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl class="recap-commande">
                    <div><dt>Sous-total</dt><dd><?= formaterPrix((float) $commande['sous_total']) ?></dd></div>
                    <?php if ((float) $commande['remise'] > 0): ?><div><dt>Remise panier</dt><dd>−<?= formaterPrix((float) $commande['remise']) ?></dd></div><?php endif; ?>
                    <?php if ((float) $commande['reduction_points'] > 0): ?><div><dt><?= (int) $commande['points_utilises'] > 0 ? 'Bon de points (' . (int) $commande['points_utilises'] . ' points)' : 'Bon cadeau' ?></dt><dd>−<?= formaterPrix((float) $commande['reduction_points']) ?></dd></div><?php endif; ?>
                    <div><dt>Livraison</dt><dd><?= (float) $commande['frais_livraison'] > 0 ? formaterPrix((float) $commande['frais_livraison']) : 'Offerte' ?></dd></div>
                    <div class="recap-commande__total"><dt>Total payé</dt><dd class="<?= $annulee ? 'text-decoration-line-through' : '' ?>"><?= formaterPrix((float) $commande['total']) ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="carte">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Historique</h2></div>
            <div class="carte-corps">
                <ol class="historique-commande">
                    <li>
                        <span class="historique-commande__date"><?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></span>
                        <span><i class="bi bi-bag-check" aria-hidden="true"></i> Commande passée par @<?= e($commande['pseudo']) ?></span>
                    </li>
                    <?php foreach ($mouvements as $mouvement): ?>
                        <li>
                            <span class="historique-commande__date"><?= date('d/m/Y H:i', strtotime($mouvement['date_mouvement'])) ?></span>
                            <span><i class="bi bi-coin" aria-hidden="true"></i> <?= e($mouvement['libelle']) ?></span>
                            <strong class="<?= (int) $mouvement['points'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $mouvement['points'] > 0 ? '+' : '' ?><?= (int) $mouvement['points'] ?> pts</strong>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($annulee): ?>
                        <li class="historique-commande__annulation">
                            <span class="historique-commande__date"><?= date('d/m/Y H:i', strtotime($commande['date_annulation'])) ?></span>
                            <span>
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Annulée<?= $commande['pseudo_annulateur'] ? ' par @' . e($commande['pseudo_annulateur']) : '' ?>, stock remis
                                <?php if ($commande['motif_annulation']): ?><small class="d-block text-muted">Motif : <?= e($commande['motif_annulation']) ?></small><?php endif; ?>
                            </span>
                        </li>
                    <?php endif; ?>
                </ol>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="carte mb-4">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Client</h2></div>
            <div class="carte-corps small">
                <p class="mb-1 fw-bold fs-6"><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></p>
                <p class="mb-1">@<?= e($commande['pseudo']) ?> · <a href="mailto:<?= e($commande['email']) ?>"><?= e($commande['email']) ?></a></p>
                <p class="text-muted mb-3"><?= $nbCommandesClient ?> commande<?= $nbCommandesClient > 1 ? 's' : '' ?> valide<?= $nbCommandesClient > 1 ? 's' : '' ?> · inscrit le <?= date('d/m/Y', strtotime($commande['date_inscription'])) ?></p>
                <h3 class="small text-uppercase fw-bold text-muted mb-1">Livraison</h3>
                <p class="mb-0"><?= e($commande['adresse']) ?></p>
            </div>
        </section>

        <section class="carte mb-4">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Points fidélité</h2></div>
            <div class="carte-corps">
                <dl class="recap-commande mb-0">
                    <div><dt>Points utilisés</dt><dd><?= (int) $commande['points_utilises'] ?></dd></div>
                    <div><dt>Points gagnés</dt><dd><?= (int) $commande['points_gagnes'] ?></dd></div>
                    <?php if ((int) $commande['bonus_niveau'] > 0): ?><div><dt>dont bonus de niveau</dt><dd><?= (int) $commande['bonus_niveau'] ?></dd></div><?php endif; ?>
                </dl>
            </div>
        </section>

        <?php if (!$annulee): ?>
            <section class="carte carte-annulation">
                <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Annuler la commande</h2></div>
                <form method="POST" class="carte-corps" data-confirmer="Annuler la commande n° <?= $idCommande ?> ? Les livres reviennent en stock<?= (int) $commande['points_utilises'] > 0 ? ', les ' . (int) $commande['points_utilises'] . ' points du bon sont rendus' : '' ?> et les points gagnés sont retirés. Cette action est définitive." data-bouton-confirmer="Annuler la commande">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="annuler">
                    <ul class="small text-muted ps-3 mb-3">
                        <li>les livres reviennent en stock (les clients en attente sont prévenus) ;</li>
                        <?php if ((int) $commande['points_utilises'] > 0): ?><li>les <?= (int) $commande['points_utilises'] ?> points du bon sont rendus ;</li><?php endif; ?>
                        <li>les points gagnés avec cette commande sont retirés.</li>
                    </ul>
                    <label for="motif" class="form-label small fw-bold">Motif <span class="fw-normal text-muted">(facultatif, visible par le client)</span></label>
                    <textarea id="motif" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="<?= MOTIF_ANNULATION_MAX ?>" placeholder="Ex. : livre abîmé lors de la préparation"></textarea>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="prevenir_client" value="1" id="prevenir_client" checked>
                        <label class="form-check-label small" for="prevenir_client">Prévenir le client par mail</label>
                    </div>
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle" aria-hidden="true"></i> Annuler la commande</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
