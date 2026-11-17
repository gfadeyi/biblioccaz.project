SET NAMES utf8mb4;

START TRANSACTION;
INSERT IGNORE INTO boutique_reprise (cle) VALUES ('stock-demonstration-six-villes-v1');
SET @repartir_boutiques = ROW_COUNT();
SET @nombre_boutiques = (SELECT COUNT(*) FROM boutique WHERE actif = 1 AND slug IN ('paris', 'lyon', 'bordeaux', 'lille', 'nantes', 'toulouse'));

UPDATE exemplaire e
INNER JOIN (
    SELECT ex.id_exemplaire, MOD(ROW_NUMBER() OVER (ORDER BY ex.id_livre, ex.id_exemplaire) - 1, 6) AS rang
    FROM exemplaire ex
    WHERE ex.type_offre = 'emprunt' AND ex.id_proprietaire IS NULL AND ex.id_boutique IS NULL
      AND ex.is_disponible = 1 AND ex.statut_pret = 'disponible'
      AND NOT EXISTS (SELECT 1 FROM emprunt em WHERE em.id_exemplaire = ex.id_exemplaire AND em.statut IN ('reserve', 'en_cours'))
) disponibles ON disponibles.id_exemplaire = e.id_exemplaire
INNER JOIN (
    SELECT id_boutique, ROW_NUMBER() OVER (ORDER BY slug) - 1 AS rang
    FROM boutique WHERE actif = 1 AND slug IN ('paris', 'lyon', 'bordeaux', 'lille', 'nantes', 'toulouse')
) destination ON destination.rang = disponibles.rang
SET e.id_boutique = destination.id_boutique
WHERE @repartir_boutiques = 1 AND @nombre_boutiques = 6 AND e.id_boutique IS NULL
  AND NOT EXISTS (SELECT 1 FROM emprunt actif WHERE actif.id_exemplaire = e.id_exemplaire AND actif.statut IN ('reserve', 'en_cours'));

SET @nombre_affectes = ROW_COUNT();
UPDATE boutique_reprise SET nombre = @nombre_affectes WHERE cle = 'stock-demonstration-six-villes-v1' AND @repartir_boutiques = 1;
DELETE FROM boutique_reprise WHERE cle = 'stock-demonstration-six-villes-v1' AND @repartir_boutiques = 1 AND @nombre_boutiques <> 6;
COMMIT;
