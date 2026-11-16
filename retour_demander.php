<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'retours_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$idCommande = (int) ($_GET['commande'] ?? $_POST['id_commande'] ?? 0);

if (!retoursDisponibles($pdo)) {
    $_SESSION['flash_message'] = ['warning', 'Les demandes de retour ne sont pas encore disponibles.'];
    header('Location: commandes');
    exit();
}
$commande = commandeDuMembre($pdo, $idCommande, $idUser);
$type = $commande ? typeRetourPossible($commande) : null;
$dejaDemande = $commande ? retourActifCommande(retoursParCommande($pdo, [$idCommande])[$idCommande] ?? []) : null;
if (!$commande || $type === null || $dejaDemande) {
    $_SESSION['flash_message'] = ['warning', $dejaDemande ? 'Une demande existe déjà pour cette commande : suivez-la dans « Mes retours ».' : 'Cette commande ne permet pas de demande de retour ou de remboursement (délai dépassé ou livraison en cours).'];
    header('Location: ' . ($dejaDemande ? 'retours?id=' . (int) $dejaDemande['id_retour'] : 'commandes'));
    exit();
}

$lignes = lignesRetournables($pdo, $idCommande);
$motifs = $type === 'remboursement' ? RETOUR_MOTIFS_REMBOURSEMENT : RETOUR_MOTIFS;
$erreur = '';
$saisie = ['motif' => '', 'message' => '', 'quantites' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saisie['motif'] = is_string($_POST['motif'] ?? null) ? $_POST['motif'] : '';
    $saisie['message'] = is_string($_POST['message'] ?? null) ? $_POST['message'] : '';
    $saisie['quantites'] = is_array($_POST['quantite'] ?? null) ? array_map('intval', $_POST['quantite']) : [];
    if (!verifierCsrf()) {
        $erreur = 'Session expirée, merci de réessayer.';
    } else {
        $resultat = creerDemandeRetour($pdo, $idUser, $idCommande, $saisie['motif'], $saisie['message'], $saisie['quantites']);
        if ($resultat[0] === 'success') {
            $_SESSION['flash_message'] = ['success', $resultat[1]];
            header('Location: retours?id=' . (int) $resultat[2]);
            exit();
        }
        $erreur = $resultat[1];
    }
}

$titreDocument = $type === 'retour' ? 'Demander un retour' : 'Demander un remboursement';
include 'header.php';
?>

<div class="container my-4 page-retour" style="max-width: 780px;">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="commandes">Mes commandes</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= htmlspecialchars($titreDocument) ?></span>
    </nav>
    <h1 class="titre-page-compte mb-1"><?= htmlspecialchars($titreDocument) ?></h1>
    <p class="text-muted">Commande n°<?= $idCommande ?> du <?= date('d/m/Y', strtotime($commande['date_commande'])) ?> · demande possible jusqu’au <?= date('d/m/Y', strtotime(dateLimiteRetour($commande))) ?>. <strong>Site de démonstration :</strong> aucun colis ni paiement réel.</p>

    <?php if ($erreur): ?>
        <div class="alert alert-warning rounded-3" role="alert"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" class="carte-compte">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_commande" value="<?= $idCommande ?>">

        <?php if ($type === 'retour'): ?>
            <fieldset class="mb-4">
                <legend class="h6 fw-bold">Livres à retourner</legend>
                <ul class="lignes-retour">
                    <?php foreach ($lignes as $ligne): ?>
                        <?php $choisie = $saisie['quantites'][$ligne['id_ligne']] ?? ((int) $ligne['quantite'] === 1 && count($lignes) === 1 ? 1 : 0); ?>
                        <li>
                            <img src="<?= htmlspecialchars(urlImage($ligne['couverture'])) ?>" alt="" loading="lazy" decoding="async">
                            <span class="lignes-retour__titre"><strong><?= htmlspecialchars($ligne['titre']) ?></strong><small><?= htmlspecialchars(libelleEtat($ligne['etat'])) ?> · <?= formaterPrix((float) $ligne['prix_unitaire']) ?></small></span>
                            <label class="lignes-retour__quantite">
                                <span class="visually-hidden">Quantité à retourner pour <?= htmlspecialchars($ligne['titre']) ?></span>
                                <select name="quantite[<?= (int) $ligne['id_ligne'] ?>]" class="form-select form-select-sm">
                                    <?php for ($q = 0; $q <= (int) $ligne['quantite']; $q++): ?>
                                        <option value="<?= $q ?>"<?= $q === (int) $choisie ? ' selected' : '' ?>><?= $q === 0 ? 'Garder' : 'Retourner ' . $q ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="small text-muted mb-0">Le remboursement tient compte de la remise et du bon utilisés ; la livraison n’est pas remboursée.</p>
            </fieldset>
        <?php else: ?>
            <p class="mb-4">Cette commande a été annulée le <?= date('d/m/Y', strtotime($commande['date_annulation'])) ?>. Demandez ici le suivi de son remboursement de <strong><?= formaterPrix((float) $commande['total']) ?></strong>.</p>
        <?php endif; ?>

        <div class="mb-3">
            <label for="retour-motif" class="form-label fw-bold">Motif *</label>
            <select id="retour-motif" name="motif" class="form-select" required>
                <option value="" disabled<?= $saisie['motif'] === '' ? ' selected' : '' ?>>Choisissez un motif</option>
                <?php foreach ($motifs as $cle => $libelle): ?>
                    <option value="<?= $cle ?>"<?= $saisie['motif'] === $cle ? ' selected' : '' ?>><?= htmlspecialchars($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="retour-message" class="form-label fw-bold">Précisions <span class="fw-normal text-muted">(obligatoires pour « Autre raison »)</span></label>
            <textarea id="retour-message" name="message" class="form-control" rows="4" maxlength="<?= RETOUR_MESSAGE_MAX ?>" placeholder="Ex : coin de la couverture déchiré, mauvais tome reçu…"><?= htmlspecialchars($saisie['message']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Envoyer ma demande</button>
        <a href="commandes" class="btn btn-link">Annuler</a>
    </form>
</div>

<?php include 'footer.php'; ?>
