<?php
require_once __DIR__ . '/notifications_lib.php';

const ADRESSES_MAX = 10;

function stockDuLivre(PDO $pdo, int $idLivre): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) FROM exemplaire WHERE id_livre = ? AND is_disponible = 1 AND quantite > 0");
    $stmt->execute([$idLivre]);
    return (int) $stmt->fetchColumn();
}

function estFavori(PDO $pdo, int $idUser, int $idLivre): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM favori WHERE id_user = ? AND id_livre = ?");
    $stmt->execute([$idUser, $idLivre]);
    return (bool) $stmt->fetchColumn();
}

function idsFavoris(PDO $pdo, int $idUser): array
{
    try {
        $stmt = $pdo->prepare("SELECT id_livre FROM favori WHERE id_user = ?");
        $stmt->execute([$idUser]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        return [];
    }
}

function nbFavoris(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favori WHERE id_user = ?");
        $stmt->execute([$idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function alerteEnAttente(PDO $pdo, int $idUser, int $idLivre): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM alerte_stock WHERE id_user = ? AND id_livre = ? AND date_envoi IS NULL");
    $stmt->execute([$idUser, $idLivre]);
    return (bool) $stmt->fetchColumn();
}

function creerAlerte(PDO $pdo, int $idUser, int $idLivre): void
{
    $stmt = $pdo->prepare("SELECT id_alerte FROM alerte_stock WHERE id_user = ? AND id_livre = ?");
    $stmt->execute([$idUser, $idLivre]);
    if ($id = $stmt->fetchColumn()) {
        $pdo->prepare("UPDATE alerte_stock SET date_creation = NOW(), date_envoi = NULL WHERE id_alerte = ?")->execute([$id]);
    } else {
        $pdo->prepare("INSERT INTO alerte_stock (id_user, id_livre) VALUES (?, ?)")->execute([$idUser, $idLivre]);
    }
}

function notifierRetourEnStock(PDO $pdo, int $idLivre): int
{
    try {
        $stmt = $pdo->prepare("
            SELECT a.id_alerte, u.email, l.titre
            FROM alerte_stock a
            INNER JOIN user u ON u.id = a.id_user
            INNER JOIN livre l ON l.id_livre = a.id_livre
            WHERE a.id_livre = ? AND a.date_envoi IS NULL");
        $stmt->execute([$idLivre]);
        $alertes = $stmt->fetchAll();
    } catch (PDOException $e) {
        return 0;
    }
    $maj = $pdo->prepare("UPDATE alerte_stock SET date_envoi = NOW() WHERE id_alerte = ?");
    foreach ($alertes as $a) {
        notifierParMail($a['email'], "« {$a['titre']} » est de nouveau disponible",
            "Bonne nouvelle : le livre « {$a['titre']} » que vous attendiez est de nouveau en stock.\n\n"
            . "Les exemplaires d'occasion partent vite, ne tardez pas : " . SITE_URL . "/detail_livre.php?id=$idLivre");
        $maj->execute([$a['id_alerte']]);
    }
    if ($alertes) {
        insertLog('STOCK', "Retour en stock du livre #$idLivre : " . count($alertes) . " alerte(s) envoyée(s)");
    }
    return count($alertes);
}

function adressesDe(PDO $pdo, int $idUser): array
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM adresse_livraison WHERE id_user = ? ORDER BY par_defaut DESC, libelle");
        $stmt->execute([$idUser]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function adresseEnLigne(array $a): string
{
    return trim($a['adresse'] . ($a['complement'] ? ', ' . $a['complement'] : '') . ', ' . $a['code_postal'] . ' ' . $a['ville']);
}
