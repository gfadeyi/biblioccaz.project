SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS equipe_conversation (
  id_conversation      INT AUTO_INCREMENT PRIMARY KEY,
  type                 VARCHAR(10)  NOT NULL,
  titre                VARCHAR(120) NULL,
  cle_unique           VARCHAR(40)  NULL,
  id_createur          INT          NULL,
  date_creation        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_dernier_message DATETIME     NULL,
  UNIQUE KEY uq_equipe_conversation_cle (cle_unique),
  INDEX idx_equipe_conversation_date (date_dernier_message),
  CONSTRAINT fk_equipe_conversation_createur FOREIGN KEY (id_createur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipe_participant (
  id_conversation INT      NOT NULL,
  id_user         INT      NOT NULL,
  id_dernier_lu   INT      NOT NULL DEFAULT 0,
  date_ajout      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_conversation, id_user),
  INDEX idx_equipe_participant_user (id_user),
  CONSTRAINT fk_equipe_participant_conversation FOREIGN KEY (id_conversation) REFERENCES equipe_conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_equipe_participant_user         FOREIGN KEY (id_user)         REFERENCES `user`(id)                           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipe_message (
  id_message      INT AUTO_INCREMENT PRIMARY KEY,
  id_conversation INT           NOT NULL,
  id_auteur       INT           NULL,
  contenu         VARCHAR(2000) NOT NULL,
  date_envoi      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_equipe_message_conversation (id_conversation, id_message),
  INDEX idx_equipe_message_auteur (id_auteur, date_envoi),
  CONSTRAINT fk_equipe_message_conversation FOREIGN KEY (id_conversation) REFERENCES equipe_conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_equipe_message_auteur       FOREIGN KEY (id_auteur)       REFERENCES `user`(id)                           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO equipe_conversation (type, titre, cle_unique) VALUES ('equipe', 'Équipe', 'equipe');
