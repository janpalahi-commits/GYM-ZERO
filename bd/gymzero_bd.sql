-- =====================================================================
-- GYM-ZERO · Base de dades (Fase 2)
-- MariaDB 10.11 / 11.x · motor InnoDB · utf8mb4
--
-- Com importar-ho:
--   phpMyAdmin > selecciona la base de dades > Importar > aquest fitxer.
--
-- ATENCIÓ: el principi del fitxer esborra les taules si ja existeixen
-- (incloent l'antiga taula `reserves`). Prova-ho primer a XAMPP.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_resum_series;
DROP TABLE IF EXISTS entrades, torns, notificacions, repeticions, series, entrenaments,
                     maquines, reserves, classes, activitats, usuaris;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- USUARIS: clients i administradors del gimnàs
-- ---------------------------------------------------------------------
CREATE TABLE usuaris (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(60)  NOT NULL,
  cognoms        VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,          -- password_hash() de PHP, mai la contrasenya
  rol            ENUM('client','admin') NOT NULL DEFAULT 'client',
  targeta_rfid   VARCHAR(32)  NULL,              -- UID del xip de la polsera, per passar el torn i fer servir les màquines
  actiu          TINYINT(1)   NOT NULL DEFAULT 1,
  quota_fins     DATE         NULL,              -- últim dia amb la quota pagada (NULL: no l'ha pagada mai)
  data_registre  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_usuaris_email UNIQUE (email),
  CONSTRAINT uq_usuaris_rfid  UNIQUE (targeta_rfid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ACTIVITATS: el tipus d'activitat (Musculació, Cross Training...)
-- ---------------------------------------------------------------------
CREATE TABLE activitats (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom                VARCHAR(60)  NOT NULL,
  descripcio         TEXT         NULL,
  durada_min         SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  places_per_defecte SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  activa             TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT uq_activitats_nom UNIQUE (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CLASSES: una activitat concreta en un dia i hora, amb places limitades
-- (és el que el client reserva)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- RESERVES: un usuari reserva una plaça en una classe
--  · uq_reserves_usuari_classe impedeix reserves duplicades
--  · el límit de places es controla amb una transacció (vegeu disseny-bd.md)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- MAQUINES: màquines intel·ligents (dissenyades; les dades són simulades)
-- ---------------------------------------------------------------------
CREATE TABLE maquines (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codi        VARCHAR(20)  NOT NULL,             -- ex.: PRESS-01
  nom         VARCHAR(80)  NOT NULL,
  tipus       VARCHAR(40)  NOT NULL,             -- ex.: press de banca, premsa de cames
  ubicacio    VARCHAR(60)  NULL,
  estat       ENUM('activa','manteniment','baixa') NOT NULL DEFAULT 'activa',
  token_hash  VARCHAR(255) NOT NULL,             -- clau de l'API de la màquina, guardada amb hash
  CONSTRAINT uq_maquines_codi UNIQUE (codi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ENTRENAMENTS: el dia que un usuari entrena (agrupa les seves sèries)
-- ---------------------------------------------------------------------
CREATE TABLE entrenaments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuari_id  INT UNSIGNED NOT NULL,
  data       DATE         NOT NULL,
  CONSTRAINT fk_entrenaments_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_entrenaments_usuari_data UNIQUE (usuari_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SERIES: una sèrie feta en una màquina dins d'un entrenament
-- ---------------------------------------------------------------------
CREATE TABLE series (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entrenament_id  INT UNSIGNED NOT NULL,
  maquina_id      INT UNSIGNED NOT NULL,
  num_serie       TINYINT UNSIGNED NOT NULL,     -- 1a, 2a, 3a sèrie a aquesta màquina aquest dia
  carrega_kg      DECIMAL(5,1) NOT NULL,
  inici           DATETIME     NOT NULL,
  CONSTRAINT fk_series_entrenament FOREIGN KEY (entrenament_id)
    REFERENCES entrenaments(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_series_maquina FOREIGN KEY (maquina_id)
    REFERENCES maquines(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_series_num UNIQUE (entrenament_id, maquina_id, num_serie),
  CONSTRAINT ck_series_carrega CHECK (carrega_kg >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REPETICIONS: cada repetició d'una sèrie, tal com l'envia la màquina
-- (permet calcular la pèrdua de velocitat dins de la sèrie)
-- ---------------------------------------------------------------------
CREATE TABLE repeticions (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  serie_id          INT UNSIGNED NOT NULL,
  num_rep           TINYINT UNSIGNED NOT NULL,
  velocitat_mitjana DECIMAL(4,2) NOT NULL,       -- m/s
  recorregut_m      DECIMAL(4,2) NOT NULL,       -- ROM, en metres
  durada_s          DECIMAL(4,2) NOT NULL,       -- segons
  CONSTRAINT fk_repeticions_serie FOREIGN KEY (serie_id)
    REFERENCES series(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_repeticions_num UNIQUE (serie_id, num_rep),
  CONSTRAINT ck_repeticions_valors CHECK (velocitat_mitjana > 0 AND recorregut_m > 0 AND durada_s > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- NOTIFICACIONS: avisos per a l'usuari (reserva confirmada, classe cancel·lada...)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- TORNS: torns d'accés amb lector de polsera (dissenyats; les dades són simulades)
-- ---------------------------------------------------------------------
CREATE TABLE torns (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codi        VARCHAR(20)  NOT NULL,             -- ex.: TORN-01
  nom         VARCHAR(80)  NOT NULL,
  estat       ENUM('activa','baixa') NOT NULL DEFAULT 'activa',
  token_hash  VARCHAR(255) NOT NULL,             -- clau de l'API del lector, guardada amb hash
  CONSTRAINT uq_torns_codi UNIQUE (codi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ENTRADES: cada vegada que algú acosta la polsera a un torn,
-- tant si el deixa passar com si no (així es veu qui ho ha intentat i per què no ha pogut)
-- ---------------------------------------------------------------------
CREATE TABLE entrades (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  torn_id     INT UNSIGNED NOT NULL,
  usuari_id   INT UNSIGNED NULL,                 -- NULL si la polsera no és de ningú
  targeta     VARCHAR(32)  NOT NULL,             -- codi llegit, encara que no sigui de ningú
  data_hora   DATETIME     NOT NULL,
  permesa     TINYINT(1)   NOT NULL,
  motiu       VARCHAR(60)  NOT NULL,             -- Quota pagada, Quota sense pagar, Polsera desconeguda...
  CONSTRAINT fk_entrades_torn FOREIGN KEY (torn_id)
    REFERENCES torns(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_entrades_usuari FOREIGN KEY (usuari_id)
    REFERENCES usuaris(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX ix_entrades_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- VISTA: resum de cada sèrie (per a l'historial i l'anàlisi)
--  perdua_velocitat_pct = quant ha baixat la velocitat de la
--  repetició més ràpida a l'última repetició de la sèrie
-- ---------------------------------------------------------------------
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
