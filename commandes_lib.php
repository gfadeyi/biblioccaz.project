<?php
require_once __DIR__ . '/compte_lib.php';
require_once __DIR__ . '/points_lib.php';
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/notifications_lib.php';

const STATUTS_COMMANDE = ['validee' => 'Validée', 'annulee' => 'Annulée'];
const MOTIF_ANNULATION_MAX = 255;

function lignesDeCommande(PDO $pdo, int $idCommande): array
{
    $stmt = $pdo->prepare("
        SELECT cl.*, l.couverture, e.quantite AS stock_offre
        FROM commande_ligne cl
        LEFT JOIN livre l ON l.id_livre = cl.id_livre
        LEFT JOIN exemplaire e ON e.id_exemplaire = cl.id_exemplaire
        WHERE cl.id_commande = ?
        ORDER BY cl.id_ligne");
    $stmt->execute([$idCommande]);
    return $stmt->fetchAll();
}

function exemplairePourRetour(PDO $pdo, array $ligne): int
{
    if (!empty($ligne['id_exemplaire'])) {
        $stmt = $pdo->prepare("SELECT id_exemplaire FROM exemplaire WHERE id_exemplaire = ? FOR UPDATE");
        $stmt->execute([$ligne['id_exemplaire']]);
        $idExemplaire = (int) $stmt->fetchColumn();
        if ($idExemplaire) {
            return $idExemplaire;
        }
    }
    $stmt = $pdo->prepare("SELECT id_exemplaire FROM exemplaire WHERE id_livre = ? AND etat = ? AND prix = ? ORDER BY id_exemplaire LIMIT 1 FOR UPDATE");
    $stmt->execute([$ligne['id_livre'], $ligne['etat'], $ligne['prix_unitaire']]);
    $idExemplaire = (int) $stmt->fetchColumn();
    if (!$idExemplaire) {
        $pdo->prepare("INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) VALUES (?, ?, ?, 'vente', 1, 0)")
            ->execute([$ligne['id_livre'], $ligne['etat'], $ligne['prix_unitaire']]);
        $idExemplaire = (int) $pdo->lastInsertId();
    }
    $pdo->prepare("UPDATE commande_ligne SET id_exemplaire = ? WHERE id_ligne = ?")->execute([$idExemplaire, $ligne['id_ligne']]);
    return $idExemplaire;
}

function annulerCommande(PDO $pdo, int $idCommande, int $idAnnulateur, string $motif): array
{
    $motif = mb_substr(trim($motif), 0, MOTIF_ANNULATION_MAX);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.email, u.prenom AS prenom_client, u.pseudo
            FROM commande c
            INNER JOIN `user` u ON u.id = c.id_user
            WHERE c.id_commande = ?
            FOR UPDATE");
        $stmt->execute([$idCommande]);
        $commande = $stmt->fetch();
        if (!$commande) {
            throw new RuntimeException("Cette commande n'existe pas.");
        }
        if ($commande['statut'] === 'annulee') {
            throw new RuntimeException("Cette commande est déjà annulée.");
        }

        $lignes = lignesDeCommande($pdo, $idCommande);
        $remettreEnStock = $pdo->prepare("UPDATE exemplaire SET quantite = quantite + ? WHERE id_exemplaire = ?");
        $livresRemis = [];
        $titresSansFiche = [];
        foreach ($lignes as $ligne) {
            if (empty($ligne['id_livre'])) {
                $titresSansFiche[] = $ligne['titre'];
                continue;
            }
            $remettreEnStock->execute([(int) $ligne['quantite'], exemplairePourRetour($pdo, $ligne)]);
            $livresRemis[(int) $ligne['id_livre']] = true;
        }

        $bilanPoints = annulerPointsCommande($pdo, $commande);
        $pdo->prepare("UPDATE commande SET statut = 'annulee', date_annulation = NOW(), id_annulateur = ?, motif_annulation = ? WHERE id_commande = ?")
            ->execute([$idAnnulateur, $motif !== '' ? $motif : null, $idCommande]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $clientsPrevenus = 0;
    foreach (array_keys($livresRemis) as $idLivre) {
        $clientsPrevenus += notifierRetourEnStock($pdo, $idLivre);
    }
    insertLog('COMMANDE', "Commande #$idCommande annulée" . ($motif !== '' ? " : $motif" : ''));

    return [
        'commande' => $commande,
        'lignes' => $lignes,
        'motif' => $motif,
        'points' => $bilanPoints,
        'solde' => soldePoints($pdo, (int) $commande['id_user']),
        'clients_prevenus' => $clientsPrevenus,
        'titres_sans_fiche' => $titresSansFiche,
    ];
}

function mailAnnulationCommande(array $annulation): void
{
    $commande = $annulation['commande'];
    $texte = ["Bonjour " . ($commande['prenom_client'] ?: $commande['pseudo']) . ",", ""];
    $texte[] = "Votre commande n° {$commande['id_commande']} du " . date('d/m/Y', strtotime($commande['date_commande'])) . " a été annulée.";
    if ($annulation['motif'] !== '') {
        $texte[] = "Motif : {$annulation['motif']}";
    }
    $texte[] = "";
    foreach ($annulation['lignes'] as $ligne) {
        $texte[] = "- {$ligne['titre']} (" . libelleEtat($ligne['etat']) . ") x {$ligne['quantite']}";
    }
    $texte[] = "";
    $texte[] = "Le montant payé (" . formaterPrix((float) $commande['total']) . ") vous sera remboursé.";
    if ($annulation['points']['rendus'] > 0 || $annulation['points']['retires'] > 0) {
        $texte[] = "";
        $texte[] = "Vos points fidélité :";
        if ($annulation['points']['rendus'] > 0) {
            $texte[] = "- Points du bon de réduction rendus : {$annulation['points']['rendus']}";
        }
        if ($annulation['points']['retires'] > 0) {
            $texte[] = "- Points gagnés avec cette commande retirés : {$annulation['points']['retires']}";
        }
        $texte[] = "- Nouveau solde : {$annulation['solde']} points";
    }
    $texte[] = "";
    $texte[] = "Vos commandes : " . SITE_URL . "/commandes.php";
    notifierParMail($commande['email'], "Annulation de votre commande n° {$commande['id_commande']}", implode("\n", $texte));
}
