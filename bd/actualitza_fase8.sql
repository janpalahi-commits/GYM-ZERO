ALTER TABLE maquines
  ADD COLUMN IF NOT EXISTS coef_1rm DECIMAL(4,1) NOT NULL DEFAULT 30 AFTER token_hash;

UPDATE maquines SET coef_1rm = 34 WHERE codi = 'PRESS-01';
UPDATE maquines SET coef_1rm = 35 WHERE codi IN ('SQUAT-01', 'REM-01');

SELECT codi, nom, coef_1rm FROM maquines;
