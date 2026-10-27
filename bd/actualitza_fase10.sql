-- =====================================================================
-- GYM-ZERO · Actualització de la Fase 10 (per a una base de dades que ja existeix)
-- El 1RM es calcula amb la mateixa fórmula (Epley) a totes les màquines,
-- així que la columna maquines.coef_1rm ja no cal.
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho i Continua.
-- No esborra cap altra dada i es pot executar dues vegades.
-- =====================================================================

ALTER TABLE maquines DROP CONSTRAINT IF EXISTS ck_maquines_coef;
ALTER TABLE maquines DROP COLUMN IF EXISTS coef_1rm;

SELECT codi, nom FROM maquines ORDER BY id;
