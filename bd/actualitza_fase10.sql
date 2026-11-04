ALTER TABLE maquines DROP CONSTRAINT IF EXISTS ck_maquines_coef;
ALTER TABLE maquines DROP COLUMN IF EXISTS coef_1rm;

SELECT codi, nom FROM maquines ORDER BY id;
