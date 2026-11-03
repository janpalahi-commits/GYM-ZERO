-- =====================================================================
-- GYM-ZERO · Actualització de la Fase 13 (seguretat), per a una base de dades que ja existeix
-- Afegeix la taula dels intents d'entrada fallits (límit d'intents al login).
-- phpMyAdmin > base de dades > Importar (o SQL > enganxa-ho) i Continua.
-- No esborra cap dada i es pot executar dues vegades.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- INTENTS_LOGIN: entrades fallides, per bloquejar qui prova contrasenyes una rere l'altra.
-- 5 errors amb el mateix correu o 20 des de la mateixa IP en 15 minuts: 15 minuts sense poder entrar.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intents_login (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(150) NOT NULL,             -- el que s'ha escrit, encara que no sigui de ningú
  ip          VARCHAR(45)  NOT NULL,             -- IPv4 o IPv6
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_intents_email (email, data),
  INDEX ix_intents_ip (ip, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CONFIGURACIO: valors de la web que no són de cap taula concreta.
-- Ara: el hash de la clau amb què el PC de recepció es descarrega les còpies de seguretat.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracio (
  clau        VARCHAR(40)  PRIMARY KEY,
  valor       VARCHAR(255) NOT NULL,
  canviat     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
