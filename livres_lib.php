<?php

const ETATS_LIVRE = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];

function libelleEtat(string $etat): string
{
    return ETATS_LIVRE[$etat] ?? ucfirst($etat);
}

function aUneVraieCouverture(?string $fichier): bool
{
    if ($fichier && preg_match('~\Ahttps://covers\.openlibrary\.org/b/id/[1-9][0-9]*-[SML]\.jpg\z~', $fichier)) {
        return true;
    }
    return $fichier
        && is_file(__DIR__ . '/img/' . $fichier)
        && !str_ends_with(pathinfo($fichier, PATHINFO_FILENAME), '-generee');
}

function privilegierVraiesCouvertures(array $livres, int $nombre): array
{
    $avecCouverture = array_filter($livres, fn($livre) => aUneVraieCouverture($livre['couverture'] ?? null));
    $sansCouverture = array_diff_key($livres, $avecCouverture);
    return array_slice([...array_values($avecCouverture), ...array_values($sansCouverture)], 0, $nombre);
}

function conditionOffreMagasin(string $alias = 'e'): string
{
    return "$alias.id_proprietaire IS NULL AND $alias.type_offre = 'vente' AND $alias.is_disponible = 1 AND $alias.quantite > 0";
}

function conditionExemplaireMagasin(string $alias = 'e'): string
{
    return "$alias.id_proprietaire IS NULL AND $alias.type_offre = 'vente'";
}

const ORIGINES_OFFRE = ['magasin' => 'En magasin', 'particulier' => 'Entre particuliers', 'don' => 'Don entre lecteurs', 'emprunt' => 'Emprunt'];
const STATUTS_COMPTE_SUSPENDU = ['refuse_temporaire', 'refuse_definitif'];

function conditionAnnoncePubliee(string $alias = 'e'): string
{
    return "$alias.id_proprietaire IS NOT NULL AND $alias.type_offre = 'vente' AND $alias.statut_annonce = 'publiee' AND $alias.quantite > 0
            AND EXISTS (SELECT 1 FROM user vendeur WHERE vendeur.id = $alias.id_proprietaire
                        AND vendeur.statut NOT IN ('" . implode("', '", STATUTS_COMPTE_SUSPENDU) . "'))";
}

function sqlOffres(array $origines = []): string
{
    $requetesParOrigine = [
        'magasin' => "SELECT ex.id_exemplaire AS id_offre, ex.id_livre, ex.etat, ex.prix, ex.quantite, 'magasin' AS origine
                      FROM exemplaire ex WHERE " . conditionOffreMagasin('ex'),
        'particulier' => "SELECT an.id_exemplaire AS id_offre, an.id_livre, an.etat, an.prix, 1 AS quantite, 'particulier' AS origine
                          FROM exemplaire an WHERE " . conditionAnnoncePubliee('an'),
        'don'     => "SELECT d.id_don AS id_offre, d.id_livre, d.etat, 0 AS prix, 1 AS quantite, 'don' AS origine
                      FROM don d WHERE d.statut = 'disponible' AND d.id_livre IS NOT NULL AND d.date_limite >= CURDATE()",
        'emprunt' => "SELECT ep.id_exemplaire AS id_offre, ep.id_livre, ep.etat, 0 AS prix, 1 AS quantite, 'emprunt' AS origine
                      FROM exemplaire ep WHERE ep.id_proprietaire IS NULL AND ep.type_offre = 'emprunt' AND ep.is_disponible = 1",
    ];
    $choisies = array_intersect_key($requetesParOrigine, array_flip($origines ?: array_keys($requetesParOrigine)));
    return '(' . implode(' UNION ALL ', $choisies ?: $requetesParOrigine) . ')';
}

function sqlCartesLivres(string $conditions = ''): string
{
    return "
        SELECT l.id_livre, l.titre, l.auteur, l.couverture,
               MIN(e.prix) AS prix,
               SUBSTRING_INDEX(GROUP_CONCAT(e.etat ORDER BY e.prix ASC), ',', 1) AS etat,
               SUM(e.quantite) AS stock,
               COUNT(e.id_exemplaire) AS nb_offres,
               av.moyenne AS note_moyenne, COALESCE(av.nb, 0) AS nb_avis,
               COALESCE(fv.nb, 0) AS nb_favoris
        FROM livre l
        INNER JOIN exemplaire e ON e.id_livre = l.id_livre AND " . conditionOffreMagasin() . "
        LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb
                   FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre
        LEFT JOIN (SELECT id_livre, COUNT(*) AS nb FROM favori GROUP BY id_livre) fv ON fv.id_livre = l.id_livre
        WHERE l.is_valide = 1 $conditions
        GROUP BY l.id_livre";
}
