<?php
require_once __DIR__ . '/livres_lib.php';

const POIDS_RECOMMANDATION = [
    'preference_categorie' => 5,
    'preference_tag' => 3,
    'achat' => 4,
    'emprunt' => 3,
    'note_haute' => 3,
    'note_basse' => -2,
    'favori' => 2,
    'consultation' => 1,
    'livre_courant' => 4,
    'panier' => 4,
    'part_univers' => 0.5,
    'part_tags' => 0.5,
    'part_auteur' => 2,
    'part_serie' => 2.5,
    'bonus_complement' => 8,
    'part_profil_fiche' => 0.5,
    'affinite' => 60,
    'popularite' => 25,
    'note' => 15,
    'repli_popularite' => 60,
    'repli_note' => 40,
    'note_reference' => 3.5,
    'avis_reference' => 3,
    'max_par_categorie' => 3,
];
const RAISONS_RECOMMANDATION = [
    'preference_categorie' => 'Parce que vous aimez « %s »',
    'preference_tag' => 'Parce que vous aimez le thème « %s »',
    'achat' => 'Proche de « %s », que vous avez acheté',
    'emprunt' => 'Proche de « %s », que vous avez emprunté',
    'note_haute' => 'Proche de « %s », que vous avez bien noté',
    'note_basse' => 'Éloigné de « %s », que vous avez peu apprécié',
    'favori' => 'Proche de « %s », dans vos favoris',
    'consultation' => 'Parce que vous avez consulté « %s »',
    'meme_categorie' => 'Dans la même catégorie « %s »',
    'meme_univers' => 'Du même univers « %s »',
    'meme_theme' => 'Même thème « %s »',
    'meme_tag' => 'En commun : « %s »',
    'meme_auteur' => 'Du même auteur : %s',
    'meme_serie' => 'Dans la même série « %s »',
    'panier' => 'Va bien avec « %s », dans votre panier',
];
const AUTEURS_SANS_AFFINITE = ['collectif', 'anonyme', 'divers', 'inconnu'];
const RECOMMANDATIONS_NOMBRE = 12;
const RECOMMANDATIONS_DUREE_CACHE = 600;
const HISTORIQUE_TAILLE_MAX = 20;
const HISTORIQUE_DUREE_JOURS = 90;
const PREFERENCES_MAX = 40;
const ROLES_SUGGESTIONS = ['client', 'auteur'];

function texteNormalise(string $texte): string
{
    return trim(preg_replace('/\s+/u', ' ', mb_strtolower(str_replace(['.', '’'], [' ', "'"], $texte))));
}

function auteursDuLivre(?string $auteur): array
{
    $auteurs = [];
    foreach (preg_split('/\s*(?:,|;|&|\bet\b)\s*/u', (string) $auteur) as $nom) {
        $nom = trim($nom);
        if ($nom !== '' && !in_array(texteNormalise($nom), AUTEURS_SANS_AFFINITE, true)) {
            $auteurs[texteNormalise($nom)] = $nom;
        }
    }
    return $auteurs;
}

function serieDuTitre(?string $titre): ?string
{
    if (preg_match('/^(.+?)\s*[-–—:,]?\s*\b(?:tomes?|volumes?|vol\s*\.?|livre|t\s*\.)\s*\d+/iu', (string) $titre, $trouve)) {
        $serie = trim($trouve[1], " \t-–—:,");
        return mb_strlen($serie) >= 3 ? $serie : null;
    }
    return null;
}

function clesLivre(?int $idCategorie, ?int $idUnivers, array $idsTags, float $partUnivers, float $partTags, ?string $auteur = null, ?string $titre = null, float $partAuteur = 0.0, float $partSerie = 0.0): array
{
    $cles = [];
    if ($partAuteur > 0) {
        foreach (array_keys(auteursDuLivre($auteur)) as $nom) {
            $cles['a:' . $nom] = $partAuteur;
        }
    }
    $serie = $partSerie > 0 ? serieDuTitre($titre) : null;
    if ($serie !== null) {
        $cles['s:' . texteNormalise($serie)] = $partSerie;
    }
    if ($idCategorie) {
        $cles['c' . $idCategorie] = 1.0;
    }
    if ($idUnivers && $idUnivers !== $idCategorie) {
        $cles['c' . $idUnivers] = $partUnivers;
    }
    foreach ($idsTags as $idTag) {
        $cles['t' . (int) $idTag] = $partTags;
    }
    return $cles;
}

function profilAffinites(array $signaux): array
{
    $profil = [];
    foreach ($signaux as $signal) {
        foreach ($signal['cles'] as $cle => $part) {
            $valeur = $signal['poids'] * $part;
            $profil[$cle]['total'] = ($profil[$cle]['total'] ?? 0) + $valeur;
            $profil[$cle]['raisons'][$signal['raison']] = ($profil[$cle]['raisons'][$signal['raison']] ?? 0) + $valeur;
        }
    }
    return $profil;
}

function fusionnerProfils(array $profil, array $ajout, float $facteur, array $raisonsIgnorees = []): array
{
    foreach ($ajout as $cle => $affinite) {
        $profil[$cle]['total'] = ($profil[$cle]['total'] ?? 0) + $affinite['total'] * $facteur;
        $profil[$cle]['raisons'] ??= [];
        foreach ($affinite['raisons'] as $raison => $valeur) {
            if (!isset($raisonsIgnorees[$raison])) {
                $profil[$cle]['raisons'][$raison] = ($profil[$cle]['raisons'][$raison] ?? 0) + $valeur * $facteur;
            }
        }
    }
    return $profil;
}

function raisonsDesLivres(array $titres): array
{
    $raisons = [];
    foreach ($titres as $titre) {
        foreach (['achat', 'emprunt', 'note_haute', 'note_basse', 'favori', 'consultation'] as $type) {
            $raisons[sprintf(RAISONS_RECOMMANDATION[$type], $titre)] = true;
        }
    }
    return $raisons;
}

function profilEstVide(array $profil): bool
{
    foreach ($profil as $affinite) {
        if ($affinite['total'] > 0) {
            return false;
        }
    }
    return true;
}

function noteAjustee(?float $moyenne, int $nbAvis, array $poids = POIDS_RECOMMANDATION): float
{
    $reference = (float) $poids['note_reference'];
    $nbReference = (int) $poids['avis_reference'];
    return ($nbReference * $reference + $nbAvis * ($moyenne ?? $reference)) / ($nbReference + $nbAvis);
}

function raisonRepli(array $candidat): string
{
    if ((int) $candidat['nb_avis'] > 0 && (float) $candidat['note_moyenne'] >= 4) {
        return 'Très bien noté par les lecteurs';
    }
    if ((int) $candidat['ventes'] > 0) {
        return 'Parmi les meilleures ventes';
    }
    if ((int) $candidat['nb_favoris'] > 0) {
        return 'Souvent ajouté aux favoris';
    }
    return 'À découvrir dans nos rayons';
}

function classerRecommandations(array $candidats, array $profil, int $nombre, array $poids = POIDS_RECOMMANDATION): array
{
    $candidats = array_values($candidats);
    $repli = profilEstVide($profil);
    $popularites = [];
    $bruts = [];
    $raisons = [];
    foreach ($candidats as $index => $candidat) {
        $popularites[$index] = log(1 + (int) $candidat['ventes'] + (int) $candidat['nb_favoris']);
        $brut = 0.0;
        $parRaison = [];
        foreach (clesLivre($candidat['id_categorie'], $candidat['id_univers'], $candidat['tags'], 1.0, 1.0, $candidat['auteur'] ?? null, $candidat['titre'] ?? null, 1.0, 1.0) as $cle => $part) {
            if (!isset($profil[$cle])) {
                continue;
            }
            $brut += $profil[$cle]['total'];
            foreach ($profil[$cle]['raisons'] as $raison => $valeur) {
                $parRaison[$raison] = ($parRaison[$raison] ?? 0) + $valeur;
            }
        }
        $bruts[$index] = max(0.0, $brut);
        arsort($parRaison);
        $raisons[$index] = $parRaison && reset($parRaison) > 0 ? (string) array_key_first($parRaison) : '';
    }
    $populariteMax = $popularites ? max($popularites) : 0;
    $brutMax = $bruts ? max($bruts) : 0;
    $classes = [];
    foreach ($candidats as $index => $candidat) {
        $popularite = $populariteMax > 0 ? $popularites[$index] / $populariteMax : 0;
        $note = (noteAjustee($candidat['note_moyenne'] !== null ? (float) $candidat['note_moyenne'] : null, (int) $candidat['nb_avis'], $poids) - 1) / 4;
        $affinite = $brutMax > 0 ? $bruts[$index] / $brutMax : 0;
        $score = $repli
            ? $poids['repli_popularite'] * $popularite + $poids['repli_note'] * $note
            : $poids['affinite'] * $affinite + $poids['popularite'] * $popularite + $poids['note'] * $note;
        $classes[] = $candidat + [
            'score' => round($score, 4),
            'raison' => $affinite > 0 && $raisons[$index] !== '' ? $raisons[$index] : raisonRepli($candidat),
        ];
    }
    usort($classes, fn($a, $b) => [$b['score'], $b['id_livre']] <=> [$a['score'], $a['id_livre']]);
    return diversifierRecommandations($classes, $nombre, (int) $poids['max_par_categorie']);
}

function diversifierRecommandations(array $classes, int $nombre, int $maxParCategorie): array
{
    $retenus = [];
    $ecartes = [];
    $parCategorie = [];
    foreach ($classes as $livre) {
        $groupe = (int) ($livre['id_categorie'] ?? 0);
        if (count($retenus) < $nombre && ($parCategorie[$groupe] ?? 0) < $maxParCategorie) {
            $retenus[] = $livre;
            $parCategorie[$groupe] = ($parCategorie[$groupe] ?? 0) + 1;
        } else {
            $ecartes[] = $livre;
        }
    }
    foreach ($ecartes as $livre) {
        if (count($retenus) >= $nombre) {
            break;
        }
        $retenus[] = $livre;
    }
    return $retenus;
}

function lignesRecommandation(PDO $pdo, string $sql, array $parametres = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametres);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function marqueursRequete(array $valeurs): string
{
    return implode(', ', array_fill(0, count($valeurs), '?'));
}

function categoriesRecommandation(PDO $pdo): array
{
    $categories = [];
    foreach (lignesRecommandation($pdo, "SELECT id_categorie, id_parent, nom FROM categorie") as $categorie) {
        $categories[(int) $categorie['id_categorie']] = ['id_parent' => $categorie['id_parent'] !== null ? (int) $categorie['id_parent'] : null, 'nom' => $categorie['nom']];
    }
    return $categories;
}

function historiqueActif(PDO $pdo, int $idUser): bool
{
    $lignes = lignesRecommandation($pdo, "SELECT historique_actif FROM user WHERE id = ?", [$idUser]);
    return $lignes && (int) $lignes[0]['historique_actif'] === 1;
}

function signauxLecteur(PDO $pdo, int $idUser, array $poids = POIDS_RECOMMANDATION): array
{
    $categories = categoriesRecommandation($pdo);
    $signaux = [];
    foreach (lignesRecommandation($pdo, "SELECT id_categorie FROM preference_categorie WHERE id_user = ?", [$idUser]) as $preference) {
        $idCategorie = (int) $preference['id_categorie'];
        if (isset($categories[$idCategorie])) {
            $signaux[] = ['poids' => $poids['preference_categorie'], 'cles' => ['c' . $idCategorie => 1.0],
                          'raison' => sprintf(RAISONS_RECOMMANDATION['preference_categorie'], $categories[$idCategorie]['nom'])];
        }
    }
    foreach (lignesRecommandation($pdo, "SELECT p.id_tag, t.nom FROM preference_tag p INNER JOIN tag t ON t.id_tag = p.id_tag WHERE p.id_user = ?", [$idUser]) as $preference) {
        $signaux[] = ['poids' => $poids['preference_tag'], 'cles' => ['t' . (int) $preference['id_tag'] => 1.0],
                      'raison' => sprintf(RAISONS_RECOMMANDATION['preference_tag'], $preference['nom'])];
    }

    $evenements = [];
    $sources = [
        'achat' => ["SELECT DISTINCT cl.id_livre FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande
                     WHERE c.id_user = ? AND c.statut <> 'annulee' AND cl.id_livre IS NOT NULL", [$idUser]],
        'achat_particulier' => ["SELECT DISTINCT e.id_livre FROM demande_achat d INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce
                                 WHERE d.id_acheteur = ? AND d.statut = 'validee'", [$idUser]],
        'emprunt' => ["SELECT DISTINCT e.id_livre FROM emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire
                       WHERE em.id_user = ? AND em.statut IN ('en_cours', 'rendu')", [$idUser]],
        'favori' => ["SELECT id_livre FROM favori WHERE id_user = ?", [$idUser]],
    ];
    foreach ($sources as $type => [$sql, $parametres]) {
        foreach (lignesRecommandation($pdo, $sql, $parametres) as $ligne) {
            $evenements[] = [$type === 'achat_particulier' ? 'achat' : $type, (int) $ligne['id_livre']];
        }
    }
    foreach (lignesRecommandation($pdo, "SELECT id_livre, note FROM avis WHERE id_user = ?", [$idUser]) as $avis) {
        if ((int) $avis['note'] >= 4) {
            $evenements[] = ['note_haute', (int) $avis['id_livre']];
        } elseif ((int) $avis['note'] <= 2) {
            $evenements[] = ['note_basse', (int) $avis['id_livre']];
        }
    }
    if (historiqueActif($pdo, $idUser)) {
        foreach (lignesRecommandation($pdo, "SELECT id_livre FROM consultation_livre
                                             WHERE id_user = ? AND date_consultation >= NOW() - INTERVAL " . HISTORIQUE_DUREE_JOURS . " DAY
                                             ORDER BY date_consultation DESC, id_livre DESC LIMIT " . HISTORIQUE_TAILLE_MAX, [$idUser]) as $consultation) {
            $evenements[] = ['consultation', (int) $consultation['id_livre']];
        }
    }
    if (!$evenements) {
        return $signaux;
    }

    $idsLivres = array_values(array_unique(array_column($evenements, 1)));
    $livres = [];
    foreach (lignesRecommandation($pdo, "
        SELECT l.id_livre, l.titre, l.auteur, l.id_categorie, GROUP_CONCAT(lt.id_tag) AS tags
        FROM livre l LEFT JOIN livre_tag lt ON lt.id_livre = l.id_livre
        WHERE l.id_livre IN (" . marqueursRequete($idsLivres) . ")
        GROUP BY l.id_livre, l.titre, l.auteur, l.id_categorie", $idsLivres) as $livre) {
        $livres[(int) $livre['id_livre']] = $livre;
    }
    foreach ($evenements as [$type, $idLivre]) {
        if (!isset($livres[$idLivre])) {
            continue;
        }
        $livre = $livres[$idLivre];
        $idCategorie = $livre['id_categorie'] !== null ? (int) $livre['id_categorie'] : null;
        $signaux[] = [
            'poids' => $poids[$type],
            'cles' => clesLivre($idCategorie, $idCategorie ? ($categories[$idCategorie]['id_parent'] ?? null) : null,
                                $livre['tags'] !== null ? array_map('intval', explode(',', $livre['tags'])) : [],
                                (float) $poids['part_univers'], (float) $poids['part_tags'],
                                $livre['auteur'], $livre['titre'], (float) $poids['part_auteur'], (float) $poids['part_serie']),
            'raison' => sprintf(RAISONS_RECOMMANDATION[$type], $livre['titre']),
        ];
    }
    return $signaux;
}

function livresExclus(PDO $pdo, int $idUser): array
{
    $requetes = [
        "SELECT cl.id_livre FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande
         WHERE c.id_user = ? AND c.statut <> 'annulee' AND cl.id_livre IS NOT NULL",
        "SELECT e.id_livre FROM demande_achat d INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce
         WHERE d.id_acheteur = ? AND d.statut IN ('acceptee', 'validee')",
        "SELECT e.id_livre FROM emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire
         WHERE em.id_user = ? AND em.statut IN ('reserve', 'en_cours', 'rendu')",
        "SELECT id_livre FROM livre WHERE id_user_auteur = ?",
        "SELECT id_livre FROM exemplaire WHERE id_proprietaire = ?",
        "SELECT id_livre FROM don WHERE id_donneur = ? AND id_livre IS NOT NULL",
        "SELECT id_livre FROM favori WHERE id_user = ?",
    ];
    $exclus = [];
    foreach ($requetes as $sql) {
        foreach (lignesRecommandation($pdo, $sql, [$idUser]) as $ligne) {
            $exclus[(int) $ligne['id_livre']] = true;
        }
    }
    return $exclus;
}

function candidatsRecommandation(PDO $pdo, string $condition = '', array $parametres = []): array
{
    $lignes = lignesRecommandation($pdo, "
        SELECT l.id_livre, l.titre, l.auteur, l.couverture, l.id_categorie, c.id_parent AS id_univers,
               COALESCE(MIN(CASE WHEN e.origine IN ('magasin', 'particulier') THEN e.prix END), 0) AS prix,
               SUBSTRING_INDEX(GROUP_CONCAT(e.etat ORDER BY e.origine IN ('don', 'emprunt'), e.prix ASC), ',', 1) AS etat,
               SUM(CASE WHEN e.origine = 'magasin' THEN e.quantite END) AS stock,
               GROUP_CONCAT(DISTINCT e.origine ORDER BY e.origine) AS origines,
               av.moyenne AS note_moyenne, COALESCE(av.nb, 0) AS nb_avis,
               COALESCE(fv.nb, 0) AS nb_favoris, COALESCE(vt.nb, 0) AS ventes, tg.tags
        FROM livre l
        INNER JOIN " . sqlOffres() . " e ON e.id_livre = l.id_livre
        LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
        LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre
        LEFT JOIN (SELECT id_livre, COUNT(*) AS nb FROM favori GROUP BY id_livre) fv ON fv.id_livre = l.id_livre
        LEFT JOIN (SELECT cl.id_livre, SUM(cl.quantite) AS nb FROM commande_ligne cl INNER JOIN commande co ON co.id_commande = cl.id_commande
                   WHERE co.statut <> 'annulee' GROUP BY cl.id_livre) vt ON vt.id_livre = l.id_livre
        LEFT JOIN (SELECT id_livre, GROUP_CONCAT(id_tag) AS tags FROM livre_tag GROUP BY id_livre) tg ON tg.id_livre = l.id_livre
        WHERE l.is_valide = 1 $condition
        GROUP BY l.id_livre, l.titre, l.auteur, l.couverture, l.id_categorie, c.id_parent, av.moyenne, av.nb, fv.nb, vt.nb, tg.tags", $parametres);
    $candidats = [];
    foreach ($lignes as $ligne) {
        $ligne['id_livre'] = (int) $ligne['id_livre'];
        $ligne['id_categorie'] = $ligne['id_categorie'] !== null ? (int) $ligne['id_categorie'] : null;
        $ligne['id_univers'] = $ligne['id_univers'] !== null ? (int) $ligne['id_univers'] : null;
        $ligne['tags'] = $ligne['tags'] !== null ? array_map('intval', explode(',', $ligne['tags'])) : [];
        $candidats[$ligne['id_livre']] = $ligne;
    }
    return $candidats;
}

function oublierRecommandations(): void
{
    unset($_SESSION['recommandations']);
}

function donneesRecommandation(PDO $pdo, int $idUser): array
{
    $cache = $_SESSION['recommandations'] ?? null;
    if (is_array($cache) && ($cache['id_user'] ?? 0) === $idUser && ($cache['date'] ?? 0) >= time() - RECOMMANDATIONS_DUREE_CACHE) {
        return $cache;
    }
    $profil = profilAffinites(signauxLecteur($pdo, $idUser));
    $candidats = array_diff_key(candidatsRecommandation($pdo), livresExclus($pdo, $idUser));
    $classes = classerRecommandations($candidats, $profil, RECOMMANDATIONS_NOMBRE);
    $cache = [
        'id_user' => $idUser,
        'date' => time(),
        'profil' => $profil,
        'repli' => profilEstVide($profil),
        'livres' => array_map(fn($livre) => ['id_livre' => $livre['id_livre'], 'raison' => $livre['raison']], $classes),
    ];
    $_SESSION['recommandations'] = $cache;
    return $cache;
}

function recommandationsPour(PDO $pdo, int $idUser, int $nombre = RECOMMANDATIONS_NOMBRE): array
{
    $donnees = donneesRecommandation($pdo, $idUser);
    $ids = array_column($donnees['livres'], 'id_livre');
    if (!$ids) {
        return [];
    }
    $cartes = array_diff_key(candidatsRecommandation($pdo, "AND l.id_livre IN (" . marqueursRequete($ids) . ")", $ids), livresExclus($pdo, $idUser));
    $resultat = [];
    foreach ($donnees['livres'] as $livre) {
        if (isset($cartes[$livre['id_livre']]) && count($resultat) < $nombre) {
            $resultat[] = $cartes[$livre['id_livre']] + ['raison' => $livre['raison']];
        }
    }
    return $resultat;
}

function livresSimilaires(PDO $pdo, array $livre, int $idUser, int $nombre = 6, array $poids = POIDS_RECOMMANDATION): array
{
    $idLivre = (int) $livre['id_livre'];
    $categories = categoriesRecommandation($pdo);
    $idCategorie = !empty($livre['id_categorie']) && isset($categories[(int) $livre['id_categorie']]) ? (int) $livre['id_categorie'] : null;
    $idUnivers = $idCategorie ? ($categories[$idCategorie]['id_parent'] ?? null) : null;
    $tags = lignesRecommandation($pdo, "SELECT t.id_tag, t.nom, t.famille FROM livre_tag lt INNER JOIN tag t ON t.id_tag = lt.id_tag WHERE lt.id_livre = ?", [$idLivre]);

    $signaux = [];
    if ($idCategorie) {
        $signaux[] = ['poids' => $poids['livre_courant'], 'cles' => ['c' . $idCategorie => 1.0], 'raison' => sprintf(RAISONS_RECOMMANDATION['meme_categorie'], $categories[$idCategorie]['nom'])];
    }
    if ($idUnivers && isset($categories[$idUnivers])) {
        $signaux[] = ['poids' => $poids['livre_courant'], 'cles' => ['c' . $idUnivers => (float) $poids['part_univers']], 'raison' => sprintf(RAISONS_RECOMMANDATION['meme_univers'], $categories[$idUnivers]['nom'])];
    }
    foreach ($tags as $tag) {
        $signaux[] = ['poids' => $poids['livre_courant'], 'cles' => ['t' . (int) $tag['id_tag'] => (float) $poids['part_tags']],
                      'raison' => sprintf(RAISONS_RECOMMANDATION[($tag['famille'] ?? 'theme') === 'theme' ? 'meme_theme' : 'meme_tag'], $tag['nom'])];
    }
    $auteurs = auteursDuLivre($livre['auteur'] ?? null);
    foreach ($auteurs as $cle => $nom) {
        $signaux[] = ['poids' => $poids['livre_courant'], 'cles' => ['a:' . $cle => (float) $poids['part_auteur']], 'raison' => sprintf(RAISONS_RECOMMANDATION['meme_auteur'], $nom)];
    }
    $serie = serieDuTitre($livre['titre'] ?? null);
    if ($serie !== null) {
        $signaux[] = ['poids' => $poids['livre_courant'], 'cles' => ['s:' . texteNormalise($serie) => (float) $poids['part_serie']], 'raison' => sprintf(RAISONS_RECOMMANDATION['meme_serie'], $serie)];
    }
    $profil = profilAffinites($signaux);

    $conditions = [];
    $parametres = [$idLivre];
    foreach ($auteurs as $nom) {
        $conditions[] = "l.auteur LIKE ?";
        $parametres[] = '%' . addcslashes($nom, '%_\\') . '%';
    }
    if ($serie !== null) {
        $conditions[] = "l.titre LIKE ?";
        $parametres[] = addcslashes($serie, '%_\\') . '%';
    }
    $racine = $idUnivers ?? $idCategorie;
    if ($racine) {
        $conditions[] = "l.id_categorie = ? OR c.id_parent = ?";
        array_push($parametres, $racine, $racine);
    }
    if ($tags) {
        $idsTags = array_map(fn($tag) => (int) $tag['id_tag'], $tags);
        $conditions[] = "EXISTS (SELECT 1 FROM livre_tag lt2 WHERE lt2.id_livre = l.id_livre AND lt2.id_tag IN (" . marqueursRequete($idsTags) . "))";
        array_push($parametres, ...$idsTags);
    }
    $candidats = candidatsRecommandation($pdo, "AND l.id_livre <> ?" . ($conditions ? " AND (" . implode(' OR ', $conditions) . ")" : ''), $parametres);

    if ($idUser) {
        $candidats = array_diff_key($candidats, livresExclus($pdo, $idUser));
        $profilLecteur = donneesRecommandation($pdo, $idUser)['profil'];
        $affiniteMax = max([0, ...array_column($profilLecteur, 'total')]);
        if ($affiniteMax > 0) {
            $profil = fusionnerProfils($profil, $profilLecteur, $poids['livre_courant'] * $poids['part_profil_fiche'] / $affiniteMax, raisonsDesLivres([(string) ($livre['titre'] ?? '')]));
        }
    }
    return classerRecommandations($candidats, $profil, $nombre, $poids);
}

function suggestionsPanier(PDO $pdo, array $panier, int $idUser, float $reste, int $nombre = 8, array $poids = POIDS_RECOMMANDATION): array
{
    $idsPanier = [];
    $quantitesPanier = [];
    foreach ($panier as $item) {
        $idsPanier[(int) $item['id_livre']] = true;
        $quantitesPanier[(int) $item['id_exemplaire']] = ($quantitesPanier[(int) $item['id_exemplaire']] ?? 0) + (int) $item['quantite'];
    }
    $idsPanier = array_keys($idsPanier);
    $categories = categoriesRecommandation($pdo);
    $signaux = [];
    if ($idsPanier) {
        foreach (lignesRecommandation($pdo, "
            SELECT l.id_livre, l.titre, l.auteur, l.id_categorie, GROUP_CONCAT(lt.id_tag) AS tags
            FROM livre l LEFT JOIN livre_tag lt ON lt.id_livre = l.id_livre
            WHERE l.id_livre IN (" . marqueursRequete($idsPanier) . ")
            GROUP BY l.id_livre, l.titre, l.auteur, l.id_categorie", $idsPanier) as $livre) {
            $idCategorie = $livre['id_categorie'] !== null ? (int) $livre['id_categorie'] : null;
            $signaux[] = [
                'poids' => $poids['panier'],
                'cles' => clesLivre($idCategorie, $idCategorie ? ($categories[$idCategorie]['id_parent'] ?? null) : null,
                                    $livre['tags'] !== null ? array_map('intval', explode(',', $livre['tags'])) : [],
                                    (float) $poids['part_univers'], (float) $poids['part_tags'],
                                    $livre['auteur'], $livre['titre'], (float) $poids['part_auteur'], (float) $poids['part_serie']),
                'raison' => sprintf(RAISONS_RECOMMANDATION['panier'], $livre['titre']),
            ];
        }
    }
    $profil = profilAffinites($signaux);
    if ($idUser) {
        $profilLecteur = donneesRecommandation($pdo, $idUser)['profil'];
        $affiniteMax = max([0, ...array_column($profilLecteur, 'total')]);
        if ($affiniteMax > 0) {
            $profil = fusionnerProfils($profil, $profilLecteur, $poids['panier'] * ($signaux ? $poids['part_profil_fiche'] : 1) / $affiniteMax, raisonsDesLivres(array_column($panier, 'titre')));
        }
    }

    $condition = "AND EXISTS (SELECT 1 FROM exemplaire em WHERE em.id_livre = l.id_livre AND " . conditionOffreMagasin('em') . ")";
    if ($idsPanier) {
        $condition .= " AND l.id_livre NOT IN (" . marqueursRequete($idsPanier) . ")";
    }
    $candidats = candidatsRecommandation($pdo, $condition, $idsPanier);
    if ($idUser) {
        $candidats = array_diff_key($candidats, livresExclus($pdo, $idUser));
    }
    if (!$candidats) {
        return [];
    }
    $exemplaires = [];
    $idsCandidats = array_keys($candidats);
    foreach (lignesRecommandation($pdo, "
        SELECT e.id_exemplaire, e.id_livre, e.etat, e.prix, e.quantite FROM exemplaire e
        WHERE e.id_livre IN (" . marqueursRequete($idsCandidats) . ") AND " . conditionOffreMagasin() . "
        ORDER BY e.prix, e.id_exemplaire", $idsCandidats) as $exemplaire) {
        $restant = (int) $exemplaire['quantite'] - ($quantitesPanier[(int) $exemplaire['id_exemplaire']] ?? 0);
        if ($restant > 0) {
            $exemplaires[(int) $exemplaire['id_livre']][] = $exemplaire + ['restant' => $restant];
        }
    }
    $classes = classerRecommandations(array_intersect_key($candidats, $exemplaires), $profil, count($exemplaires), $poids);
    foreach ($classes as &$livre) {
        $livre['exemplaires'] = $exemplaires[$livre['id_livre']];
        $livre['prix'] = (float) $livre['exemplaires'][0]['prix'];
        if ($reste > 0 && $livre['prix'] <= $reste + 3) {
            $livre['score'] += $poids['bonus_complement'];
        }
    }
    unset($livre);
    usort($classes, fn($a, $b) => [$b['score'], $b['id_livre']] <=> [$a['score'], $a['id_livre']]);
    return diversifierRecommandations($classes, $nombre, (int) $poids['max_par_categorie']);
}

function categoriesPertinence(PDO $pdo, int $idUser, int $nombre = 5): array
{
    $categories = categoriesRecommandation($pdo);
    $totaux = [];
    foreach (donneesRecommandation($pdo, $idUser)['profil'] as $cle => $affinite) {
        if ($cle[0] === 'c' && $affinite['total'] > 0) {
            $totaux[(int) substr($cle, 1)] = $affinite['total'];
        }
    }
    arsort($totaux);
    $ids = [];
    foreach (array_slice(array_keys($totaux), 0, $nombre) as $idCategorie) {
        $ids[] = $idCategorie;
        foreach ($categories as $idEnfant => $categorie) {
            if ($categorie['id_parent'] === $idCategorie) {
                $ids[] = $idEnfant;
            }
        }
    }
    return array_values(array_unique($ids));
}

function enregistrerConsultation(PDO $pdo, int $idUser, int $idLivre): void
{
    try {
        if (!historiqueActif($pdo, $idUser)) {
            return;
        }
        $ajout = $pdo->prepare("INSERT INTO consultation_livre (id_user, id_livre, date_consultation) VALUES (?, ?, NOW())
                       ON DUPLICATE KEY UPDATE date_consultation = NOW()");
        $ajout->execute([$idUser, $idLivre]);
        if ($ajout->rowCount() === 1) {
            oublierRecommandations();
        }
        $pdo->prepare("DELETE FROM consultation_livre WHERE id_user = ? AND date_consultation < NOW() - INTERVAL " . HISTORIQUE_DUREE_JOURS . " DAY")->execute([$idUser]);
        $stmt = $pdo->prepare("SELECT id_livre FROM consultation_livre WHERE id_user = ?
                               ORDER BY date_consultation DESC, id_livre DESC LIMIT 1000 OFFSET " . HISTORIQUE_TAILLE_MAX);
        $stmt->execute([$idUser]);
        $enTrop = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if ($enTrop) {
            $pdo->prepare("DELETE FROM consultation_livre WHERE id_user = ? AND id_livre IN (" . marqueursRequete($enTrop) . ")")->execute([$idUser, ...$enTrop]);
        }
    } catch (PDOException $e) {
    }
}

function historiqueConsultations(PDO $pdo, int $idUser): array
{
    return lignesRecommandation($pdo, "
        SELECT l.id_livre, l.titre, l.auteur, l.couverture, c.date_consultation
        FROM consultation_livre c INNER JOIN livre l ON l.id_livre = c.id_livre
        WHERE c.id_user = ? AND l.is_valide = 1
        ORDER BY c.date_consultation DESC, c.id_livre DESC LIMIT " . HISTORIQUE_TAILLE_MAX, [$idUser]);
}

function effacerHistorique(PDO $pdo, int $idUser): void
{
    $pdo->prepare("DELETE FROM consultation_livre WHERE id_user = ?")->execute([$idUser]);
    oublierRecommandations();
}

function definirHistoriqueActif(PDO $pdo, int $idUser, bool $actif): void
{
    $pdo->prepare("UPDATE user SET historique_actif = ? WHERE id = ?")->execute([$actif ? 1 : 0, $idUser]);
    oublierRecommandations();
}

function preferencesLecteur(PDO $pdo, int $idUser): array
{
    return [
        'categories' => array_map('intval', array_column(lignesRecommandation($pdo, "SELECT id_categorie FROM preference_categorie WHERE id_user = ?", [$idUser]), 'id_categorie')),
        'tags' => array_map('intval', array_column(lignesRecommandation($pdo, "SELECT id_tag FROM preference_tag WHERE id_user = ?", [$idUser]), 'id_tag')),
    ];
}

function idsChoisis(mixed $valeurs): array
{
    if (!is_array($valeurs)) {
        return [];
    }
    $ids = array_filter(array_map(fn($valeur) => is_scalar($valeur) ? filter_var($valeur, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false, $valeurs));
    return array_slice(array_values(array_unique($ids)), 0, PREFERENCES_MAX);
}

function enregistrerPreferences(PDO $pdo, int $idUser, array $idsCategories, array $idsTags): void
{
    $categoriesValides = $idsCategories ? array_map('intval', $pdo->query("SELECT id_categorie FROM categorie")->fetchAll(PDO::FETCH_COLUMN)) : [];
    $tagsValides = $idsTags ? array_map('intval', $pdo->query("SELECT id_tag FROM tag WHERE famille = 'theme'")->fetchAll(PDO::FETCH_COLUMN)) : [];
    $idsCategories = array_values(array_intersect($idsCategories, $categoriesValides));
    $idsTags = array_values(array_intersect($idsTags, $tagsValides));
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM preference_categorie WHERE id_user = ?")->execute([$idUser]);
        $pdo->prepare("DELETE FROM preference_tag WHERE id_user = ?")->execute([$idUser]);
        $ajoutCategorie = $pdo->prepare("INSERT INTO preference_categorie (id_user, id_categorie) VALUES (?, ?)");
        foreach ($idsCategories as $idCategorie) {
            $ajoutCategorie->execute([$idUser, $idCategorie]);
        }
        $ajoutTag = $pdo->prepare("INSERT INTO preference_tag (id_user, id_tag) VALUES (?, ?)");
        foreach ($idsTags as $idTag) {
            $ajoutTag->execute([$idUser, $idTag]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    oublierRecommandations();
}
