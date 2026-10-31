<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'panier_calcul.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

require_once 'compte_lib.php';
require_once 'points_lib.php';
require_once 'avis_lib.php';
require_once 'notifications_lib.php';
require_once 'icones_lib.php';
require_once 'livres_lib.php';

if (!verifierCsrf()) {
    header("Location: achat.php?erreur=" . urlencode("Session expirée, merci de valider à nouveau votre commande."));
    exit();
}

$idAdresse = (int) ($_POST['id_adresse'] ?? 0);
if ($idAdresse > 0) {
    $stmtA = $pdo->prepare("SELECT * FROM adresse_livraison WHERE id_adresse = ? AND id_user = ?");
    $stmtA->execute([$idAdresse, $_SESSION['user_id']]);
    $choisie = $stmtA->fetch();
    if (!$choisie) {
        header("Location: achat.php?erreur=" . urlencode("Adresse introuvable, merci d'en choisir une autre."));
        exit();
    }
    $prenom = $choisie['prenom'];
    $nom = $choisie['nom'];
    $adresse = adresseEnLigne($choisie);
} else {
    $nouvelle = [];
    foreach (['prenom', 'nom', 'adresse', 'complement', 'code_postal', 'ville'] as $c) {
        $nouvelle[$c] = trim($_POST[$c] ?? '');
    }
    if ($nouvelle['prenom'] === '' || $nouvelle['nom'] === '' || $nouvelle['adresse'] === '' || $nouvelle['ville'] === '' || !preg_match('/^\d{5}$/', $nouvelle['code_postal'])) {
        header("Location: achat.php?erreur=" . urlencode("Adresse incomplète : vérifiez la rue, le code postal (5 chiffres) et la ville."));
        exit();
    }
    $prenom = $nouvelle['prenom'];
    $nom = $nouvelle['nom'];
    $adresse = adresseEnLigne($nouvelle);
    if (!empty($_POST['enregistrer']) && count(adressesDe($pdo, (int) $_SESSION['user_id'])) < ADRESSES_MAX) {
        try {
            $pdo->prepare("INSERT INTO adresse_livraison (id_user, libelle, prenom, nom, adresse, complement, code_postal, ville, par_defaut)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$_SESSION['user_id'], $nouvelle['ville'], $prenom, $nom, $nouvelle['adresse'], $nouvelle['complement'] ?: null,
                           $nouvelle['code_postal'], $nouvelle['ville'], adressesDe($pdo, (int) $_SESSION['user_id']) ? 0 : 1]);
        } catch (PDOException $e) {
        }
    }
}

$idUser = (int) $_SESSION['user_id'];
$choixBon = is_string($_POST['bon'] ?? null) ? $_POST['bon'] : '';
$niveauAvant = niveauMembre($pdo, $idUser);

try {
    $pdo->beginTransaction();

    $stmtEx = $pdo->prepare("
        SELECT e.id_exemplaire, e.id_livre, e.prix, e.etat, e.quantite, l.titre
        FROM exemplaire e
        INNER JOIN livre l ON l.id_livre = e.id_livre
        WHERE e.id_exemplaire = ? AND l.is_valide = 1 AND e.is_disponible = 1 AND " . conditionExemplaireMagasin() . "
        FOR UPDATE");

    $lignes = [];
    foreach ($_SESSION['panier'] as $item) {
        $stmtEx->execute([$item['id_exemplaire']]);
        $ex = $stmtEx->fetch();
        $qte = (int) $item['quantite'];

        if (!$ex || $ex['quantite'] < $qte) {
            throw new RuntimeException("Stock insuffisant pour « " . $item['titre'] . " ».");
        }

        $lignes[] = [
            'id_exemplaire' => $ex['id_exemplaire'],
            'id_livre'      => $ex['id_livre'],
            'titre'         => $ex['titre'],
            'etat'          => $ex['etat'],
            'prix'          => (float) $ex['prix'],
            'quantite'      => $qte,
        ];
    }

    $solde = soldePoints($pdo, $idUser, true);
    $totauxSansBon = calculerTotauxPanier($lignes, 0, $niveauAvant['livraison_des'], pointsRegle($pdo, 'achat'));
    $option = null;
    if ($choixBon !== '') {
        $options = optionsReduction($pdo, $idUser, $totauxSansBon, $solde);
        $option = $options[$choixBon] ?? null;
        if (!$option || !$option['possible']) {
            throw new RuntimeException("Ce bon ne peut pas être utilisé pour cette commande : " . ($option['raison'] ?? 'choix inconnu') . ".");
        }
        if ($option['type'] === 'cadeau') {
            $stmtBon = $pdo->prepare("SELECT id_bon FROM bon_cadeau WHERE id_bon = ? AND id_user = ? AND date_utilisation IS NULL AND date_expiration >= CURDATE() FOR UPDATE");
            $stmtBon->execute([$option['id_bon'], $idUser]);
            if (!$stmtBon->fetchColumn()) {
                throw new RuntimeException("Ce bon cadeau n'est plus disponible.");
            }
        }
    }

    $totaux = calculerTotauxPanier($lignes, $option['montant'] ?? 0, $niveauAvant['livraison_des'], pointsRegle($pdo, 'achat'));
    $bonus = bonusNiveau($totaux['points'], $niveauAvant);

    $stmtAnciennes = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_user = ? AND statut <> 'annulee'");
    $stmtAnciennes->execute([$idUser]);
    $stmtPremier = $pdo->prepare("SELECT COUNT(*) FROM point_mouvement WHERE id_user = ? AND type = 'premier_achat'");
    $stmtPremier->execute([$idUser]);
    $premierAchat = (int) $stmtAnciennes->fetchColumn() === 0 && (int) $stmtPremier->fetchColumn() === 0;

    $stmtCmd = $pdo->prepare("
        INSERT INTO commande (id_user, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes,
                              points_utilises, reduction_points, bonus_niveau, id_bon_cadeau)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtCmd->execute([
        $idUser, $prenom, $nom, $adresse,
        $totaux['sous_total'], $totaux['remise'], $totaux['frais_livraison'], $totaux['total'], $totaux['points'],
        $option['points'] ?? 0, $totaux['reduction'], $bonus, $option['id_bon'] ?? null,
    ]);
    $idCommande = (int) $pdo->lastInsertId();
    $reference = 'commande:' . $idCommande;

    $stmtLigne = $pdo->prepare("
        INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE exemplaire SET quantite = quantite - ? WHERE id_exemplaire = ?");

    foreach ($lignes as $l) {
        $stmtLigne->execute([$idCommande, $l['id_livre'], $l['id_exemplaire'], $l['titre'], $l['etat'], $l['prix'], $l['quantite']]);
        $stmtStock->execute([$l['quantite'], $l['id_exemplaire']]);
        insertLog('ACHAT', "Commande #$idCommande : {$l['quantite']} x livre ID #{$l['id_livre']}. Stock mis a jour.");
    }

    if ($option && $option['type'] === 'points') {
        crediterPoints($pdo, $idUser, -$option['points'], 'depense', 'Bon de ' . formaterPrix($option['montant']) . " (commande n° $idCommande)", $reference);
    } elseif ($option && $option['type'] === 'cadeau') {
        $pdo->prepare("UPDATE bon_cadeau SET date_utilisation = NOW(), id_commande = ? WHERE id_bon = ?")->execute([$idCommande, $option['id_bon']]);
    }
    if ($totaux['points'] > 0) {
        crediterPoints($pdo, $idUser, $totaux['points'], 'achat', "Commande n° $idCommande", $reference);
    }
    if ($bonus > 0) {
        crediterPoints($pdo, $idUser, $bonus, 'bonus_niveau', "Bonus {$niveauAvant['nom']} +{$niveauAvant['bonus']} % (commande n° $idCommande)", $reference);
    }
    if ($premierAchat) {
        crediterPoints($pdo, $idUser, pointsRegle($pdo, 'premier_achat'), 'premier_achat', 'Bonus premier achat', $reference);
    }
    $nouveauxBadges = verifierBadges($pdo, $idUser);

    $pdo->commit();
    insertLog('ACHAT', "Commande #$idCommande validee ({$totaux['total']} EUR, +{$totaux['points']} points, -" . ($option['points'] ?? 0) . " points).");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    insertLog('ERREUR', "Echec de la validation de commande : " . $e->getMessage());
    $message = $e instanceof RuntimeException ? $e->getMessage() : "Une erreur est survenue lors de la validation de la commande.";
    header("Location: panier.php?error=" . urlencode($message));
    exit();
}

$pointsGagnes = $totaux['points'] + $bonus + ($premierAchat ? pointsRegle($pdo, 'premier_achat') : 0);
$pointsDepenses = (int) ($option['points'] ?? 0);
$nouveauSolde = soldePoints($pdo, $idUser);
$niveauApres = niveauMembre($pdo, $idUser);
$changementNiveau = $niveauApres['index'] > $niveauAvant['index'];

$stmtEmail = $pdo->prepare("SELECT email FROM user WHERE id = ?");
$stmtEmail->execute([$idUser]);
$lignesMail = ["Bonjour $prenom,", "", "Merci pour votre commande n° $idCommande !", ""];
foreach ($lignes as $l) {
    $lignesMail[] = "- {$l['titre']} (" . libelleEtat($l['etat']) . ") x {$l['quantite']} : " . formaterPrix($l['prix'] * $l['quantite']);
}
$lignesMail[] = "";
$lignesMail[] = "Sous-total : " . formaterPrix($totaux['sous_total']);
if ($totaux['remise'] > 0) {
    $lignesMail[] = "Remise panier : -" . formaterPrix($totaux['remise']);
}
if ($totaux['reduction'] > 0) {
    $lignesMail[] = ($pointsDepenses > 0 ? "Bon de points ($pointsDepenses points)" : "Bon cadeau") . " : -" . formaterPrix($totaux['reduction']);
}
$lignesMail[] = "Livraison : " . ($totaux['frais_livraison'] > 0 ? formaterPrix($totaux['frais_livraison']) : 'offerte');
$lignesMail[] = "Total payé : " . formaterPrix($totaux['total']);
$lignesMail[] = "";
$lignesMail[] = "Vos points fidélité :";
if ($pointsDepenses > 0) {
    $lignesMail[] = "- Points utilisés : $pointsDepenses";
}
$lignesMail[] = "- Points gagnés : $pointsGagnes" . ($bonus > 0 ? " (dont $bonus de bonus {$niveauAvant['nom']})" : '') . ($premierAchat ? " (dont " . pointsRegle($pdo, 'premier_achat') . " pour votre premier achat)" : '');
$lignesMail[] = "- Nouveau solde : $nouveauSolde points";
if ($changementNiveau) {
    $lignesMail[] = "";
    $lignesMail[] = "Bravo, vous devenez « {$niveauApres['nom']} » !";
}
$lignesMail[] = "";
$lignesMail[] = "Suivre votre commande : " . SITE_URL . "/commandes.php";
$lignesMail[] = "Vos points et badges : " . SITE_URL . "/points_badges.php";
notifierParMail((string) $stmtEmail->fetchColumn(), "Confirmation de votre commande n° $idCommande", implode("\n", $lignesMail));

unset($_SESSION['panier']);
include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="carte-confirmation">
                <div class="text-center mb-4">
                    <span class="icone-succes" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <h1 class="h2 fw-bold mt-3 mb-2">Merci pour votre commande !</h1>
                    <p class="text-secondary mb-0">Votre commande <strong>n° <?= $idCommande ?></strong> est enregistrée. Un récapitulatif vient de vous être envoyé par mail.</p>
                </div>

                <div class="ligne-recap"><span>Sous-total</span><span><?= formaterPrix($totaux['sous_total']) ?></span></div>
                <?php if ($totaux['remise'] > 0): ?>
                    <div class="ligne-recap remise"><span>Remise panier</span><span>-<?= formaterPrix($totaux['remise']) ?></span></div>
                <?php endif; ?>
                <?php if ($totaux['reduction'] > 0): ?>
                    <div class="ligne-recap remise"><span><?= $pointsDepenses > 0 ? "Bon de points ($pointsDepenses points)" : 'Bon cadeau' ?></span><span>-<?= formaterPrix($totaux['reduction']) ?></span></div>
                <?php endif; ?>
                <div class="ligne-recap"><span>Livraison</span><span><?= $totaux['frais_livraison'] > 0 ? formaterPrix($totaux['frais_livraison']) : 'Offerte' ?></span></div>
                <div class="ligne-recap total"><span>Total payé</span><span><?= formaterPrix($totaux['total']) ?></span></div>

                <div class="bilan-points">
                    <h2 class="bilan-points__titre"><?= iconeDessin('medaille') ?> Vos points fidélité</h2>
                    <dl class="bilan-points__chiffres">
                        <?php if ($pointsDepenses > 0): ?>
                            <div><dt>Utilisés</dt><dd class="negatif">-<?= $pointsDepenses ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Gagnés</dt><dd class="positif">+<?= $pointsGagnes ?></dd></div>
                        <div><dt>Nouveau solde</dt><dd><?= $nouveauSolde ?></dd></div>
                    </dl>
                    <?php if ($bonus > 0 || $premierAchat): ?>
                        <p class="bilan-points__detail">
                            Dont <?= $totaux['points'] ?> pour vos achats<?= $bonus > 0 ? ", $bonus de bonus " . htmlspecialchars($niveauAvant['nom']) : '' ?><?= $premierAchat ? ' et ' . pointsRegle($pdo, 'premier_achat') . ' pour votre premier achat' : '' ?>.
                        </p>
                    <?php endif; ?>
                    <?php if ($changementNiveau): ?>
                        <p class="bilan-points__niveau"><i class="bi bi-stars" aria-hidden="true"></i> Bravo, vous devenez <strong><?= htmlspecialchars($niveauApres['nom']) ?></strong> !</p>
                    <?php endif; ?>
                    <?php if ($nouveauxBadges): ?>
                        <p class="bilan-points__niveau"><i class="bi bi-award" aria-hidden="true"></i> <?= count($nouveauxBadges) ?> nouveau<?= count($nouveauxBadges) > 1 ? 'x' : '' ?> badge<?= count($nouveauxBadges) > 1 ? 's' : '' ?> débloqué<?= count($nouveauxBadges) > 1 ? 's' : '' ?> !</p>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a href="points_badges.php" class="btn btn-bibli py-3 rounded-pill fw-bold">Voir mes points et badges</a>
                    <a href="commandes.php" class="btn btn-outline-bibli py-3 rounded-pill fw-bold">Voir mes commandes</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
