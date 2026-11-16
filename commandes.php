<?php
require_once 'config.php';
require_once 'livraison_lib.php';
require_once 'dons_lib.php';
require_once 'retours_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}
$idClient = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'avis_lib.php';
    require_once 'commandes_lib.php';
    $idCommandeAnnulee = (int) ($_POST['id_commande'] ?? 0);
    $resultat = ['danger', 'Action inconnue.'];
    if (!verifierCsrf()) {
        $resultat = ['danger', 'Session expirée, merci de réessayer.'];
    } elseif (($_POST['action'] ?? '') === 'annuler') {
        $stmt = $pdo->prepare("SELECT * FROM commande WHERE id_commande = ? AND id_user = ?");
        $stmt->execute([$idCommandeAnnulee, $idClient]);
        $commandeAAnnuler = $stmt->fetch();
        if (!$commandeAAnnuler) {
            $resultat = ['danger', 'Cette commande ne vous appartient pas.'];
        } elseif (!commandeAnnulableParClient($commandeAAnnuler)) {
            $resultat = ['warning', 'Cette commande ne peut plus être annulée : le délai de ' . COMMANDE_ANNULATION_CLIENT_HEURES . ' heures est dépassé ou elle est déjà annulée. Vous pourrez demander un retour après la livraison.'];
        } else {
            try {
                $annulation = annulerCommande($pdo, $idCommandeAnnulee, $idClient, 'Annulée par le client');
                mailAnnulationCommande($annulation);
                $resultat = ['success', 'Votre commande n° ' . $idCommandeAnnulee . ' est annulée : le montant vous est remboursé et vos points sont rétablis. Un mail de confirmation vous a été envoyé.'];
            } catch (Throwable $e) {
                $resultat = ['danger', $e instanceof RuntimeException ? $e->getMessage() : 'L’annulation n’a pas pu être enregistrée.'];
            }
        }
    }
    $_SESSION['flash_message'] = $resultat;
    header('Location: commandes#commande-' . $idCommandeAnnulee);
    exit();
}
if (!apercuActif()) { avancerSuiviCommandes($pdo, $idClient); }

$stmt = $pdo->prepare("SELECT * FROM commande WHERE id_user = ? ORDER BY date_commande DESC, id_commande DESC");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

$lignesParCommande = [];
if ($commandes) {
    $ids = array_column($commandes, 'id_commande');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmtL = $pdo->prepare("
        SELECT cl.*, l.couverture
        FROM commande_ligne cl
        LEFT JOIN livre l ON l.id_livre = cl.id_livre
        WHERE cl.id_commande IN ($place)
        ORDER BY cl.id_ligne");
    $stmtL->execute($ids);
    foreach ($stmtL->fetchAll() as $ligne) {
        $lignesParCommande[$ligne['id_commande']][] = $ligne;
    }
}

$donsDesLignes = donsParLigneCommande($pdo, array_map('intval', array_merge([], ...array_map(fn($lignes) => array_column($lignes, 'id_ligne'), array_values($lignesParCommande)))));
$retoursDesCommandes = retoursParCommande($pdo, array_column($commandes, 'id_commande'));
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

const LIGNES_COMMANDE_VISIBLES = 3;
const LIGNES_COMMANDE_AVANT_PLIAGE = 4;

function etatLisible($etat) {
    $e = strtolower($etat);
    if ($e == 'tres bon' || $e == 'très bon') return 'Très bon état';
    if ($e == 'bon') return 'Bon état';
    if ($e == 'neuf') return 'Neuf';
    if ($e == 'use' || $e == 'usé') return 'État usé';
    return ucfirst($e);
}

$csrf = jetonCsrfCommandes();

function jetonCsrfCommandes(): string
{
    require_once __DIR__ . '/avis_lib.php';
    return jetonCsrf();
}

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mes commandes</h2>
        <a href="mon-compte" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Mon compte</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>
    <?php if (empty($commandes)): ?>
        <div class="card p-5 text-center shadow-sm border-0 bg-white" style="border-radius: 15px;">
            <i class="bi bi-bag text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Vous n'avez pas encore passé de commande.</h5>
            <a href="./" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Découvrir nos livres</a>
        </div>
    <?php else: ?>
        <?php foreach ($commandes as $cmd): ?>
            <div class="card border-0 shadow-sm bg-white mb-4 carte-commande" id="commande-<?= (int) $cmd['id_commande'] ?>" style="border-radius: 15px;">
                <div class="card-header bg-light border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 p-3" style="border-radius: 15px 15px 0 0;">
                    <div>
                        <span class="fw-bold">Commande n°<?= $cmd['id_commande'] ?></span>
                        <span class="text-muted small ms-2"><?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?></span>
                    </div>
                    <?php $annulee = ($cmd['statut'] ?? 'validee') === 'annulee'; ?>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($annulee): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle" aria-hidden="true"></i> Annulée<?= !empty($cmd['date_annulation']) ? ' le ' . date('d/m/Y', strtotime($cmd['date_annulation'])) : '' ?></span>
                        <?php elseif (commandeLivree($cmd)): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-box-seam" aria-hidden="true"></i> Livrée le <?= date('d/m/Y', strtotime(dateLivraisonCommande($cmd))) ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border rounded-pill"><i class="bi bi-truck" aria-hidden="true"></i> Livraison prévue le <?= date('d/m/Y', strtotime(dateLivraisonCommande($cmd))) ?></span>
                        <?php endif; ?>
                        <?php if (!$annulee && $cmd['points_gagnes'] > 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">+<?= $cmd['points_gagnes'] ?> points</span>
                        <?php endif; ?>
                        <span class="fw-bold fs-5 <?= $annulee ? 'text-muted text-decoration-line-through' : 'text-success' ?>"><?= formaterPrix($cmd['total']) ?></span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php if (!$annulee): ?>
                        <?php $etapeActuelle = etapeCommande($cmd); ?>
                        <ol class="suivi-commande" aria-label="Suivi de la commande">
                            <?php foreach (COMMANDE_ETAPES as $numeroEtape => [$cleEtape, $libelleEtape]): ?>
                                <li class="suivi-commande__etape<?= $numeroEtape < $etapeActuelle ? ' suivi-commande__etape--faite' : ($numeroEtape === $etapeActuelle ? ' suivi-commande__etape--actuelle' : '') ?>"<?= $numeroEtape === $etapeActuelle ? ' aria-current="step"' : '' ?>>
                                    <span class="suivi-commande__point" aria-hidden="true"><?= $numeroEtape <= $etapeActuelle ? '✓' : $numeroEtape + 1 ?></span>
                                    <span class="suivi-commande__libelle"><?= $libelleEtape ?></span>
                                    <small><?= $numeroEtape <= $etapeActuelle ? '' : 'prévu ' ?><?= date('d/m', strtotime(dateEtapeCommande($cmd, $numeroEtape))) ?><?= $numeroEtape === 1 ? ' ' . date('H\\hi', strtotime(dateEtapeCommande($cmd, 1))) : '' ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <?php $lignesCommande = $lignesParCommande[$cmd['id_commande']] ?? []; $plier = count($lignesCommande) > LIGNES_COMMANDE_AVANT_PLIAGE; ?>
                    <?php foreach ($lignesCommande as $indexLigne => $l): ?>
                        <?php if ($plier && $indexLigne === LIGNES_COMMANDE_VISIBLES): ?>
                            <details class="lignes-repliees">
                                <summary><span class="lignes-repliees__ouvrir">Voir les <?= count($lignesCommande) - LIGNES_COMMANDE_VISIBLES ?> autres livres</span><span class="lignes-repliees__fermer">Masquer les <?= count($lignesCommande) - LIGNES_COMMANDE_VISIBLES ?> autres livres</span></summary>
                        <?php endif; ?>
                        <div class="d-flex align-items-center mb-2">
                            <?php if (!empty($l['couverture'])): ?>
                                <img width="40" height="55" src="<?= htmlspecialchars(urlImage($l['couverture'])) ?>" alt="" loading="lazy" decoding="async" class="rounded border" style="width: 40px; height: 55px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 55px;"><i class="bi bi-book text-muted"></i></div>
                            <?php endif; ?>
                            <div class="ms-3">
                                <div class="fw-bold small"><?= htmlspecialchars($l['titre']) ?></div>
                                <div class="text-muted small"><?= etatLisible($l['etat']) ?> · <?= $l['quantite'] ?> × <?= formaterPrix($l['prix_unitaire']) ?></div>
                            </div>
                            <div class="ms-auto fw-medium small text-end">
                                <?= formaterPrix($l['prix_unitaire'] * $l['quantite']) ?>
                                <?php if (commandeLivree($cmd) && !empty($l['id_livre'])): ?>
                                    <?php $donnes = $donsDesLignes[(int) $l['id_ligne']] ?? 0; ?>
                                    <div class="mt-1">
                                        <?php if ($donnes < (int) $l['quantite']): ?>
                                            <a href="don_creer?ligne=<?= (int) $l['id_ligne'] ?>" class="don-commande link-success"><i class="bi bi-gift" aria-hidden="true"></i> Donner ce livre</a>
                                        <?php else: ?>
                                            <span class="don-commande text-muted"><i class="bi bi-check2" aria-hidden="true"></i> Don proposé</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($plier && $indexLigne === count($lignesCommande) - 1): ?>
                            </details>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <hr class="my-2">
                    <div class="small text-secondary">
                        <?php if ($annulee): ?>
                            <div class="text-danger mb-1">
                                <?php if (!empty($cmd['motif_annulation'])): ?><strong>Motif de l'annulation :</strong> <?= htmlspecialchars($cmd['motif_annulation']) ?><br><?php endif; ?>
                                Le montant payé vous est remboursé et vos points ont été rétablis.
                            </div>
                        <?php endif; ?>
                        <?php if ($cmd['remise'] > 0): ?>Remise : -<?= formaterPrix($cmd['remise']) ?> · <?php endif; ?>
                        <?php if (($cmd['reduction_points'] ?? 0) > 0): ?>Bon de réduction : -<?= formaterPrix($cmd['reduction_points']) ?> · <?php endif; ?>
                        Livraison : <?= $cmd['frais_livraison'] > 0 ? formaterPrix($cmd['frais_livraison']) : 'offerte' ?>
                        · Livré à <?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?>, <?= htmlspecialchars($cmd['adresse']) ?>
                    </div>
                    <?php if (commandeAnnulableParClient($cmd)): ?>
                        <form method="POST" class="annulation-commande" data-confirmer="Annuler la commande n° <?= (int) $cmd['id_commande'] ?> ? Les livres sont remis en vente et le montant vous est remboursé." data-bouton-confirmer="Annuler la commande">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="annuler">
                            <input type="hidden" name="id_commande" value="<?= (int) $cmd['id_commande'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-x-circle" aria-hidden="true"></i> Annuler ma commande</button>
                            <small class="text-muted">possible jusqu’au <?= date('d/m/Y à H\\hi', strtotime(dateLimiteAnnulationClient($cmd))) ?></small>
                        </form>
                    <?php endif; ?>
                    <?php $demandeRetour = retourActifCommande($retoursDesCommandes[(int) $cmd['id_commande']] ?? []); $typeRetour = typeRetourPossible($cmd); ?>
                    <?php if ($demandeRetour || $typeRetour): ?>
                        <div class="liens-retour-commande">
                            <?php if ($demandeRetour): ?>
                                <a href="retours?id=<?= (int) $demandeRetour['id_retour'] ?>" class="btn btn-sm btn-outline-bibli rounded-pill"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> Suivre la demande <?= htmlspecialchars((string) $demandeRetour['reference']) ?></a>
                                <span class="statut-retour statut-retour--<?= RETOUR_STATUTS[$demandeRetour['statut']][1] ?? 'attente' ?>"><?= libelleStatutRetour($demandeRetour['statut']) ?></span>
                            <?php else: ?>
                                <a href="retour_demander?commande=<?= (int) $cmd['id_commande'] ?>" class="btn btn-sm btn-outline-bibli rounded-pill"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> <?= $typeRetour === 'retour' ? 'Retourner des livres' : 'Suivre le remboursement' ?></a>
                                <small class="text-muted">jusqu’au <?= date('d/m/Y', strtotime(dateLimiteRetour($cmd))) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
