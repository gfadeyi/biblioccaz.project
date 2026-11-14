<?php
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/recompenses_regles_lib.php';

const DON_POINTS = 20;
const DON_MAX_RESERVATIONS = 3;
const DON_DUREE_MAX_JOURS = 60;
const DON_ETATS = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];
const DON_STATUTS = [
    'en_validation' => ['En attente de validation', 'warning'],
    'refuse' => ['À corriger', 'danger'],
    'disponible' => ['Disponible', 'success'],
    'reserve'    => ['Réservé', 'warning'],
    'recupere'   => ['Récupéré', 'secondary'],
    'annule'     => ['Annulé', 'danger'],
    'expire'     => ['Expiré', 'dark'],
];

function statutDon(array $don): string
{
    if ($don['statut'] === 'disponible' && $don['date_limite'] < date('Y-m-d')) {
        return 'expire';
    }
    return $don['statut'];
}

function badgeStatutDon(array $don): string
{
    [$libelle, $couleur] = DON_STATUTS[statutDon($don)] ?? ['?', 'secondary'];
    $texte = in_array($couleur, ['warning'], true) ? 'text-dark' : '';
    return "<span class=\"badge bg-$couleur $texte rounded-pill\">$libelle</span>";
}

function genererCodeCourt(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $stmt = $pdo->prepare("SELECT 1 FROM don WHERE code_court = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

function urlValidationDon(array $don): string
{
    return SITE_URL . '/don_valider.php?code=' . $don['code'];
}

function photoDon(?string $photo, ?string $couverture = null): string
{
    if ($photo && is_file(__DIR__ . '/img/dons/' . $photo)) {
        return 'img/dons/' . rawurlencode($photo);
    }
    if ($couverture && is_file(__DIR__ . '/img/' . $couverture)) {
        return 'img/' . implode('/', array_map('rawurlencode', explode('/', $couverture)));
    }
    return 'img/default.png';
}

function legendePhotoDon(?string $photo, ?string $couverture = null): string
{
    $image = photoDon($photo, $couverture);
    if (str_starts_with($image, 'img/dons/')) {
        return 'Photo du livre proposé';
    }
    return $image === 'img/default.png' ? 'Photo non fournie' : 'Couverture du catalogue';
}

function ligneCommandeADonner(PDO $pdo, int $idLigne, int $idUser, bool $verrouiller = false): ?array
{
    require_once __DIR__ . '/livraison_lib.php';
    try {
        $stmt = $pdo->prepare("
            SELECT cl.id_ligne, cl.id_livre, cl.etat, cl.quantite, c.id_commande, c.date_commande, c.statut,
                   l.titre, l.auteur, l.id_categorie,
                   (SELECT COUNT(*) FROM don d WHERE d.id_commande_ligne = cl.id_ligne AND d.statut <> 'annule') AS nb_dons
            FROM commande_ligne cl
            INNER JOIN commande c ON c.id_commande = cl.id_commande
            INNER JOIN livre l ON l.id_livre = cl.id_livre AND l.is_valide = 1
            WHERE cl.id_ligne = ? AND c.id_user = ?" . ($verrouiller ? ' FOR UPDATE' : ''));
        $stmt->execute([$idLigne, $idUser]);
        $ligne = $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
    if (!$ligne || !commandeLivree($ligne) || (int) $ligne['nb_dons'] >= (int) $ligne['quantite']) {
        return null;
    }
    return $ligne;
}

function donsParLigneCommande(PDO $pdo, array $idsLignes): array
{
    if (!$idsLignes) {
        return [];
    }
    try {
        $stmt = $pdo->prepare("SELECT id_commande_ligne, COUNT(*) FROM don WHERE statut <> 'annule' AND id_commande_ligne IN (" . implode(',', array_fill(0, count($idsLignes), '?')) . ") GROUP BY id_commande_ligne");
        $stmt->execute(array_values($idsLignes));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    } catch (PDOException $e) {
        return [];
    }
}

function etatDonDepuisCommande(string $etat): string
{
    $cle = strtr(mb_strtolower(trim($etat)), ['é' => 'e', 'è' => 'e']);
    return isset(DON_ETATS[$cle]) ? $cle : 'bon';
}

function motsRechercheDon(string $recherche): array
{
    $mots = preg_split('/\s+/u', mb_substr(trim($recherche), 0, 100), -1, PREG_SPLIT_NO_EMPTY);
    return array_slice(array_values(array_filter($mots, fn($mot) => mb_strlen($mot) >= 2 || ctype_digit($mot))), 0, 6);
}

function conditionRechercheDon(array $mots, array $colonnes, string $colonneIsbn, array &$parametres): string
{
    $conditions = [];
    foreach ($mots as $mot) {
        $motif = '%' . addcslashes($mot, '%_\\') . '%';
        $parties = [];
        foreach ($colonnes as $colonne) {
            $parties[] = "$colonne LIKE ?";
            $parametres[] = $motif;
        }
        $chiffres = preg_replace('/[^0-9Xx]/', '', $mot);
        if (strlen($chiffres) >= 4 && strlen($chiffres) === strlen(str_replace('-', '', $mot))) {
            $parties[] = "REPLACE($colonneIsbn, '-', '') LIKE ?";
            $parametres[] = '%' . $chiffres . '%';
        }
        $conditions[] = '(' . implode(' OR ', $parties) . ')';
    }
    return $conditions ? implode(' AND ', $conditions) : '1 = 1';
}

function rechercherLivresPourDon(PDO $pdo, string $recherche, int $limite = 8): array
{
    $mots = motsRechercheDon($recherche);
    if (!$mots) {
        return [];
    }
    $parametres = [];
    $condition = conditionRechercheDon($mots, ['l.titre', 'l.auteur'], 'l.isbn', $parametres);
    $parametres[] = addcslashes(mb_substr(trim($recherche), 0, 100), '%_\\') . '%';
    $stmt = $pdo->prepare("SELECT l.id_livre, l.titre, l.auteur, l.id_categorie, l.couverture, l.isbn
        FROM livre l WHERE l.is_valide = 1 AND $condition
        ORDER BY (l.titre LIKE ?) DESC, l.titre LIMIT " . max(1, min(20, $limite)));
    $stmt->execute($parametres);
    return array_map(fn($livre) => [
        'id' => (int) $livre['id_livre'],
        'titre' => $livre['titre'],
        'auteur' => (string) ($livre['auteur'] ?? ''),
        'categorie' => $livre['id_categorie'] !== null ? (int) $livre['id_categorie'] : null,
        'isbn' => (string) ($livre['isbn'] ?? ''),
        'couverture' => urlImage($livre['couverture']),
    ], $stmt->fetchAll());
}

function emailMembreDon(PDO $pdo, int $idUser): string
{
    $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    return (string) $stmt->fetchColumn();
}

const DON_PHOTOS_MAX = 3;

function photosDonDisponibles(PDO $pdo): bool
{
    static $disponible = null;
    if ($disponible === null) {
        try {
            $pdo->query('SELECT id_photo FROM don_photo LIMIT 0');
            $disponible = true;
        } catch (PDOException $e) {
            $disponible = false;
        }
    }
    return $disponible;
}

function photosSupplementairesDon(PDO $pdo, int $idDon): array
{
    if (!photosDonDisponibles($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT id_photo, fichier FROM don_photo WHERE id_don = ? ORDER BY ordre, id_photo');
    $stmt->execute([$idDon]);
    return array_values(array_filter($stmt->fetchAll(), fn($photo) => is_file(__DIR__ . '/img/dons/' . basename($photo['fichier']))));
}

function cheminPhotoDon(string $fichier): string
{
    return 'img/dons/' . rawurlencode(basename($fichier));
}
