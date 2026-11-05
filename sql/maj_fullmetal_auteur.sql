SET NAMES utf8mb4;

UPDATE serie s
SET s.auteur = 'Hiromu Arakawa'
WHERE s.nom = 'Fullmetal Alchemist' AND s.auteur = 'Makoto Inoue'
  AND NOT EXISTS (SELECT 1 FROM (SELECT id_serie FROM serie WHERE nom = 'Fullmetal Alchemist' AND auteur = 'Hiromu Arakawa') autre);
