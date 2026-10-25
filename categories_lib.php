<?php

function listerCategories(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM categorie ORDER BY ordre, nom")->fetchAll();
}

function listerTags(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM tag ORDER BY nom")->fetchAll();
}

function tagsDuLivre(PDO $pdo, int $idLivre): array
{
    $stmt = $pdo->prepare("
        SELECT t.* FROM tag t
        INNER JOIN livre_tag lt ON lt.id_tag = t.id_tag
        WHERE lt.id_livre = ? ORDER BY t.nom");
    $stmt->execute([$idLivre]);
    return $stmt->fetchAll();
}

function enregistrerCategorieEtTags(PDO $pdo, int $idLivre, array $post): void
{
    $idCategorie = !empty($post['id_categorie']) ? (int) $post['id_categorie'] : null;
    $pdo->prepare("UPDATE livre SET id_categorie = ? WHERE id_livre = ?")->execute([$idCategorie, $idLivre]);

    $pdo->prepare("DELETE FROM livre_tag WHERE id_livre = ?")->execute([$idLivre]);
    $stmt = $pdo->prepare("INSERT IGNORE INTO livre_tag (id_livre, id_tag) VALUES (?, ?)");
    foreach ((array) ($post['tags'] ?? []) as $idTag) {
        $stmt->execute([$idLivre, (int) $idTag]);
    }
}

function creerSlug(string $texte): string
{
    $texte = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte);
    $texte = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $texte));
    return trim($texte, '-') ?: 'sans-nom';
}
