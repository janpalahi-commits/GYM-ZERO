-- =====================================================================
-- GYM-ZERO · Actualització de la Fase 12 (per a una base de dades que ja existeix)
-- Tarifes (gimnàs, + pla, + seguiment), pagaments, qüestionari del pla personalitzat,
-- PDF del pla, rutina i llibreta, revisions i missatges amb l'entrenador.
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho i Continua.
-- No esborra cap dada i es pot executar dues vegades. Tots els clients comencen a la tarifa Gimnàs.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- TARIFES: el que paga cada client cada mes. El nivell diu quins serveis té:
-- 1 = gimnàs, 2 = + pla personalitzat i llibreta, 3 = + seguiment amb l'entrenador
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tarifes (
  id           TINYINT UNSIGNED PRIMARY KEY,
  nom          VARCHAR(40)  NOT NULL,
  nivell       TINYINT UNSIGNED NOT NULL,
  preu_mes     DECIMAL(6,2) NOT NULL,
  preu_3mesos  DECIMAL(6,2) NOT NULL,
  inclou       VARCHAR(255) NOT NULL,            -- el que es mostra a la portada
  CONSTRAINT uq_tarifes_nivell UNIQUE (nivell),
  CONSTRAINT ck_tarifes_preus CHECK (preu_mes > 0 AND preu_3mesos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO tarifes (id, nom, nivell, preu_mes, preu_3mesos, inclou) VALUES
 (1, 'Gimnàs', 1, 55.00, 142.00, 'Sales de força i cardio, classes amb reserva i les sèries de les màquines amb la polsera.'),
 (2, 'Gimnàs + pla', 2, 80.00, 210.00, 'Tot el del gimnàs, un pla d''entrenament i alimentació fet per a tu i la llibreta per apuntar els entrenos.'),
 (3, 'Gimnàs + pla + seguiment', 3, 110.00, 300.00, 'Tot l''anterior, una revisió amb l''entrenador cada 2 setmanes i missatges per resoldre dubtes i canviar la rutina.');

ALTER TABLE usuaris
  ADD COLUMN IF NOT EXISTS tarifa_id TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER quota_fins,
  ADD CONSTRAINT fk_usuaris_tarifa FOREIGN KEY IF NOT EXISTS (tarifa_id)
    REFERENCES tarifes(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- ---------------------------------------------------------------------
-- PAGAMENTS: cada cobrament de recepció (quina tarifa, quants mesos, quant i fins quan)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagaments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  tarifa_id   TINYINT UNSIGNED NOT NULL,
  mesos       TINYINT UNSIGNED NOT NULL,
  import      DECIMAL(6,2) NOT NULL,
  fins        DATE         NOT NULL,             -- últim dia pagat amb aquest cobrament
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cobrat_per  INT UNSIGNED NULL,                 -- qui de recepció l'ha cobrat
  CONSTRAINT fk_pagaments_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_pagaments_tarifa FOREIGN KEY (tarifa_id)
    REFERENCES tarifes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pagaments_cobrat FOREIGN KEY (cobrat_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_pagaments_mesos CHECK (mesos IN (1, 3))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- QUESTIONARIS: les respostes del qüestionari inicial del pla personalitzat.
-- Hi ha dades de salut (lesions, malalties): només les veu l'entrenador i cal el consentiment.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS questionaris (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id     INT UNSIGNED NOT NULL,
  respostes     LONGTEXT     NOT NULL,           -- JSON com una fila de la fulla d'APP GYM: {"Peso actual (kg)": 78.5, ...}
  consentiment  TINYINT(1)   NOT NULL,           -- ha acceptat que es facin servir les dades de salut
  enviat        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_questionaris_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT ck_questionaris_json CHECK (JSON_VALID(respostes)),
  CONSTRAINT ck_questionaris_consentiment CHECK (consentiment = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PLANS: el PDF del pla d'entrenament i alimentació que fa l'entrenador i descarrega el client
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  nom_fitxer  VARCHAR(120) NOT NULL,
  pdf         MEDIUMBLOB   NOT NULL,             -- fins a 16 MB
  pujat       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pujat_per   INT UNSIGNED NULL,
  CONSTRAINT fk_plans_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_plans_pujat FOREIGN KEY (pujat_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- RUTINA: els exercicis del pla de cada client, per dia de la setmana.
-- Són les files de la llibreta. Si l'exercici es fa en una màquina intel·ligent,
-- les sèries hi surten soles (taula series).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rutina (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  dia         TINYINT UNSIGNED NOT NULL,         -- 1 = dilluns ... 7 = diumenge
  ordre       TINYINT UNSIGNED NOT NULL,
  exercici    VARCHAR(80)  NOT NULL,
  series      TINYINT UNSIGNED NOT NULL,
  maquina_id  INT UNSIGNED NULL,
  nota        VARCHAR(120) NULL,                 -- ex.: banc a 30°, agafada neutra
  CONSTRAINT fk_rutina_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rutina_maquina FOREIGN KEY (maquina_id)
    REFERENCES maquines(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_rutina_dia CHECK (dia BETWEEN 1 AND 7),
  CONSTRAINT ck_rutina_series CHECK (series BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REGISTRE: les sèries que el client apunta a mà a la llibreta
-- (manuelles, pes corporal... el que no fa una màquina intel·ligent)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS registre (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rutina_id   INT UNSIGNED NOT NULL,
  data        DATE         NOT NULL,
  num_serie   TINYINT UNSIGNED NOT NULL,
  carrega_kg  DECIMAL(5,1) NULL,                 -- NULL: pes corporal
  reps        TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_registre_rutina FOREIGN KEY (rutina_id)
    REFERENCES rutina(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_registre_serie UNIQUE (rutina_id, data, num_serie),
  CONSTRAINT ck_registre_valors CHECK (reps BETWEEN 1 AND 100 AND (carrega_kg IS NULL OR carrega_kg BETWEEN 0 AND 500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REVISIONS: cada 2 setmanes el client explica com li va i l'entrenador li respon
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS revisions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  data        DATE         NOT NULL,
  pes_kg      DECIMAL(5,1) NULL,
  com_va      TEXT         NOT NULL,             -- el que escriu el client
  resposta    TEXT         NULL,                 -- el que respon l'entrenador
  respost_per INT UNSIGNED NULL,
  respost     DATETIME     NULL,
  CONSTRAINT fk_revisions_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_revisions_respost FOREIGN KEY (respost_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MISSATGES: dubtes i canvis entre el client i l'entrenador (una conversa per client)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS missatges (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,             -- el client de la conversa
  autor_id    INT UNSIGNED NULL,                 -- el client o algú del gimnàs
  text        TEXT         NOT NULL,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  llegit      TINYINT(1)   NOT NULL DEFAULT 0,   -- l'ha llegit l'altra part
  CONSTRAINT fk_missatges_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_missatges_autor FOREIGN KEY (autor_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX ix_missatges_usuari (usuari_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;
