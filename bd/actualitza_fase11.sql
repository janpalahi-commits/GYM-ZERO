CREATE TABLE IF NOT EXISTS recuperacions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  token_hash  CHAR(64)     NOT NULL,
  creada      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  caduca      DATETIME     NOT NULL,
  usada       TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT uq_recuperacions_token UNIQUE (token_hash),
  CONSTRAINT fk_recuperacions_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;
