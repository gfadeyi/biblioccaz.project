<?php

const ETATS_LIVRE = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];

function libelleEtat(string $etat): string
{
    return ETATS_LIVRE[$etat] ?? ucfirst($etat);
}

function formatPrix(float $prix): string
{
    return number_format($prix, 2, ',', ' ') . ' €';
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
        INNER JOIN exemplaire e ON e.id_livre = l.id_livre AND e.quantite > 0 AND e.is_disponible = 1
        LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb
                   FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre
        LEFT JOIN (SELECT id_livre, COUNT(*) AS nb FROM favori GROUP BY id_livre) fv ON fv.id_livre = l.id_livre
        WHERE l.is_valide = 1 $conditions
        GROUP BY l.id_livre";
}
