<?php
require_once 'config.php';
require_once 'ventes_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

$transactions = ['achats' => [], 'ventes' => []];
$indisponible = false;
try {
    expirerDemandes($pdo);
    $stmt = $pdo->prepare("
        SELECT d.*, l.titre, l.auteur, l.couverture, e.ville_remise,
               a.pseudo AS acheteur_pseudo, v.pseudo AS vendeur_pseudo,
               (SELECT p.fichier FROM exemplaire_photo p WHERE p.id_exemplaire = d.id_annonce ORDER BY p.ordre, p.id_photo LIMIT 1) AS photo
        FROM demande_achat d
        INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce
        INNER JOIN livre l ON l.id_livre = e.id_livre
        INNER JOIN `user` a ON a.id = d.id_acheteur
        INNER JOIN `user` v ON v.id = d.id_vendeur
        WHERE ? IN (d.id_acheteur, d.id_vendeur)
        ORDER BY (d.statut = 'validee' AND d.date_confirmation IS NULL) DESC, FIELD(d.statut, 'en_file', 'en_attente', 'acceptee') DESC, COALESCE(d.date_fin, d.date_demande) DESC");
    $stmt->execute([$idUser]);
    foreach ($stmt->fetchAll() as $transaction) {
        $transactions[(int) $transaction['id_vendeur'] === $idUser ? 'ventes' : 'achats'][] = $transaction;
    }
} catch (PDOException $e) {
    $indisponible = true;
}

function actionAttendue(array $transaction, bool $estVendeur): string
{
    if ($estVendeur && $transaction['statut'] === 'en_attente') {
        return 'À vous de répondre';
    }
    if ($estVendeur && $transaction['statut'] === 'acceptee') {
        return 'Échange à faire';
    }
    if (!$estVendeur && receptionAConfirmer($transaction)) {
        return 'Confirmez la réception';
    }
    return '';
}

$enCours = fn(array $liste) => count(array_filter($liste, fn($t) => in_array($t['statut'], STATUTS_DEMANDE_ACTIFS, true) || receptionAConfirmer($t)));
$ongletDemande = $_GET['onglet'] ?? '';
$onglet = in_array($ongletDemande, ['achats', 'ventes'], true) ? $ongletDemande
    : (!$transactions['achats'] && $transactions['ventes'] ? 'ventes' : 'achats');
$liste = $transactions[$onglet];
$estVendeur = $onglet === 'ventes';

$titreDocument = 'Mes ventes et achats';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-mes-transactions">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes ventes et achats</span>
    </nav>

    <div class="entete-mes-annonces">
        <div>
            <h1 class="titre-page-compte mb-1">Mes ventes et achats</h1>
            <p class="text-muted mb-0">Vos échanges de livres avec les autres lecteurs, de la demande au reçu.</p>
        </div>
        <a href="mes_annonces.php" class="bouton bouton-contour bouton-petit"><i class="bi bi-tag" aria-hidden="true"></i> Mes annonces</a>
    </div>

    <?php if ($indisponible): ?>
        <div class="alert alert-warning rounded-3">Les ventes entre lecteurs sont momentanément indisponibles.</div>
    <?php else: ?>
        <nav class="onglets-transactions" aria-label="Type de transaction">
            <?php foreach (['achats' => ['Mes achats', 'bag'], 'ventes' => ['Mes ventes', 'tag']] as $cle => [$libelle, $icone]): ?>
                <a href="?onglet=<?= $cle ?>" class="onglets-transactions__lien<?= $cle === $onglet ? ' actif' : '' ?>" <?= $cle === $onglet ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= $icone ?>" aria-hidden="true"></i> <?= $libelle ?>
                    <?php if ($n = $enCours($transactions[$cle])): ?><span class="onglets-transactions__compteur"><?= $n ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (!$liste): ?>
            <div class="carte-compte etat-vide-compte">
                <?= iconeDessin($estVendeur ? 'etiquette' : 'cabas') ?>
                <h2 class="h5"><?= $estVendeur ? 'Aucune demande reçue pour le moment' : 'Aucun achat entre lecteurs' ?></h2>
                <p><?= $estVendeur
                    ? 'Quand un lecteur demande à acheter un de vos livres, sa demande apparaît ici.'
                    : 'Des lecteurs revendent leurs livres à petit prix : faites-leur une demande depuis une annonce.' ?></p>
                <a href="<?= $estVendeur ? 'mes_annonces.php' : 'catalogue.php?offre=particulier' ?>" class="bouton bouton-principal bouton-petit">
                    <?= $estVendeur ? 'Voir mes annonces' : 'Voir les annonces entre lecteurs' ?>
                </a>
            </div>
        <?php else: ?>
            <ul class="liste-transactions">
                <?php foreach ($liste as $t): ?>
                    <?php $action = actionAttendue($t, $estVendeur); ?>
                    <li>
                        <a href="transaction.php?id=<?= (int) $t['id_demande'] ?>" class="carte-compte ligne-transaction<?= $action ? ' ligne-transaction--action' : '' ?>">
                            <img src="<?= htmlspecialchars(urlPhotoAnnonce($t['photo'], $t['couverture'])) ?>" alt="" class="ligne-transaction__visuel" loading="lazy">
                            <span class="ligne-transaction__contenu">
                                <span class="ligne-transaction__titre"><?= htmlspecialchars($t['titre']) ?></span>
                                <span class="ligne-transaction__qui">
                                    <?= $estVendeur ? 'Acheteur' : 'Vendeur' ?> : @<?= htmlspecialchars($estVendeur ? $t['acheteur_pseudo'] : $t['vendeur_pseudo']) ?>
                                    · <?= htmlspecialchars(MODES_REMISE[$t['mode_remise']][0] ?? '') ?>
                                    · <?= date('d/m/Y', strtotime($t['date_demande'])) ?>
                                </span>
                                <span class="ligne-transaction__pastilles">
                                    <?= pastilleStatutDemande($t) ?>
                                    <?php if ($action): ?><span class="ligne-transaction__action"><i class="bi bi-arrow-right-circle" aria-hidden="true"></i> <?= $action ?></span><?php endif; ?>
                                </span>
                            </span>
                            <span class="ligne-transaction__prix"><?= formaterPrix($t['prix']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
