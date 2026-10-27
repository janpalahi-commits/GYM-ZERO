-- =====================================================================
-- GYM-ZERO · Actualització per al control d'accés (Fase 9)
-- Per a una base de dades que ja té les taules de les fases anteriors.
-- No esborra res. Es pot executar més d'una vegada.
--
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho tot > Continuar
-- =====================================================================
SET NAMES utf8mb4;

-- Fins quin dia té pagada la quota cada client
ALTER TABLE usuaris ADD COLUMN IF NOT EXISTS quota_fins DATE NULL AFTER actiu;

CREATE TABLE IF NOT EXISTS torns (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codi        VARCHAR(20)  NOT NULL,
  nom         VARCHAR(80)  NOT NULL,
  estat       ENUM('activa','baixa') NOT NULL DEFAULT 'activa',
  token_hash  VARCHAR(255) NOT NULL,
  CONSTRAINT uq_torns_codi UNIQUE (codi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entrades (
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

-- NOMÉS PER A PROVES A XAMPP (no ho facis al servidor de gymzero.cat):
-- el torn amb la clau de prova token-TORN-01, i la quota d'aquest mes
-- pagada a tots els clients que encara no en tenen, perquè puguin entrar.
INSERT IGNORE INTO torns (codi, nom, token_hash) VALUES
 ('TORN-01', 'Torn d''entrada a les sales', '$2y$10$VWAHG8kjSXJERqXJqkTLV.SOzmc.U91e/tdl0W1mUQaEDRLnd64f.');
UPDATE usuaris SET quota_fins = LAST_DAY(CURDATE()) WHERE rol = 'client' AND quota_fins IS NULL;
