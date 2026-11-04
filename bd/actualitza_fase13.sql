SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS intents_login (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(150) NOT NULL,
  ip          VARCHAR(45)  NOT NULL,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_intents_email (email, data),
  INDEX ix_intents_ip (ip, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracio (
  clau        VARCHAR(40)  PRIMARY KEY,
  valor       VARCHAR(255) NOT NULL,
  canviat     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
