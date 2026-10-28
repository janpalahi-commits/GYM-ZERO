-- =====================================================================
-- GYM-ZERO · Actualització de la Fase 11 (per a una base de dades que ja existeix)
-- Afegeix la taula per recuperar la contrasenya per correu.
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho i Continua.
-- No esborra cap dada i es pot executar dues vegades.
-- =====================================================================

-- ---------------------------------------------------------------------
-- RECUPERACIONS: enllaços per posar una contrasenya nova ("Has oblidat la contrasenya?")
-- Només es guarda el hash de l'enllaç; l'enllaç sencer només és al correu de l'usuari
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recuperacions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  token_hash  CHAR(64)     NOT NULL,             -- SHA-256 del codi de l'enllaç
  creada      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  caduca      DATETIME     NOT NULL,             -- l'enllaç serveix 1 hora
  usada       TINYINT(1)   NOT NULL DEFAULT 0,   -- cada enllaç només es pot fer servir una vegada
  CONSTRAINT uq_recuperacions_token UNIQUE (token_hash),
  CONSTRAINT fk_recuperacions_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;
