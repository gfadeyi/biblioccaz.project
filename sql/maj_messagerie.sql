SET NAMES utf8mb4;
USE biblioccaz;

CREATE TABLE IF NOT EXISTS conversation (
  id_conversation      INT AUTO_INCREMENT PRIMARY KEY,
  type_objet           VARCHAR(20)  NOT NULL,
  id_objet             INT          NOT NULL,
  statut               VARCHAR(20)  NOT NULL DEFAULT 'ouverte',
  motif_fermeture      VARCHAR(255) NULL,
  date_creation        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_dernier_message DATETIME     NULL,
  date_fermeture       DATETIME     NULL,
  INDEX idx_conversation_objet (type_objet, id_objet),
  INDEX idx_conversation_statut (statut, date_dernier_message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_participant (
  id_conversation       INT         NOT NULL,
  id_user               INT         NOT NULL,
  role_participant      VARCHAR(20) NOT NULL,
  date_derniere_lecture DATETIME    NULL,
  date_dernier_mail     DATETIME    NULL,
  PRIMARY KEY (id_conversation, id_user),
  INDEX idx_participant_user (id_user),
  CONSTRAINT fk_participant_conversation FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_participant_user         FOREIGN KEY (id_user)         REFERENCES `user`(id)                    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_message (
  id_message      INT AUTO_INCREMENT PRIMARY KEY,
  id_conversation INT           NOT NULL,
  id_auteur       INT           NULL,
  contenu         VARCHAR(1000) NOT NULL,
  est_systeme     TINYINT(1)    NOT NULL DEFAULT 0,
  est_masque      TINYINT(1)    NOT NULL DEFAULT 0,
  id_masque_par   INT           NULL,
  date_envoi      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_masquage   DATETIME      NULL,
  INDEX idx_message_conversation (id_conversation, date_envoi),
  INDEX idx_message_auteur (id_auteur, date_envoi),
  CONSTRAINT fk_message_conversation FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_message_auteur       FOREIGN KEY (id_auteur)       REFERENCES `user`(id)                    ON DELETE SET NULL,
  CONSTRAINT fk_message_masque_par   FOREIGN KEY (id_masque_par)   REFERENCES `user`(id)                    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_signalement (
  id_signalement  INT AUTO_INCREMENT PRIMARY KEY,
  id_message      INT          NOT NULL,
  id_user         INT          NOT NULL,
  motif           VARCHAR(30)  NOT NULL,
  commentaire     VARCHAR(500) NULL,
  traite          TINYINT(1)   NOT NULL DEFAULT 0,
  id_traite_par   INT          NULL,
  date_signalement DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_traitement DATETIME     NULL,
  UNIQUE KEY uq_signalement_message (id_message, id_user),
  INDEX idx_signalement_traite (traite, date_signalement),
  CONSTRAINT fk_signalement_message    FOREIGN KEY (id_message)    REFERENCES conversation_message(id_message) ON DELETE CASCADE,
  CONSTRAINT fk_signalement_user       FOREIGN KEY (id_user)       REFERENCES `user`(id)                       ON DELETE CASCADE,
  CONSTRAINT fk_signalement_traite_par FOREIGN KEY (id_traite_par) REFERENCES `user`(id)                       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO conversation (type_objet, id_objet, statut, date_creation, date_dernier_message)
SELECT 'don', d.id_don, 'ouverte', d.date_reservation, d.date_reservation
FROM don d
WHERE d.statut = 'reserve' AND d.id_beneficiaire IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM conversation c WHERE c.type_objet = 'don' AND c.id_objet = d.id_don);

INSERT IGNORE INTO conversation_participant (id_conversation, id_user, role_participant, date_derniere_lecture)
SELECT c.id_conversation, d.id_donneur, 'donneur', NULL
FROM conversation c INNER JOIN don d ON d.id_don = c.id_objet
WHERE c.type_objet = 'don';

INSERT IGNORE INTO conversation_participant (id_conversation, id_user, role_participant, date_derniere_lecture)
SELECT c.id_conversation, d.id_beneficiaire, 'beneficiaire', c.date_creation
FROM conversation c INNER JOIN don d ON d.id_don = c.id_objet
WHERE c.type_objet = 'don' AND d.id_beneficiaire IS NOT NULL;

INSERT INTO conversation_message (id_conversation, id_auteur, contenu, est_systeme, date_envoi)
SELECT c.id_conversation, NULL, CONCAT('@', u.pseudo, ' a réservé ce don. Convenez ici du jour et du lieu de retrait.'), 1, c.date_creation
FROM conversation c
INNER JOIN don d ON d.id_don = c.id_objet
INNER JOIN `user` u ON u.id = d.id_beneficiaire
WHERE c.type_objet = 'don'
  AND NOT EXISTS (SELECT 1 FROM conversation_message m WHERE m.id_conversation = c.id_conversation);
