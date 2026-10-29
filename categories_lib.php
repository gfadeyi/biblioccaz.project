<?php

function listerCategories(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM categorie ORDER BY ordre, nom")->fetchAll();
}

const PAGES_CATEGORIES = ['roman' => 'roman.php', 'jeunesse' => 'jeunesse.php', 'bd-manga-jeunesse' => 'bd_mangas.php'];

function arbreCategories(PDO $pdo): array
{
    try {
        $lignes = $pdo->query("SELECT * FROM categorie ORDER BY ordre, nom")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    $univers = [];
    foreach ($lignes as $categorie) {
        if (empty($categorie['id_parent'])) {
            $univers[$categorie['id_categorie']] = $categorie + ['categories' => []];
        }
    }
    foreach ($lignes as $categorie) {
        if (!empty($categorie['id_parent']) && isset($univers[$categorie['id_parent']])) {
            $univers[$categorie['id_parent']]['categories'][] = $categorie;
        }
    }
    return array_values($univers);
}

function lienCategorie(string $slug): string
{
    return PAGES_CATEGORIES[$slug] ?? 'catalogue.php?categorie=' . urlencode($slug);
}

function categorieParSlug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM categorie WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function idsCategorieEtEnfants(PDO $pdo, string $slug): array
{
    $categorie = categorieParSlug($pdo, $slug);
    if (!$categorie) {
        return [0];
    }
    $ids = [(int) $categorie['id_categorie']];
    try {
        $stmt = $pdo->prepare("SELECT id_categorie FROM categorie WHERE id_parent = ?");
        $stmt->execute([$categorie['id_categorie']]);
        $ids = array_merge($ids, array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    } catch (PDOException $e) {
    }
    return $ids;
}

function optionsCategories(array $arbre, $selection, string $champ = 'id_categorie', bool $universChoisissable = false): string
{
    $option = fn(array $c, string $libelle) => '<option value="' . htmlspecialchars((string) $c[$champ]) . '"'
        . ((string) $c[$champ] === (string) $selection ? ' selected' : '') . '>' . htmlspecialchars($libelle) . '</option>';
    $html = '';
    foreach ($arbre as $univers) {
        if (!$univers['categories']) {
            $html .= $option($univers, $univers['nom']);
            continue;
        }
        $html .= '<optgroup label="' . htmlspecialchars($univers['nom']) . '">';
        if ($universChoisissable) {
            $html .= $option($univers, 'Tout l\'univers ' . $univers['nom']);
        }
        foreach ($univers['categories'] as $categorie) {
            $html .= $option($categorie, $categorie['nom']);
        }
        $html .= '</optgroup>';
    }
    return $html;
}

const FAMILLES_TAGS = ['theme' => 'Thèmes & ambiances', 'public' => 'Public', 'particularite' => 'Particularités'];

const ANCIENNES_ADRESSES_CATEGORIES = [
    'bd-mangas' => 'bandes-dessinees',
    'manuel-scolaire' => 'lycee',
    'young-adult' => 'ados',
    'cinema' => 'musique',
];

const ANCIENS_TAGS = [
    'fantastique' => ['tag', 'magie-surnaturel'],
    'action' => ['tag', 'action-combats'],
    'societe' => ['tag', 'engagement'],
    'classique' => ['categorie', 'classiques'],
    'romance' => ['categorie', 'romance'],
    'histoire' => ['categorie', 'histoire'],
];

function redirigerAnciennesAdresses(): void
{
    $parametres = $_GET;
    $aChange = false;
    $ancienneCategorie = is_string($parametres['categorie'] ?? null) ? $parametres['categorie'] : '';
    if (isset(ANCIENNES_ADRESSES_CATEGORIES[$ancienneCategorie])) {
        $parametres['categorie'] = ANCIENNES_ADRESSES_CATEGORIES[$ancienneCategorie];
        $aChange = true;
    }
    $ancienTag = is_string($parametres['tag'] ?? null) ? $parametres['tag'] : '';
    if (isset(ANCIENS_TAGS[$ancienTag])) {
        [$cible, $nouveauSlug] = ANCIENS_TAGS[$ancienTag];
        unset($parametres['tag']);
        $parametres[$cible] = $nouveauSlug;
        $aChange = true;
    }
    if ($aChange) {
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($parametres), true, 301);
        exit;
    }
}

function listerTags(PDO $pdo): array
{
    try {
        return $pdo->query("SELECT * FROM tag ORDER BY FIELD(famille, 'theme', 'public', 'particularite'), ordre, nom")->fetchAll();
    } catch (PDOException $e) {
        return $pdo->query("SELECT *, 'theme' AS famille FROM tag ORDER BY nom")->fetchAll();
    }
}

function tagsParFamille(array $tags): array
{
    $familles = array_fill_keys(array_keys(FAMILLES_TAGS), []);
    foreach ($tags as $tag) {
        $familles[isset(FAMILLES_TAGS[$tag['famille'] ?? '']) ? $tag['famille'] : 'theme'][] = $tag;
    }
    return array_filter($familles);
}

function tagsDuLivre(PDO $pdo, int $idLivre): array
{
    $stmt = $pdo->prepare("
        SELECT t.* FROM tag t
        INNER JOIN livre_tag lt ON lt.id_tag = t.id_tag
        WHERE lt.id_livre = ? ORDER BY t.ordre, t.nom");
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

const LETTRES_SANS_ACCENT = [
    'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
    'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o', 'œ' => 'oe',
    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
];

function creerSlug(string $texte, string $parDefaut = 'sans-nom'): string
{
    $texte = strtr(mb_strtolower($texte, 'UTF-8'), LETTRES_SANS_ACCENT);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texte), '-') ?: $parDefaut;
}
