SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_resum_series;
DROP TABLE IF EXISTS configuracio, intents_login, missatges, revisions, registre, rutina, plans, questionaris, pagaments,
                     recuperacions, entrades, torns, notificacions, repeticions, series, entrenaments,
                     maquines, reserves, classes, activitats, usuaris, tarifes;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE tarifes (
  id           TINYINT UNSIGNED PRIMARY KEY,
  nom          VARCHAR(40)  NOT NULL,
  nivell       TINYINT UNSIGNED NOT NULL,
  preu_mes     DECIMAL(6,2) NOT NULL,
  preu_3mesos  DECIMAL(6,2) NOT NULL,
  inclou       VARCHAR(255) NOT NULL,
  CONSTRAINT uq_tarifes_nivell UNIQUE (nivell),
  CONSTRAINT ck_tarifes_preus CHECK (preu_mes > 0 AND preu_3mesos > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tarifes (id, nom, nivell, preu_mes, preu_3mesos, inclou) VALUES
 (1, 'Gimnàs', 1, 55.00, 142.00, 'Sales de força i cardio, classes amb reserva i les sèries de les màquines amb la polsera.'),
 (2, 'Gimnàs + pla', 2, 80.00, 210.00, 'Tot el del gimnàs, un pla d''entrenament i alimentació fet per a tu i la llibreta per apuntar els entrenos.'),
 (3, 'Gimnàs + pla + seguiment', 3, 110.00, 300.00, 'Tot l''anterior, una revisió amb l''entrenador cada 2 setmanes i missatges per resoldre dubtes i canviar la rutina.');

CREATE TABLE usuaris (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(60)  NOT NULL,
  cognoms        VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  rol            ENUM('client','admin') NOT NULL DEFAULT 'client',
  targeta_rfid   VARCHAR(32)  NULL,
  actiu          TINYINT(1)   NOT NULL DEFAULT 1,
  quota_fins     DATE         NULL,
  tarifa_id      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  data_registre  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_usuaris_email UNIQUE (email),
  CONSTRAINT uq_usuaris_rfid  UNIQUE (targeta_rfid),
  CONSTRAINT fk_usuaris_tarifa FOREIGN KEY (tarifa_id)
    REFERENCES tarifes(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activitats (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom                VARCHAR(60)  NOT NULL,
  descripcio         TEXT         NULL,
  durada_min         SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  places_per_defecte SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  activa             TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT uq_activitats_nom UNIQUE (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classes (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activitat_id  INT UNSIGNED NOT NULL,
  inici         DATETIME     NOT NULL,
  places        SMALLINT UNSIGNED NOT NULL,
  cancelada     TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT fk_classes_activitat FOREIGN KEY (activitat_id)
    REFERENCES activitats(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_classes_activitat_inici UNIQUE (activitat_id, inici),
  CONSTRAINT ck_classes_places CHECK (places > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reserves (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id        INT UNSIGNED NOT NULL,
  classe_id        INT UNSIGNED NOT NULL,
  estat            ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
  data_reserva     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_cancelacio  DATETIME     NULL,
  CONSTRAINT fk_reserves_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reserves_classe FOREIGN KEY (classe_id)
    REFERENCES classes(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_reserves_usuari_classe UNIQUE (usuari_id, classe_id),
  INDEX ix_reserves_classe_estat (classe_id, estat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maquines (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codi        VARCHAR(20)  NOT NULL,
  nom         VARCHAR(80)  NOT NULL,
  tipus       VARCHAR(40)  NOT NULL,
  ubicacio    VARCHAR(60)  NULL,
  estat       ENUM('activa','manteniment','baixa') NOT NULL DEFAULT 'activa',
  token_hash  VARCHAR(255) NOT NULL,
  CONSTRAINT uq_maquines_codi UNIQUE (codi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrenaments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id  INT UNSIGNED NOT NULL,
  data       DATE         NOT NULL,
  CONSTRAINT fk_entrenaments_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_entrenaments_usuari_data UNIQUE (usuari_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE series (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entrenament_id  INT UNSIGNED NOT NULL,
  maquina_id      INT UNSIGNED NOT NULL,
  num_serie       TINYINT UNSIGNED NOT NULL,
  carrega_kg      DECIMAL(5,1) NOT NULL,
  inici           DATETIME     NOT NULL,
  CONSTRAINT fk_series_entrenament FOREIGN KEY (entrenament_id)
    REFERENCES entrenaments(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_series_maquina FOREIGN KEY (maquina_id)
    REFERENCES maquines(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_series_num UNIQUE (entrenament_id, maquina_id, num_serie),
  CONSTRAINT ck_series_carrega CHECK (carrega_kg >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE repeticions (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  serie_id          INT UNSIGNED NOT NULL,
  num_rep           TINYINT UNSIGNED NOT NULL,
  velocitat_mitjana DECIMAL(4,2) NOT NULL,
  recorregut_m      DECIMAL(4,2) NOT NULL,
  durada_s          DECIMAL(4,2) NOT NULL,
  CONSTRAINT fk_repeticions_serie FOREIGN KEY (serie_id)
    REFERENCES series(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_repeticions_num UNIQUE (serie_id, num_rep),
  CONSTRAINT ck_repeticions_valors CHECK (velocitat_mitjana > 0 AND recorregut_m > 0 AND durada_s > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificacions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  tipus       ENUM('reserva','cancelacio','classe_cancelada','sistema') NOT NULL,
  missatge    VARCHAR(255) NOT NULL,
  llegida     TINYINT(1)   NOT NULL DEFAULT 0,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notificacions_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX ix_notificacions_usuari_llegida (usuari_id, llegida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE torns (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codi        VARCHAR(20)  NOT NULL,
  nom         VARCHAR(80)  NOT NULL,
  estat       ENUM('activa','baixa') NOT NULL DEFAULT 'activa',
  token_hash  VARCHAR(255) NOT NULL,
  CONSTRAINT uq_torns_codi UNIQUE (codi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrades (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  torn_id     INT UNSIGNED NOT NULL,
  usuari_id   INT UNSIGNED NULL,
  targeta     VARCHAR(32)  NOT NULL,
  data_hora   DATETIME     NOT NULL,
  permesa     TINYINT(1)   NOT NULL,
  motiu       VARCHAR(60)  NOT NULL,
  CONSTRAINT fk_entrades_torn FOREIGN KEY (torn_id)
    REFERENCES torns(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_entrades_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX ix_entrades_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recuperacions (
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

CREATE TABLE intents_login (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(150) NOT NULL,
  ip          VARCHAR(45)  NOT NULL,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_intents_email (email, data),
  INDEX ix_intents_ip (ip, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracio (
  clau        VARCHAR(40)  PRIMARY KEY,
  valor       VARCHAR(255) NOT NULL,
  canviat     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagaments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  tarifa_id   TINYINT UNSIGNED NOT NULL,
  mesos       TINYINT UNSIGNED NOT NULL,
  import      DECIMAL(6,2) NOT NULL,
  fins        DATE         NOT NULL,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cobrat_per  INT UNSIGNED NULL,
  CONSTRAINT fk_pagaments_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_pagaments_tarifa FOREIGN KEY (tarifa_id)
    REFERENCES tarifes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pagaments_cobrat FOREIGN KEY (cobrat_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_pagaments_mesos CHECK (mesos IN (1, 3))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questionaris (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id     INT UNSIGNED NOT NULL,
  respostes     LONGTEXT     NOT NULL,
  consentiment  TINYINT(1)   NOT NULL,
  enviat        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_questionaris_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT ck_questionaris_json CHECK (JSON_VALID(respostes)),
  CONSTRAINT ck_questionaris_consentiment CHECK (consentiment = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  nom_fitxer  VARCHAR(120) NOT NULL,
  pdf         MEDIUMBLOB   NOT NULL,
  pujat       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pujat_per   INT UNSIGNED NULL,
  CONSTRAINT fk_plans_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_plans_pujat FOREIGN KEY (pujat_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rutina (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  dia         TINYINT UNSIGNED NOT NULL,
  ordre       TINYINT UNSIGNED NOT NULL,
  exercici    VARCHAR(80)  NOT NULL,
  series      TINYINT UNSIGNED NOT NULL,
  maquina_id  INT UNSIGNED NULL,
  nota        VARCHAR(120) NULL,
  CONSTRAINT fk_rutina_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rutina_maquina FOREIGN KEY (maquina_id)
    REFERENCES maquines(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_rutina_dia CHECK (dia BETWEEN 1 AND 7),
  CONSTRAINT ck_rutina_series CHECK (series BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registre (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rutina_id   INT UNSIGNED NOT NULL,
  data        DATE         NOT NULL,
  num_serie   TINYINT UNSIGNED NOT NULL,
  carrega_kg  DECIMAL(5,1) NULL,
  reps        TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_registre_rutina FOREIGN KEY (rutina_id)
    REFERENCES rutina(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_registre_serie UNIQUE (rutina_id, data, num_serie),
  CONSTRAINT ck_registre_valors CHECK (reps BETWEEN 1 AND 100 AND (carrega_kg IS NULL OR carrega_kg BETWEEN 0 AND 500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE revisions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  data        DATE         NOT NULL,
  pes_kg      DECIMAL(5,1) NULL,
  com_va      TEXT         NOT NULL,
  resposta    TEXT         NULL,
  respost_per INT UNSIGNED NULL,
  respost     DATETIME     NULL,
  CONSTRAINT fk_revisions_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_revisions_respost FOREIGN KEY (respost_per)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE missatges (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id   INT UNSIGNED NOT NULL,
  autor_id    INT UNSIGNED NULL,
  text        TEXT         NOT NULL,
  data        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  llegit      TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT fk_missatges_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_missatges_autor FOREIGN KEY (autor_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX ix_missatges_usuari (usuari_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE VIEW v_resum_series AS
SELECT s.id                               AS serie_id,
       e.usuari_id,
       e.data,
       m.codi                             AS maquina,
       s.num_serie,
       s.carrega_kg,
       COUNT(r.id)                        AS repeticions,
       ROUND(AVG(r.velocitat_mitjana), 2) AS velocitat_mitjana,
       MAX(r.velocitat_mitjana)           AS velocitat_maxima,
       ROUND(AVG(r.recorregut_m), 2)      AS recorregut_mitja,
       SUM(r.durada_s)                    AS durada_total_s,
       ROUND(100 * (MAX(r.velocitat_mitjana) -
             (SELECT r2.velocitat_mitjana FROM repeticions r2
               WHERE r2.serie_id = s.id ORDER BY r2.num_rep DESC LIMIT 1))
             / MAX(r.velocitat_mitjana), 1) AS perdua_velocitat_pct
FROM series s
JOIN entrenaments e ON e.id = s.entrenament_id
JOIN maquines m     ON m.id = s.maquina_id
JOIN repeticions r  ON r.serie_id = s.id
GROUP BY s.id, e.usuari_id, e.data, m.codi, s.num_serie, s.carrega_kg;
