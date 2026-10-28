SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS statut_pret VARCHAR(20) NULL AFTER date_moderation;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS duree_emprunt_jours SMALLINT NOT NULL DEFAULT 21 AFTER statut_pret;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS renouvellements_max TINYINT NOT NULL DEFAULT 2 AFTER duree_emprunt_jours;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS blocage_retard TINYINT(1) NOT NULL DEFAULT 1 AFTER renouvellements_max;
ALTER TABLE exemplaire ADD INDEX IF NOT EXISTS idx_exemplaire_pret (type_offre, statut_pret, id_livre);

CREATE TABLE IF NOT EXISTS emprunt (
  id_emprunt                  INT AUTO_INCREMENT PRIMARY KEY,
  id_exemplaire               INT          NOT NULL,
  id_user                     INT          NOT NULL,
  statut                      VARCHAR(20)  NOT NULL DEFAULT 'reserve',
  code_retrait                CHAR(6)      NOT NULL,
  duree_jours                 SMALLINT     NOT NULL DEFAULT 21,
  renouvellements_max         TINYINT      NOT NULL DEFAULT 2,
  blocage_retard              TINYINT(1)   NOT NULL DEFAULT 1,
  nb_renouvellements          TINYINT      NOT NULL DEFAULT 0,
  date_reservation            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration_reservation DATETIME     NOT NULL,
  date_retrait                DATETIME     NULL,
  date_retour_prevue          DATE         NULL,
  date_retour                 DATETIME     NULL,
  jours_retard                SMALLINT     NOT NULL DEFAULT 0,
  id_valide_par               INT          NULL,
  id_retour_par               INT          NULL,
  rappel_avant                TINYINT(1)   NOT NULL DEFAULT 0,
  rappel_jour                 TINYINT(1)   NOT NULL DEFAULT 0,
  nb_relances                 SMALLINT     NOT NULL DEFAULT 0,
  date_derniere_relance       DATETIME     NULL,
  motif_annulation            VARCHAR(500) NULL,
  date_fin                    DATETIME     NULL,
  UNIQUE KEY uq_emprunt_code (code_retrait),
  INDEX idx_emprunt_user (id_user, statut),
  INDEX idx_emprunt_exemplaire (id_exemplaire, statut),
  INDEX idx_emprunt_statut (statut, date_retour_prevue),
  CONSTRAINT fk_emprunt_exemplaire FOREIGN KEY (id_exemplaire) REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE,
  CONSTRAINT fk_emprunt_user       FOREIGN KEY (id_user)       REFERENCES `user`(id)                ON DELETE CASCADE,
  CONSTRAINT fk_emprunt_valide_par FOREIGN KEY (id_valide_par) REFERENCES `user`(id)                ON DELETE SET NULL,
  CONSTRAINT fk_emprunt_retour_par FOREIGN KEY (id_retour_par) REFERENCES `user`(id)                ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE emprunt ADD COLUMN IF NOT EXISTS blocage_retard TINYINT(1) NULL AFTER renouvellements_max;
UPDATE emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire SET em.blocage_retard = e.blocage_retard WHERE em.blocage_retard IS NULL;
ALTER TABLE emprunt MODIFY COLUMN blocage_retard TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS blocage_emprunt (
  id_blocage  INT AUTO_INCREMENT PRIMARY KEY,
  id_user     INT          NOT NULL,
  id_emprunt  INT          NULL,
  jours       SMALLINT     NOT NULL,
  date_debut  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_fin    DATETIME     NOT NULL,
  motif       VARCHAR(500) NOT NULL,
  date_levee  DATETIME     NULL,
  id_leve_par INT          NULL,
  motif_levee VARCHAR(500) NULL,
  INDEX idx_blocage_user (id_user, date_levee, date_fin),
  CONSTRAINT fk_blocage_user    FOREIGN KEY (id_user)     REFERENCES `user`(id)          ON DELETE CASCADE,
  CONSTRAINT fk_blocage_emprunt FOREIGN KEY (id_emprunt)  REFERENCES emprunt(id_emprunt) ON DELETE SET NULL,
  CONSTRAINT fk_blocage_leve    FOREIGN KEY (id_leve_par) REFERENCES `user`(id)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emprunt_attente (
  id_attente   INT AUTO_INCREMENT PRIMARY KEY,
  id_livre     INT      NOT NULL,
  id_user      INT      NOT NULL,
  date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attente_livre_user (id_livre, id_user),
  INDEX idx_attente_livre (id_livre, date_demande),
  CONSTRAINT fk_attente_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_attente_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
