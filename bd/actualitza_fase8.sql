-- =====================================================================
-- GYM-ZERO · Actualització de la Fase 8 (per a una base de dades que ja existeix)
-- Afegeix a cada màquina el número de la fórmula del 1RM (coef_1rm).
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho i Continua.
-- No esborra cap dada.
-- =====================================================================

ALTER TABLE maquines
  ADD COLUMN IF NOT EXISTS coef_1rm DECIMAL(4,1) NOT NULL DEFAULT 30 AFTER token_hash;

UPDATE maquines SET coef_1rm = 34 WHERE codi = 'PRESS-01';
UPDATE maquines SET coef_1rm = 35 WHERE codi IN ('SQUAT-01', 'REM-01');

SELECT codi, nom, coef_1rm FROM maquines;
