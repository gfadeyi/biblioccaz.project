USE biblioccaz;

CREATE TABLE IF NOT EXISTS commande (
  id_commande     INT AUTO_INCREMENT PRIMARY KEY,
  id_user         INT          NOT NULL,
  date_commande   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  prenom          VARCHAR(100) NOT NULL,
  nom             VARCHAR(100) NOT NULL,
  adresse         VARCHAR(255) NOT NULL,
  sous_total      DECIMAL(8,2) NOT NULL,
  remise          DECIMAL(8,2) NOT NULL DEFAULT 0,
  frais_livraison DECIMAL(8,2) NOT NULL DEFAULT 0,
  total           DECIMAL(8,2) NOT NULL,
  points_gagnes   INT          NOT NULL DEFAULT 0,
  CONSTRAINT fk_commande_user FOREIGN KEY (id_user)
    REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commande_ligne (
  id_ligne        INT AUTO_INCREMENT PRIMARY KEY,
  id_commande     INT          NOT NULL,
  id_livre        INT          NULL,
  titre           VARCHAR(255) NOT NULL,
  etat            VARCHAR(50)  NOT NULL,
  prix_unitaire   DECIMAL(8,2) NOT NULL,
  quantite        INT          NOT NULL,
  CONSTRAINT fk_ligne_commande FOREIGN KEY (id_commande)
    REFERENCES commande(id_commande) ON DELETE CASCADE,
  CONSTRAINT fk_ligne_livre FOREIGN KEY (id_livre)
    REFERENCES livre(id_livre) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
