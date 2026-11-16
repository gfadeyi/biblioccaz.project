<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../retours_lib.php';
require_once __DIR__ . '/../messagerie_equipe_lib.php';
exigerDroit('catalogue');

$idRetour = (int) ($_GET['id'] ?? 0);
$lienRetour = "retour?id=$idRetour";
if (!retoursDisponibles($pdo)) {
    rediriger('retours', 'warning', 'Les retours ne sont pas installés.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($lienRetour);
    $nouveauStatut = is_string($_POST['statut'] ?? null) ? $_POST['statut'] : '';
    [$type, $message] = changerStatutRetour($pdo, $idRetour, (int) $_SESSION['user_id'], $nouveauStatut, is_string($_POST['commentaire'] ?? null) ? $_POST['commentaire'] : '', is_string($_POST['statut_attendu'] ?? null) ? $_POST['statut_attendu'] : '');
    rediriger($lienRetour, $type, $message);
}

$retour = retourComplet($pdo, $idRetour);
if (!$retour) {
    rediriger('retours', 'warning', "Cette demande n'existe pas.");
}
$suivants = RETOUR_TRANSITIONS[$retour['type']][$retour['statut']] ?? [];
$libellesActions = [
    'accepte' => [$retour['type'] === 'retour' ? 'Accepter le retour' : 'Accepter le remboursement', 'check2-circle', 'btn-vert'],
    'refuse' => ['Refuser la demande', 'x-circle', 'btn-outline-danger'],
    'recu' => ['Colis reçu : remettre en stock', 'box-seam', 'btn-vert'],
    'rembourse' => ['Marquer comme remboursé', 'cash-coin', 'btn-vert'],
];

$titrePage = 'Demande ' . $retour['reference'];
$sousTitrePage = ($retour['type'] === 'retour' ? 'Retour' : 'Remboursement') . ' · commande n° ' . (int) $retour['id_commande'] . ' · @' . $retour['pseudo'];
$pageActive = 'retours';
$filAriane = [[null, 'Ventes'], ['retours', 'Retours']];
$actionsPage = [['retours', 'arrow-left', 'Toutes les demandes', 'btn-outline-secondary'], ['commande?id=' . (int) $retour['id_commande'], 'bag-check', 'Voir la commande', 'btn-outline-success'], actionDemandeAvisEquipe('retour', $idRetour)];
include __DIR__ . '/_haut.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="carte mb-4">
            <div class="carte-entete d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0">Demande du client</h2>
                <span class="etiquette<?= in_array($retour['statut'], ['refuse', 'annule'], true) ? ' etiquette-neutre' : ($retour['statut'] === 'demande' ? ' etiquette-alerte' : '') ?>"><?= e(libelleStatutRetour($retour['statut'])) ?></span>
            </div>
            <div class="carte-corps">
                <dl class="recap-commande">
                    <div><dt>Motif</dt><dd><?= e(libelleMotifRetour($retour['type'], $retour['motif'])) ?></dd></div>
                    <div><dt>Montant à rembourser</dt><dd><strong><?= formaterPrix((float) $retour['montant']) ?></strong></dd></div>
                    <div><dt>Commande</dt><dd><?= formaterPrix((float) $retour['total_commande']) ?> · <?= $retour['statut_commande'] === 'annulee' ? 'annulée le ' . date('d/m/Y', strtotime((string) $retour['date_annulation'])) : 'livrée le ' . date('d/m/Y', strtotime(dateLivraisonCommande(['date_commande' => $retour['date_commande']]))) ?></dd></div>
                    <?php if ((int) $retour['points_retires'] > 0): ?><div><dt>Points retirés</dt><dd><?= (int) $retour['points_retires'] ?></dd></div><?php endif; ?>
                </dl>
                <?php if ($retour['message']): ?>
                    <h3 class="small text-uppercase fw-bold text-muted mt-3">Message du client</h3>
                    <p class="mb-0"><?= nl2br(e($retour['message'])) ?></p>
                <?php endif; ?>
                <?php if ($retour['lignes']): ?>
                    <h3 class="small text-uppercase fw-bold text-muted mt-3">Livres retournés</h3>
                    <ul class="lignes-commande">
                        <?php foreach ($retour['lignes'] as $ligne): ?>
                            <li>
                                <img src="<?= imageLivre($ligne['couverture']) ?>" alt="" loading="lazy">
                                <div class="lignes-commande__infos"><strong><?= e($ligne['titre']) ?></strong><small class="text-muted d-block"><?= e(libelleEtat($ligne['etat'])) ?> · <?= (int) $ligne['quantite_retour'] ?> sur <?= (int) $ligne['quantite'] ?> × <?= formaterPrix((float) $ligne['prix_unitaire']) ?></small></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="carte">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Historique</h2></div>
            <div class="carte-corps">
                <ol class="historique-commande">
                    <?php foreach ($retour['historique'] as $etape): ?>
                        <li>
                            <span class="historique-commande__date"><?= date('d/m/Y H:i', strtotime($etape['date_action'])) ?></span>
                            <span><strong><?= e(libelleStatutRetour($etape['statut'])) ?></strong> par @<?= e((string) ($etape['pseudo'] ?? 'compte supprimé')) ?><?php if ($etape['commentaire']): ?><small class="d-block text-muted"><?= e($etape['commentaire']) ?></small><?php endif; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="carte mb-4">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Traitement</h2></div>
            <div class="carte-corps">
                <?php if (!$suivants): ?>
                    <p class="small text-muted mb-0">Demande clôturée (<?= e(mb_strtolower(libelleStatutRetour($retour['statut']))) ?>)<?= $retour['pseudo_traitant'] ? ' par @' . e($retour['pseudo_traitant']) : '' ?>.</p>
                <?php else: ?>
                    <ol class="small text-muted ps-3">
                        <?php foreach (etapesRetour($retour['type']) as $etape): ?><li<?= $etape === $retour['statut'] ? ' class="fw-bold text-body"' : '' ?>><?= e(libelleStatutRetour($etape)) ?></li><?php endforeach; ?>
                    </ol>
                    <?php foreach ($suivants as $suivant): ?>
                        <?php [$libelle, $icone, $classe] = $libellesActions[$suivant]; ?>
                        <form method="POST" class="mb-3"<?= $suivant === 'refuse' ? '' : ' data-confirmer="' . e($libelle) . ' ? Le client sera prévenu par mail et notification." data-bouton-confirmer="' . e($libelle) . '"' ?>>
                            <?= champCsrf() ?>
                            <input type="hidden" name="statut" value="<?= $suivant ?>">
                            <input type="hidden" name="statut_attendu" value="<?= e($retour['statut']) ?>">
                            <label for="commentaire-<?= $suivant ?>" class="form-label small fw-bold"><?= $suivant === 'refuse' ? 'Motif du refus (visible par le client) *' : 'Message au client (facultatif)' ?></label>
                            <textarea id="commentaire-<?= $suivant ?>" name="commentaire" class="form-control form-control-sm mb-2" rows="2" maxlength="<?= RETOUR_NOTE_MAX ?>"<?= $suivant === 'refuse' ? ' minlength="10" required' : '' ?>></textarea>
                            <?php if ($suivant === 'recu' && $retour['motif'] === 'abime'): ?><p class="small text-muted mb-2">Livres déclarés abîmés : ils ne seront pas remis en stock.</p><?php endif; ?>
                            <?php if ($suivant === 'rembourse' && $retour['type'] === 'retour'): ?><p class="small text-muted mb-2">Les points gagnés sur la part remboursée sont retirés (au plus les points d’achat de la commande).</p><?php endif; ?>
                            <button type="submit" class="btn <?= $classe ?> btn-sm w-100"><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i> <?= e($libelle) ?></button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="carte">
            <div class="carte-entete"><h2 class="h6 fw-bold mb-0">Client</h2></div>
            <div class="carte-corps small">
                <p class="mb-1 fw-bold">@<?= e($retour['pseudo']) ?></p>
                <p class="mb-0"><a href="mailto:<?= e($retour['email']) ?>"><?= e($retour['email']) ?></a></p>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
