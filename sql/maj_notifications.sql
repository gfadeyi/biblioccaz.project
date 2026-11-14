SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS notification (
    id_notification INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    message VARCHAR(255) NOT NULL,
    lien VARCHAR(255) NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_lecture DATETIME NULL,
    INDEX idx_notification_membre (id_user, lu, date_creation),
    CONSTRAINT fk_notification_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
