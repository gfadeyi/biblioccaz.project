<?php

const PANIER_MINUTE_SECONDES = 900;
const PANIER_QUANTITE_MAX = 99;

function enregistrerCookiePanierMinute(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) { return; }
    $expiration = (int) ($_SESSION['panier_expiration'] ?? 0);
    setcookie('BIBLIOCCAZ_PANIER', $expiration ? identifiantPanierMinute() : '', [
        'expires' => $expiration ?: time() - 3600,
        'path' => rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/') . '/',
        'secure' => function_exists('connexionHttps') && connexionHttps(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function identifiantPanierMinute(): string
{
    if (!is_string($_SESSION['panier_identifiant'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $_SESSION['panier_identifiant'])) {
        $_SESSION['panier_identifiant'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['panier_identifiant'];
}

function stockLibrePanier(PDO $pdo, int $idExemplaire, string $idPanier = ''): int
{
    $verrou = $pdo->inTransaction() ? ' FOR UPDATE' : '';
    $requete = $pdo->prepare('SELECT quantite FROM exemplaire WHERE id_exemplaire = ?' . $verrou);
    $requete->execute([$idExemplaire]);
    $quantite = (int) $requete->fetchColumn();
    $requete = $pdo->prepare('SELECT quantite FROM panier_reservation WHERE id_exemplaire = ? AND date_expiration > NOW() AND id_panier <> ?' . $verrou);
    $requete->execute([$idExemplaire, $idPanier]);
    return max(0, $quantite - array_sum(array_map('intval', $requete->fetchAll(PDO::FETCH_COLUMN))));
}

function sqlStockLibrePanier(string $alias = 'e'): string
{
    if (!preg_match('/\A[a-z_]+\z/', $alias)) { throw new InvalidArgumentException('Alias invalide'); }
    return "GREATEST(0, $alias.quantite - COALESCE((SELECT SUM(pr.quantite) FROM panier_reservation pr WHERE pr.id_exemplaire = $alias.id_exemplaire AND pr.date_expiration > NOW()), 0))";
}

function chargerPanierMinute(PDO $pdo): void
{
    $ancienPanier = !empty($_SESSION['panier']);
    if (empty($_SESSION['panier_identifiant'])) { return; }
    $requete = $pdo->prepare("SELECT r.id_exemplaire, r.quantite, UNIX_TIMESTAMP(r.date_expiration) AS expiration, e.id_livre, e.etat, e.prix, l.titre, l.couverture FROM panier_reservation r INNER JOIN exemplaire e ON e.id_exemplaire = r.id_exemplaire INNER JOIN livre l ON l.id_livre = e.id_livre WHERE r.id_panier = ? AND r.date_expiration > NOW() AND l.is_valide = 1 AND e.is_disponible = 1 AND e.id_proprietaire IS NULL AND e.type_offre = 'vente' ORDER BY e.id_exemplaire");
    $requete->execute([identifiantPanierMinute()]);
    $panier = [];
    $expiration = null;
    foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $panier['ex_' . $ligne['id_exemplaire']] = [
            'id_livre' => (int) $ligne['id_livre'], 'id_exemplaire' => (int) $ligne['id_exemplaire'],
            'titre' => $ligne['titre'], 'etat' => $ligne['etat'], 'prix' => (float) $ligne['prix'],
            'quantite' => (int) $ligne['quantite'], 'image' => $ligne['couverture'] ?: 'default.png',
        ];
        $expiration = (int) $ligne['expiration'];
    }
    $_SESSION['panier'] = $panier;
    $_SESSION['panier_expiration'] = $expiration;
    if ($ancienPanier && !$panier) { $_SESSION['panier_message'] = 'Votre réservation de 15 minutes a expiré ou une offre a été retirée. Les articles ont été libérés. Vous pouvez les ajouter à nouveau selon leur disponibilité.'; }
}

function modifierPanierMinute(PDO $pdo, string $action, int $idExemplaire, int $cible = 0, array $liste = []): array
{
    if (!in_array($action, ['ajouter', 'plus', 'moins', 'supprimer', 'changer', 'serie'], true)) { throw new DomainException('Action inconnue.'); }
    $idPanier = identifiantPanierMinute();
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare('SELECT id_exemplaire FROM panier_reservation WHERE id_panier = ?');
        $requete->execute([$idPanier]);
        $identifiants = array_map('intval', [...$requete->fetchAll(PDO::FETCH_COLUMN), $idExemplaire, $cible, ...$liste]);
        $identifiants = array_values(array_unique(array_filter($identifiants, fn($id) => $id > 0)));
        sort($identifiants, SORT_NUMERIC);
        if (!$identifiants || count($identifiants) > 300) { throw new DomainException('Sélection invalide ou panier trop volumineux.'); }
        $marqueurs = implode(',', array_fill(0, count($identifiants), '?'));
        $requete = $pdo->prepare("SELECT e.*, l.titre, l.is_valide FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre WHERE e.id_exemplaire IN ($marqueurs) ORDER BY e.id_exemplaire FOR UPDATE");
        $requete->execute($identifiants);
        $offres = [];
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $offre) { $offres[(int) $offre['id_exemplaire']] = $offre; }
        $requete = $pdo->prepare('SELECT *, date_expiration > NOW() AS active FROM panier_reservation WHERE id_panier = ? ORDER BY id_exemplaire FOR UPDATE');
        $requete->execute([$idPanier]);
        $courant = [];
        $dateExpiration = null;
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            if ((int) $ligne['active']) { $courant[(int) $ligne['id_exemplaire']] = (int) $ligne['quantite']; $dateExpiration = $ligne['date_expiration']; }
        }
        $ajoutes = 0;
        $indisponibles = 0;
        $prolonger = in_array($action, ['ajouter', 'plus', 'changer', 'serie'], true);
        if (in_array($action, ['moins', 'supprimer', 'changer'], true) && !isset($courant[$idExemplaire])) { throw new DomainException('Cette ligne a expiré. Actualisez le panier.'); }
        if ($action === 'changer') {
            if ($idExemplaire === $cible) { $prolonger = false; }
            elseif (!isset($offres[$cible], $offres[$idExemplaire]) || (int) $offres[$cible]['id_livre'] !== (int) $offres[$idExemplaire]['id_livre']) { throw new DomainException('Choisissez un état de ce même livre.'); }
            else { $courant[$cible] = ($courant[$cible] ?? 0) + $courant[$idExemplaire]; unset($courant[$idExemplaire]); }
        } elseif ($action === 'supprimer') { unset($courant[$idExemplaire]); }
        elseif ($action === 'moins') {
            if (--$courant[$idExemplaire] <= 0) { unset($courant[$idExemplaire]); }
        } else {
            foreach ($action === 'serie' ? $liste : [$idExemplaire] as $id) {
                $id = (int) $id;
                $offre = $offres[$id] ?? null;
                $quantite = ($courant[$id] ?? 0) + 1;
                if (!$offre || !(int) $offre['is_valide'] || !(int) $offre['is_disponible'] || $offre['id_proprietaire'] !== null || $offre['type_offre'] !== 'vente' || $quantite > stockLibrePanier($pdo, $id, $idPanier)) {
                    if ($action !== 'serie') { throw new DomainException('Cet état n’est plus disponible dans la quantité demandée.'); }
                    $indisponibles++; continue;
                }
                $courant[$id] = $quantite;
                $ajoutes++;
            }
        }
        foreach ($courant as $id => $quantite) {
            $offre = $offres[$id] ?? null;
            if (!$offre || !(int) $offre['is_valide'] || !(int) $offre['is_disponible'] || $offre['id_proprietaire'] !== null || $offre['type_offre'] !== 'vente' || $quantite > PANIER_QUANTITE_MAX || $quantite > stockLibrePanier($pdo, $id, $idPanier)) { throw new DomainException('Une offre du panier a changé. Retirez-la ou réduisez sa quantité.'); }
        }
        if (($prolonger && ($action !== 'serie' || $ajoutes > 0)) || $dateExpiration === null) { $dateExpiration = $pdo->query('SELECT DATE_ADD(NOW(), INTERVAL 15 MINUTE)')->fetchColumn(); }
        $pdo->prepare('DELETE FROM panier_reservation WHERE id_panier = ?')->execute([$idPanier]);
        $inserer = $pdo->prepare('INSERT INTO panier_reservation (id_panier, id_exemplaire, quantite, date_expiration) VALUES (?, ?, ?, ?)');
        foreach ($courant as $id => $quantite) { $inserer->execute([$idPanier, $id, $quantite, $dateExpiration]); }
        $pdo->commit();
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
    chargerPanierMinute($pdo);
    enregistrerCookiePanierMinute();
    try { $pdo->prepare('DELETE FROM panier_reservation WHERE date_expiration <= NOW() LIMIT 200')->execute(); }
    catch (PDOException $erreur) { error_log('Nettoyage différé du panier : ' . $erreur->getMessage()); }
    return ['ajoutes' => $ajoutes, 'indisponibles' => $indisponibles, 'articles' => array_sum(array_column($_SESSION['panier'] ?? [], 'quantite')), 'expiration' => $_SESSION['panier_expiration'] ?? null, 'maintenant' => time()];
}

function synchroniserPanierMinute(PDO $pdo): void
{
    if (empty($_SESSION['panier_identifiant']) && is_string($_COOKIE['BIBLIOCCAZ_PANIER'] ?? null) && preg_match('/\A[a-f0-9]{64}\z/', $_COOKIE['BIBLIOCCAZ_PANIER'])) {
        $_SESSION['panier_identifiant'] = $_COOKIE['BIBLIOCCAZ_PANIER'];
    }
    if (!empty($_SESSION['panier']) && empty($_SESSION['panier_identifiant'])) {
        $ancien = $_SESSION['panier'];
        $_SESSION['panier'] = [];
        foreach ($ancien as $ligne) {
            for ($numero = 0; $numero < min(PANIER_QUANTITE_MAX, (int) ($ligne['quantite'] ?? 0)); $numero++) {
                try { modifierPanierMinute($pdo, 'ajouter', (int) ($ligne['id_exemplaire'] ?? 0)); }
                catch (DomainException $erreur) { $_SESSION['panier_message'] = 'Votre panier a été actualisé selon les exemplaires disponibles.'; break; }
            }
        }
    }
    chargerPanierMinute($pdo);
}

function viderPanierApresPaiement(): void
{
    $_SESSION['panier'] = [];
    $_SESSION['panier_expiration'] = null;
    unset($_SESSION['panier_message'], $_SESSION['panier_ajout']);
    enregistrerCookiePanierMinute();
}

function verrouillerPanierPourPaiement(PDO $pdo): void
{
    $identifiants = array_map('intval', array_column($_SESSION['panier'] ?? [], 'id_exemplaire'));
    sort($identifiants, SORT_NUMERIC);
    if (!$identifiants) { throw new DomainException('Votre panier est vide ou a expiré.'); }
    $marqueurs = implode(',', array_fill(0, count($identifiants), '?'));
    $requete = $pdo->prepare("SELECT id_exemplaire FROM exemplaire WHERE id_exemplaire IN ($marqueurs) ORDER BY id_exemplaire FOR UPDATE");
    $requete->execute($identifiants);
    $requete = $pdo->prepare('SELECT id_exemplaire, quantite, date_expiration > NOW() AS active FROM panier_reservation WHERE id_panier = ? ORDER BY id_exemplaire FOR UPDATE');
    $requete->execute([identifiantPanierMinute()]);
    $reservations = $requete->fetchAll(PDO::FETCH_ASSOC);
    if (count($reservations) !== count($identifiants)) { throw new DomainException('Votre réservation a expiré. Actualisez le panier.'); }
    foreach ($reservations as $reservation) {
        $id = (int) $reservation['id_exemplaire'];
        $ligne = $_SESSION['panier']['ex_' . $id] ?? null;
        if (!(int) $reservation['active'] || !$ligne || (int) $reservation['quantite'] !== (int) $ligne['quantite'] || stockLibrePanier($pdo, $id, identifiantPanierMinute()) < (int) $ligne['quantite']) { throw new DomainException('La réservation ou le stock a changé. Actualisez le panier.'); }
    }
}

function terminerReservationPaiement(PDO $pdo): void
{
    $requete = $pdo->prepare('SELECT COUNT(*) FROM panier_reservation WHERE id_panier = ? AND date_expiration <= NOW()');
    $requete->execute([identifiantPanierMinute()]);
    if ((int) $requete->fetchColumn() > 0) { throw new DomainException('Le délai du panier a expiré avant la validation.'); }
    $pdo->prepare('DELETE FROM panier_reservation WHERE id_panier = ?')->execute([identifiantPanierMinute()]);
}
