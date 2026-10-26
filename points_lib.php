<?php

const NIVEAUX = [
    ['seuil' => 0, 'nom' => 'Lecteur curieux', 'bonus' => 0, 'livraison_des' => 20, 'prioritaire' => false, 'bon_mensuel' => false],
    ['seuil' => 150, 'nom' => 'Lecteur assidu', 'bonus' => 0, 'livraison_des' => 20, 'prioritaire' => false, 'bon_mensuel' => false],
    ['seuil' => 400, 'nom' => 'Dévoreur de pages', 'bonus' => 3, 'livraison_des' => 20, 'prioritaire' => false, 'bon_mensuel' => false],
    ['seuil' => 900, 'nom' => 'Rat de bibliothèque', 'bonus' => 5, 'livraison_des' => 15, 'prioritaire' => false, 'bon_mensuel' => false],
    ['seuil' => 1800, 'nom' => 'Bibliophile', 'bonus' => 8, 'livraison_des' => 15, 'prioritaire' => true, 'bon_mensuel' => false],
    ['seuil' => 3500, 'nom' => 'Gardien de la bibliothèque', 'bonus' => 10, 'livraison_des' => 0, 'prioritaire' => true, 'bon_mensuel' => true],
];

const BONS_POINTS = [100 => 5.00, 200 => 10.50, 400 => 21.50, 700 => 38.00];
const BON_MINIMUM_LIVRES = 15;
const BON_PLAFOND = 0.5;
const BON_ANNIVERSAIRE_MONTANT = 5.00;

const POINTS_PREMIER_ACHAT = 20;
const POINTS_PREMIER_AVIS = 10;
const POINTS_PRESENCE = 10;
const AVIS_PAYES_PAR_MOIS = 4;

const BADGES = [
    'lecteur_fidele' => ['nom' => 'Lecteur fidèle', 'icone' => 'pile', 'paliers' => [1, 10, 50], 'unite' => ['livre acheté', 'livres achetés']],
    'plume_critique' => ['nom' => 'Plume critique', 'icone' => 'plume', 'paliers' => [1, 10, 30], 'unite' => ['avis validé', 'avis validés']],
    'coeur_genereux' => ['nom' => 'Cœur généreux', 'icone' => 'seconde-vie', 'paliers' => [1, 5, 20], 'unite' => ['don récupéré', 'dons récupérés']],
    'habitue_rencontres' => ['nom' => 'Habitué des rencontres', 'icone' => 'ticket', 'paliers' => [1, 5, 15], 'unite' => ['présence validée', 'présences validées']],
    'un_an' => ['nom' => 'Un an parmi nous', 'icone' => 'tampon', 'paliers' => [5], 'unite' => ['commande', 'commandes'], 'rare' => 'Plus d\'un an d\'inscription et au moins 5 commandes'],
    'explorateur' => ['nom' => 'Explorateur', 'icone' => 'loupe', 'paliers' => [6], 'unite' => ['univers exploré', 'univers explorés'], 'rare' => 'Un livre acheté dans chacun des 6 univers'],
    'collectionneur' => ['nom' => 'Collectionneur', 'icone' => 'marque-page', 'paliers' => [25], 'unite' => ['livre du même univers', 'livres du même univers'], 'rare' => '25 livres achetés dans un même univers'],
    'pilier' => ['nom' => 'Pilier de la communauté', 'icone' => 'medaille', 'paliers' => [4], 'unite' => ['badge or', 'badges or'], 'rare' => '4 badges au palier or'],
];

const PALIERS_BADGES = [1 => 'Bronze', 2 => 'Argent', 3 => 'Or'];
const BONUS_BADGES = [1 => 5, 2 => 15, 3 => 30];
const BONUS_BADGE_RARE = 30;

const TYPES_MOUVEMENT = [
    'achat' => 'Achat',
    'bonus_niveau' => 'Bonus de niveau',
    'premier_achat' => 'Premier achat',
    'avis' => 'Avis',
    'premier_avis' => 'Premier avis',
    'don' => 'Don',
    'presence' => 'Événement',
    'badge' => 'Badge',
    'ajustement' => 'Ajustement',
    'depense' => 'Bon de réduction',
    'remboursement' => 'Points rendus',
    'annulation' => 'Annulation',
];

const STATUTS_MOUVEMENT = ['valide' => 'Validé', 'en_attente' => 'En attente', 'annule' => 'Annulé', 'refuse' => 'Refusé'];

function crediterPoints(PDO $pdo, int $idUser, int $points, string $type, string $libelle, string $reference, string $statut = 'valide'): bool
{
    $stmt = $pdo->prepare("INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idUser, $points, $type, mb_substr($libelle, 0, 255), $reference, $statut]);
    $insere = $stmt->rowCount() > 0;
    if ($insere && $statut === 'valide') {
        recalculerSolde($pdo, $idUser);
    }
    return $insere;
}

function changerStatutMouvement(PDO $pdo, int $idUser, string $type, string $reference, array $statutsActuels, string $nouveauStatut): bool
{
    $marqueurs = implode(', ', array_fill(0, count($statutsActuels), '?'));
    $stmt = $pdo->prepare("UPDATE point_mouvement SET statut = ? WHERE id_user = ? AND type = ? AND reference = ? AND statut IN ($marqueurs)");
    $stmt->execute(array_merge([$nouveauStatut, $idUser, $type, $reference], $statutsActuels));
    if ($stmt->rowCount() > 0) {
        recalculerSolde($pdo, $idUser);
        return true;
    }
    return false;
}

function recalculerSolde(PDO $pdo, int $idUser): void
{
    $pdo->prepare("UPDATE user SET solde_points = GREATEST(0, (SELECT COALESCE(SUM(points), 0) FROM point_mouvement WHERE id_user = ? AND statut = 'valide')) WHERE id = ?")
        ->execute([$idUser, $idUser]);
}

function cumulPoints(PDO $pdo, int $idUser): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM point_mouvement WHERE id_user = ? AND statut = 'valide' AND type NOT IN ('depense', 'remboursement')");
    $stmt->execute([$idUser]);
    return max(0, (int) $stmt->fetchColumn());
}

function pointsEnAttente(PDO $pdo, int $idUser): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM point_mouvement WHERE id_user = ? AND statut = 'en_attente'");
    $stmt->execute([$idUser]);
    return (int) $stmt->fetchColumn();
}

function soldePoints(PDO $pdo, int $idUser, bool $verrouiller = false): int
{
    $stmt = $pdo->prepare("SELECT solde_points FROM user WHERE id = ?" . ($verrouiller ? " FOR UPDATE" : ""));
    $stmt->execute([$idUser]);
    return (int) $stmt->fetchColumn();
}

function niveauPour(int $cumul): array
{
    $index = 0;
    foreach (NIVEAUX as $i => $niveau) {
        if ($cumul >= $niveau['seuil']) {
            $index = $i;
        }
    }
    $niveau = NIVEAUX[$index] + ['index' => $index, 'cumul' => $cumul, 'prochain' => null, 'reste' => 0, 'progression' => 1.0];
    if (isset(NIVEAUX[$index + 1])) {
        $suivant = NIVEAUX[$index + 1];
        $niveau['prochain'] = $suivant + ['index' => $index + 1];
        $niveau['reste'] = $suivant['seuil'] - $cumul;
        $niveau['progression'] = ($cumul - $niveau['seuil']) / ($suivant['seuil'] - $niveau['seuil']);
    }
    return $niveau;
}

function niveauMembre(PDO $pdo, int $idUser): array
{
    try {
        return niveauPour(cumulPoints($pdo, $idUser));
    } catch (PDOException $e) {
        return niveauPour(0);
    }
}

function avantagesNiveau(array $niveau): array
{
    $avantages = [];
    if ($niveau['bonus'] > 0) {
        $avantages[] = '+' . $niveau['bonus'] . ' % de points sur chaque achat';
    }
    if ($niveau['livraison_des'] === 0) {
        $avantages[] = 'Livraison toujours offerte';
    } elseif ($niveau['livraison_des'] < 20) {
        $avantages[] = 'Livraison offerte dès ' . $niveau['livraison_des'] . ' € de livres';
    }
    if ($niveau['prioritaire']) {
        $avantages[] = 'Passage en tête des listes d\'attente des événements';
    }
    if ($niveau['bon_mensuel']) {
        $avantages[] = 'Bon de ' . formaterEuros(BON_ANNIVERSAIRE_MONTANT) . ' offert chaque mois, à la date anniversaire de votre inscription';
    }
    return $avantages;
}

function formaterEuros(float $montant): string
{
    return number_format($montant, 2, ',', ' ') . ' €';
}

function bonusNiveau(int $pointsAchat, array $niveau): int
{
    return (int) floor($pointsAchat * $niveau['bonus'] / 100);
}

function mesuresBadges(PDO $pdo, int $idUser): array
{
    $requetes = [
        'lecteur_fidele' => "SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande
                             WHERE c.id_user = ? AND c.statut <> 'annulee'",
        'plume_critique' => "SELECT COUNT(*) FROM avis WHERE id_user = ? AND statut = 'visible' AND date_validation IS NOT NULL",
        'coeur_genereux' => "SELECT COUNT(*) FROM don WHERE id_donneur = ? AND statut = 'recupere'",
        'habitue_rencontres' => "SELECT COUNT(*) FROM evenement_inscription WHERE id_user = ? AND present = 1",
        'commandes' => "SELECT COUNT(*) FROM commande WHERE id_user = ? AND statut <> 'annulee'",
        'anciennete' => "SELECT DATEDIFF(NOW(), date_inscription) FROM user WHERE id = ?",
    ];
    $valeurs = [];
    foreach ($requetes as $cle => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idUser]);
        $valeurs[$cle] = (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(cat.id_parent, cat.id_categorie) AS univers, SUM(cl.quantite) AS nombre
        FROM commande_ligne cl
        INNER JOIN commande c ON c.id_commande = cl.id_commande
        INNER JOIN livre l ON l.id_livre = cl.id_livre
        INNER JOIN categorie cat ON cat.id_categorie = l.id_categorie
        WHERE c.id_user = ? AND c.statut <> 'annulee'
        GROUP BY univers");
    $stmt->execute([$idUser]);
    $parUnivers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $valeurs['un_an'] = $valeurs['anciennete'] >= 365 ? $valeurs['commandes'] : min($valeurs['commandes'], 4);
    $valeurs['explorateur'] = count($parUnivers);
    $valeurs['collectionneur'] = $parUnivers ? (int) max($parUnivers) : 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM badge_obtenu WHERE id_user = ? AND palier = 3");
    $stmt->execute([$idUser]);
    $valeurs['pilier'] = (int) $stmt->fetchColumn();
    return $valeurs;
}

function verifierBadges(PDO $pdo, int $idUser): array
{
    try {
        $mesures = mesuresBadges($pdo, $idUser);
    } catch (PDOException $e) {
        return [];
    }
    $nouveaux = [];
    $insertion = $pdo->prepare("INSERT IGNORE INTO badge_obtenu (id_user, code, palier) VALUES (?, ?, ?)");
    foreach (BADGES as $code => $badge) {
        if ($code === 'pilier') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM badge_obtenu WHERE id_user = ? AND palier = 3");
            $stmt->execute([$idUser]);
            $mesures['pilier'] = (int) $stmt->fetchColumn();
        }
        foreach ($badge['paliers'] as $position => $seuil) {
            if ($mesures[$code] < $seuil) {
                break;
            }
            $palier = $position + 1;
            $insertion->execute([$idUser, $code, $palier]);
            if ($insertion->rowCount() === 0) {
                continue;
            }
            $estRare = isset($badge['rare']);
            $bonus = $estRare ? BONUS_BADGE_RARE : BONUS_BADGES[$palier];
            $libelle = 'Badge « ' . $badge['nom'] . ' »' . ($estRare ? '' : ' ' . mb_strtolower(PALIERS_BADGES[$palier]));
            crediterPoints($pdo, $idUser, $bonus, 'badge', $libelle, $code . ':' . $palier);
            $nouveaux[] = ['code' => $code, 'palier' => $palier];
        }
    }
    return $nouveaux;
}

function badgesMembre(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare("SELECT code, palier, date_obtention, vu FROM badge_obtenu WHERE id_user = ? ORDER BY date_obtention");
    $stmt->execute([$idUser]);
    $obtenus = [];
    foreach ($stmt->fetchAll() as $ligne) {
        $obtenus[$ligne['code']][(int) $ligne['palier']] = $ligne;
    }
    $mesures = mesuresBadges($pdo, $idUser);

    $badges = [];
    foreach (BADGES as $code => $badge) {
        $palierAtteint = empty($obtenus[$code]) ? 0 : max(array_keys($obtenus[$code]));
        $prochainSeuil = $badge['paliers'][$palierAtteint] ?? null;
        $seuilPrecedent = $palierAtteint > 0 ? $badge['paliers'][$palierAtteint - 1] : 0;
        $nouveau = false;
        foreach ($obtenus[$code] ?? [] as $ligne) {
            $nouveau = $nouveau || !$ligne['vu'];
        }
        $badges[$code] = $badge + [
            'code' => $code,
            'palier' => $palierAtteint,
            'mesure' => $mesures[$code],
            'prochain_seuil' => $prochainSeuil,
            'progression' => $prochainSeuil === null ? 1.0 : max(0, min(1, ($mesures[$code] - $seuilPrecedent) / max(1, $prochainSeuil - $seuilPrecedent))),
            'nouveau' => $nouveau,
            'date' => $palierAtteint > 0 ? $obtenus[$code][$palierAtteint]['date_obtention'] : null,
        ];
    }
    return $badges;
}

function proposerPointsAvis(PDO $pdo, int $idUser, int $idLivre, string $titreLivre): string
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande
                           WHERE c.id_user = ? AND cl.id_livre = ? AND c.statut <> 'annulee'");
    $stmt->execute([$idUser, $idLivre]);
    if ((int) $stmt->fetchColumn() === 0) {
        return 'non_achete';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_mouvement WHERE id_user = ? AND type = 'avis' AND reference = ?");
    $stmt->execute([$idUser, 'livre:' . $idLivre]);
    if ((int) $stmt->fetchColumn() > 0) {
        return 'deja_paye';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_mouvement WHERE id_user = ? AND type = 'avis' AND statut IN ('valide', 'en_attente')
                           AND date_mouvement >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute([$idUser]);
    if ((int) $stmt->fetchColumn() >= AVIS_PAYES_PAR_MOIS) {
        return 'quota';
    }
    crediterPoints($pdo, $idUser, AVIS_POINTS, 'avis', 'Avis sur « ' . $titreLivre . ' »', 'livre:' . $idLivre, 'en_attente');
    return 'en_attente';
}

function validerPointsAvis(PDO $pdo, int $idUser, int $idLivre): bool
{
    if (!changerStatutMouvement($pdo, $idUser, 'avis', 'livre:' . $idLivre, ['en_attente'], 'valide')) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM avis WHERE id_user = ? AND statut = 'visible' AND date_validation IS NOT NULL");
    $stmt->execute([$idUser]);
    if ((int) $stmt->fetchColumn() === 1) {
        crediterPoints($pdo, $idUser, POINTS_PREMIER_AVIS, 'premier_avis', 'Bonus premier avis validé', 'premier');
    }
    return true;
}

function optionsReduction(PDO $pdo, int $idUser, array $totaux, int $solde): array
{
    $baseLivres = $totaux['sous_total'] - $totaux['remise'];
    $plafond = round($baseLivres * BON_PLAFOND, 2);
    $options = [];
    foreach (BONS_POINTS as $points => $montant) {
        $raison = '';
        if ($solde < $points) {
            $raison = 'Encore ' . ($points - $solde) . ' points';
        } elseif ($baseLivres < BON_MINIMUM_LIVRES) {
            $raison = 'Dès ' . BON_MINIMUM_LIVRES . ' € de livres';
        } elseif ($montant > $plafond) {
            $raison = 'Limité à 50 % des livres';
        }
        $options['points:' . $points] = [
            'type' => 'points', 'points' => $points, 'montant' => $montant, 'id_bon' => null,
            'libelle' => $points . ' points', 'possible' => $raison === '', 'raison' => $raison,
        ];
    }
    try {
        $stmt = $pdo->prepare("SELECT id_bon, montant, date_expiration FROM bon_cadeau
                               WHERE id_user = ? AND date_utilisation IS NULL AND date_expiration >= CURDATE() ORDER BY date_expiration");
        $stmt->execute([$idUser]);
        foreach ($stmt->fetchAll() as $bon) {
            $montant = (float) $bon['montant'];
            $raison = $baseLivres < BON_MINIMUM_LIVRES ? 'Dès ' . BON_MINIMUM_LIVRES . ' € de livres' : ($montant > $plafond ? 'Limité à 50 % des livres' : '');
            $options['cadeau:' . $bon['id_bon']] = [
                'type' => 'cadeau', 'points' => 0, 'montant' => $montant, 'id_bon' => (int) $bon['id_bon'],
                'libelle' => 'Bon cadeau Gardien (jusqu\'au ' . date('d/m', strtotime($bon['date_expiration'])) . ')', 'possible' => $raison === '', 'raison' => $raison,
            ];
        }
    } catch (PDOException $e) {
    }
    return $options;
}

function attribuerBonAnniversaire(PDO $pdo, int $idUser, array $niveau): void
{
    if (!$niveau['bon_mensuel']) {
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT date_inscription FROM user WHERE id = ?");
        $stmt->execute([$idUser]);
        $inscription = new DateTime((string) $stmt->fetchColumn());
        $aujourdhui = new DateTime('today');
        $jourAnniversaire = min((int) $inscription->format('j'), (int) $aujourdhui->format('t'));
        if ((int) $aujourdhui->format('j') < $jourAnniversaire) {
            return;
        }
        $expiration = (clone $aujourdhui)->modify('+1 month')->format('Y-m-d');
        $pdo->prepare("INSERT IGNORE INTO bon_cadeau (id_user, montant, motif, annee, date_expiration) VALUES (?, ?, 'anniversaire', ?, ?)")
            ->execute([$idUser, BON_ANNIVERSAIRE_MONTANT, (int) $aujourdhui->format('Ym'), $expiration]);
    } catch (Exception $e) {
    }
}

function annulerPointsCommande(PDO $pdo, array $commande): void
{
    $idUser = (int) $commande['id_user'];
    $idCommande = (int) $commande['id_commande'];
    $reference = 'commande:' . $idCommande;
    if ((int) $commande['points_utilises'] > 0) {
        crediterPoints($pdo, $idUser, (int) $commande['points_utilises'], 'remboursement', "Points rendus : commande n° $idCommande annulée", $reference);
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM point_mouvement
                           WHERE id_user = ? AND statut = 'valide' AND type IN ('achat', 'bonus_niveau') AND reference = ?");
    $stmt->execute([$idUser, $reference]);
    $gagnes = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_mouvement WHERE id_user = ? AND type = 'premier_achat' AND reference = ? AND statut = 'valide'");
    $stmt->execute([$idUser, $reference]);
    if ((int) $stmt->fetchColumn() > 0) {
        $gagnes += POINTS_PREMIER_ACHAT;
    }
    if ($gagnes > 0) {
        crediterPoints($pdo, $idUser, -$gagnes, 'annulation', "Points retirés : commande n° $idCommande annulée", $reference);
    }
    if (!empty($commande['id_bon_cadeau'])) {
        $pdo->prepare("UPDATE bon_cadeau SET date_utilisation = NULL, id_commande = NULL WHERE id_bon = ?")->execute([$commande['id_bon_cadeau']]);
    }
}

function pointsPresence(PDO $pdo, array $inscription, string $titreEvenement, bool $present): void
{
    $idUser = (int) $inscription['id_user'];
    $reference = 'inscription:' . (int) $inscription['id_inscription'];
    if (!$present) {
        changerStatutMouvement($pdo, $idUser, 'presence', $reference, ['valide'], 'annule');
        return;
    }
    if (!crediterPoints($pdo, $idUser, POINTS_PRESENCE, 'presence', 'Présence à « ' . $titreEvenement . ' »', $reference)) {
        changerStatutMouvement($pdo, $idUser, 'presence', $reference, ['annule'], 'valide');
    }
    verifierBadges($pdo, $idUser);
}
