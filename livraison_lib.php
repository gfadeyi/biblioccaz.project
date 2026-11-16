<?php

const COMMANDE_LIVRAISON_JOURS = 3;
const COMMANDE_ANNULATION_CLIENT_HEURES = 4;
const COMMANDE_ETAPES = [
    0 => ['confirmee', 'Confirmée', 0],
    1 => ['preparation', 'En préparation', 4 * 3600],
    2 => ['expediee', 'Expédiée', 86400],
    3 => ['livree', 'Livrée', COMMANDE_LIVRAISON_JOURS * 86400],
];
const COMMANDE_SUIVI_RATTRAPAGE_JOURS = 4;

function dateLivraisonCommande(array $commande): string
{
    return dateEtapeCommande($commande, 3);
}

function commandeLivree(array $commande): bool
{
    return ($commande['statut'] ?? 'validee') === 'validee' && dateLivraisonCommande($commande) <= date('Y-m-d H:i:s');
}

function dateEtapeCommande(array $commande, int $etape): string
{
    return date('Y-m-d H:i:s', strtotime($commande['date_commande']) + COMMANDE_ETAPES[$etape][2]);
}

function etapeCommande(array $commande): int
{
    $ecoule = time() - strtotime($commande['date_commande']);
    $etapeAtteinte = 0;
    foreach (COMMANDE_ETAPES as $numero => [, , $delai]) {
        if ($ecoule >= $delai) {
            $etapeAtteinte = $numero;
        }
    }
    return $etapeAtteinte;
}

function commandeAnnulableParClient(array $commande): bool
{
    return ($commande['statut'] ?? 'validee') === 'validee'
        && time() - strtotime($commande['date_commande']) < COMMANDE_ANNULATION_CLIENT_HEURES * 3600;
}

function dateLimiteAnnulationClient(array $commande): string
{
    return date('Y-m-d H:i:s', strtotime($commande['date_commande']) + COMMANDE_ANNULATION_CLIENT_HEURES * 3600);
}

function conditionEtapeAtteinteSql(): string
{
    return "CASE WHEN c.date_commande <= NOW() - INTERVAL " . COMMANDE_LIVRAISON_JOURS . " DAY THEN 3
                 WHEN c.date_commande <= NOW() - INTERVAL 1 DAY THEN 2
                 WHEN c.date_commande <= NOW() - INTERVAL 4 HOUR THEN 1 ELSE 0 END";
}

function texteMailEtapeCommande(array $commande, int $etape): array
{
    $numero = (int) $commande['id_commande'];
    $prenom = $commande['prenom_client'] ?: $commande['pseudo'];
    $livraison = date('d/m/Y', strtotime(dateLivraisonCommande($commande)));
    return match ($etape) {
        1 => ["Votre commande n° $numero est en préparation", "Bonjour $prenom,\n\nBonne nouvelle : nous préparons votre commande n° $numero. Les livres sont vérifiés et emballés avec soin.\n\nLivraison prévue le $livraison.", "Commande n° $numero en préparation."],
        2 => ["Votre commande n° $numero est expédiée", "Bonjour $prenom,\n\nVotre commande n° $numero vient d’être expédiée. Elle arrivera le $livraison.\n\n(Site de démonstration : aucun colis n’est réellement envoyé.)", "Commande n° $numero expédiée : livraison prévue le $livraison."],
        default => ["Votre commande n° $numero est livrée", "Bonjour $prenom,\n\nVotre commande n° $numero est livrée. Bonne lecture !\n\nUn livre ne vous convient pas ? Vous pouvez demander un retour pendant 14 jours depuis « Mes commandes ». Une fois lu, pensez aussi à le donner à un autre lecteur.", "Commande n° $numero livrée. Bonne lecture !"],
    };
}

function avancerSuiviCommandes(PDO $pdo, ?int $idUser = null): int
{
    require_once __DIR__ . '/notifications_lib.php';
    require_once __DIR__ . '/notifications_membre_lib.php';
    try {
        $stmt = $pdo->prepare("SELECT c.id_commande, c.id_user, c.date_commande, c.etape_suivi, u.email, u.prenom AS prenom_client, u.pseudo, " . conditionEtapeAtteinteSql() . " AS etape_atteinte
            FROM commande c INNER JOIN `user` u ON u.id = c.id_user
            WHERE c.statut = 'validee' AND c.etape_suivi < 3 AND c.etape_suivi < " . conditionEtapeAtteinteSql()
            . ($idUser !== null ? ' AND c.id_user = ?' : '') . " ORDER BY c.id_commande LIMIT 50");
        $stmt->execute($idUser !== null ? [$idUser] : []);
        $commandes = $stmt->fetchAll();
    } catch (PDOException $e) {
        return 0;
    }
    $envoyes = 0;
    $avancer = $pdo->prepare("UPDATE commande SET etape_suivi = ? WHERE id_commande = ? AND etape_suivi = ? AND statut = 'validee'");
    foreach ($commandes as $commande) {
        $etape = (int) $commande['etape_atteinte'];
        $avancer->execute([$etape, (int) $commande['id_commande'], (int) $commande['etape_suivi']]);
        if (!$avancer->rowCount() || strtotime($commande['date_commande']) < time() - COMMANDE_SUIVI_RATTRAPAGE_JOURS * 86400) {
            continue;
        }
        [$sujet, $texte, $notification] = texteMailEtapeCommande($commande, $etape);
        notifierMembre($pdo, (int) $commande['id_user'], 'commande', $notification, 'commandes#commande-' . (int) $commande['id_commande']);
        if (notifierParMail($commande['email'], $sujet, $texte, SITE_URL . '/commandes', 'Suivre ma commande')) {
            $envoyes++;
        }
    }
    return $envoyes;
}

function avancerSuiviCommandesSiBesoin(PDO $pdo): void
{
    if (($_SESSION['suivi_commandes_verifie'] ?? 0) > time() - 300) {
        return;
    }
    $_SESSION['suivi_commandes_verifie'] = time();
    avancerSuiviCommandes($pdo);
}
