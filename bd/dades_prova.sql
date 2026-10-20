-- =====================================================================
-- GYM-ZERO · Dades de prova (Fase 2)
-- Importa-ho DESPRÉS de gymzero_bd.sql. Només per a proves:
-- contrasenyes de prova -> admin: Admin1234 · clients: Client1234
-- Les dates són relatives al dia que s'importa (CURDATE()).
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO usuaris (id, nom, cognoms, email, password_hash, rol, targeta_rfid) VALUES
 (1, 'Admin', 'GYM-ZERO', 'admin@gymzero.cat', '$2y$10$eRJUxortN4WJSfhhfLdcHuU75awd6ySVm8c1C6HF42c8V0D1dv5Qa', 'admin', NULL),
 (2, 'Marc', 'Puig Serra', 'marc@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04A1B2C3'),
 (3, 'Laia', 'Vidal Roca', 'laia@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04D4E5F6'),
 (4, 'Pau', 'Costa Mas', 'pau@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04078899');

INSERT INTO activitats (id, nom, descripcio, durada_min, places_per_defecte) VALUES
 (1, 'Musculació', 'Entrenament de força guiat a la sala de màquines.', 60, 15),
 (2, 'Cross Training', 'Circuit d''alta intensitat en grup.', 45, 12),
 (3, 'Powerlifting', 'Tècnica de sentadilla, press de banca i pes mort.', 90, 8),
 (4, 'Hipertrofia', 'Rutina orientada a guanyar massa muscular.', 60, 15),
 (5, 'Mobilitat', 'Sessió de mobilitat i estiraments.', 45, 2);

-- Classes dels propers dies (la de Mobilitat té només 2 places per provar el límit)
INSERT INTO classes (id, activitat_id, inici, places) VALUES
 (1, 1, CURDATE() + INTERVAL 1 DAY + INTERVAL 18 HOUR, 15),
 (2, 2, CURDATE() + INTERVAL 1 DAY + INTERVAL 19 HOUR, 12),
 (3, 3, CURDATE() + INTERVAL 2 DAY + INTERVAL 18 HOUR, 8),
 (4, 4, CURDATE() + INTERVAL 2 DAY + INTERVAL 19 HOUR, 15),
 (5, 5, CURDATE() + INTERVAL 3 DAY + INTERVAL 10 HOUR, 2);

INSERT INTO reserves (usuari_id, classe_id) VALUES
 (2, 1), (3, 1), (2, 3), (4, 5);

-- Tokens de prova de les màquines: token-PRESS-01, token-SQUAT-01, token-REM-01
-- coef_1rm: número de la fórmula del 1RM per a cada exercici (vegeu disseny-bd.md)
INSERT INTO maquines (id, codi, nom, tipus, ubicacio, token_hash, coef_1rm) VALUES
 (1, 'PRESS-01', 'Press de banca intel·ligent', 'press de banca', 'Sala de força', '$2y$10$sF5gXHi2ts6Z8f4jUe9kEuSITWYPg4po5ez/cO/LPnOY155gp08n6', 34),
 (2, 'SQUAT-01', 'Rack de sentadilla intel·ligent', 'sentadilla', 'Sala de força', '$2y$10$Zib2GmLU20H7qVzRSFsp7.WIRZZq2VYDHcJDal/1/ONpyeYc5zDYO', 35),
 (3, 'REM-01', 'Rem assegut intel·ligent', 'rem', 'Sala de màquines', '$2y$10$rEMI8etkhnKc8gCR9CxNK.GbsFDvPX9qNjeAEHsyBkswZI2k.u/MG', 35);

-- Dos entrenaments del Marc (fa 7 dies i fa 2 dies), dades com les enviaria el simulador
INSERT INTO entrenaments (id, usuari_id, data) VALUES
 (1, 2, CURDATE() - INTERVAL 7 DAY),
 (2, 2, CURDATE() - INTERVAL 2 DAY);

INSERT INTO series (id, entrenament_id, maquina_id, num_serie, carrega_kg, inici) VALUES
 (1, 1, 1, 1, 60, CURDATE() - INTERVAL 7 DAY + INTERVAL '18:04' HOUR_MINUTE),
 (2, 1, 1, 2, 62.5, CURDATE() - INTERVAL 7 DAY + INTERVAL '18:08' HOUR_MINUTE),
 (3, 1, 1, 3, 62.5, CURDATE() - INTERVAL 7 DAY + INTERVAL '18:12' HOUR_MINUTE),
 (4, 1, 2, 1, 80, CURDATE() - INTERVAL 7 DAY + INTERVAL '18:16' HOUR_MINUTE),
 (5, 1, 2, 2, 80, CURDATE() - INTERVAL 7 DAY + INTERVAL '18:20' HOUR_MINUTE),
 (6, 2, 1, 1, 62.5, CURDATE() - INTERVAL 2 DAY + INTERVAL '18:04' HOUR_MINUTE),
 (7, 2, 1, 2, 65, CURDATE() - INTERVAL 2 DAY + INTERVAL '18:08' HOUR_MINUTE),
 (8, 2, 1, 3, 65, CURDATE() - INTERVAL 2 DAY + INTERVAL '18:12' HOUR_MINUTE),
 (9, 2, 2, 1, 85, CURDATE() - INTERVAL 2 DAY + INTERVAL '18:16' HOUR_MINUTE),
 (10, 2, 2, 2, 85, CURDATE() - INTERVAL 2 DAY + INTERVAL '18:20' HOUR_MINUTE);

INSERT INTO repeticions (serie_id, num_rep, velocitat_mitjana, recorregut_m, durada_s) VALUES
 (1, 1, 0.77, 0.41, 0.52),
 (1, 2, 0.76, 0.40, 0.53),
 (1, 3, 0.73, 0.41, 0.57),
 (1, 4, 0.69, 0.42, 0.61),
 (1, 5, 0.67, 0.42, 0.63),
 (1, 6, 0.64, 0.40, 0.63),
 (1, 7, 0.63, 0.43, 0.69),
 (1, 8, 0.59, 0.41, 0.69),
 (1, 9, 0.58, 0.44, 0.75),
 (1, 10, 0.56, 0.42, 0.75),
 (2, 1, 0.73, 0.40, 0.55),
 (2, 2, 0.71, 0.41, 0.58),
 (2, 3, 0.66, 0.40, 0.61),
 (2, 4, 0.65, 0.43, 0.67),
 (2, 5, 0.62, 0.42, 0.68),
 (2, 6, 0.61, 0.41, 0.68),
 (2, 7, 0.58, 0.40, 0.69),
 (2, 8, 0.55, 0.41, 0.75),
 (2, 9, 0.54, 0.42, 0.77),
 (3, 1, 0.69, 0.42, 0.61),
 (3, 2, 0.68, 0.41, 0.61),
 (3, 3, 0.66, 0.43, 0.64),
 (3, 4, 0.63, 0.42, 0.68),
 (3, 5, 0.61, 0.44, 0.71),
 (3, 6, 0.59, 0.41, 0.69),
 (3, 7, 0.58, 0.40, 0.70),
 (3, 8, 0.54, 0.43, 0.80),
 (4, 1, 0.65, 0.55, 0.85),
 (4, 2, 0.63, 0.56, 0.89),
 (4, 3, 0.63, 0.55, 0.88),
 (4, 4, 0.61, 0.54, 0.89),
 (4, 5, 0.58, 0.55, 0.95),
 (4, 6, 0.56, 0.55, 0.98),
 (4, 7, 0.54, 0.57, 1.04),
 (4, 8, 0.51, 0.56, 1.09),
 (5, 1, 0.61, 0.56, 0.92),
 (5, 2, 0.60, 0.57, 0.94),
 (5, 3, 0.59, 0.54, 0.92),
 (5, 4, 0.56, 0.56, 1.00),
 (5, 5, 0.53, 0.55, 1.04),
 (5, 6, 0.51, 0.53, 1.05),
 (5, 7, 0.49, 0.56, 1.15),
 (5, 8, 0.47, 0.54, 1.15),
 (6, 1, 0.80, 0.43, 0.55),
 (6, 2, 0.76, 0.42, 0.55),
 (6, 3, 0.75, 0.44, 0.58),
 (6, 4, 0.73, 0.43, 0.59),
 (6, 5, 0.69, 0.42, 0.60),
 (6, 6, 0.67, 0.44, 0.65),
 (6, 7, 0.66, 0.41, 0.62),
 (6, 8, 0.61, 0.41, 0.67),
 (6, 9, 0.59, 0.42, 0.71),
 (6, 10, 0.57, 0.41, 0.72),
 (7, 1, 0.73, 0.42, 0.57),
 (7, 2, 0.71, 0.42, 0.59),
 (7, 3, 0.71, 0.43, 0.61),
 (7, 4, 0.67, 0.42, 0.63),
 (7, 5, 0.65, 0.40, 0.62),
 (7, 6, 0.63, 0.43, 0.68),
 (7, 7, 0.61, 0.43, 0.71),
 (7, 8, 0.57, 0.42, 0.73),
 (7, 9, 0.54, 0.43, 0.79),
 (8, 1, 0.70, 0.40, 0.58),
 (8, 2, 0.68, 0.41, 0.60),
 (8, 3, 0.66, 0.40, 0.61),
 (8, 4, 0.63, 0.41, 0.65),
 (8, 5, 0.61, 0.41, 0.68),
 (8, 6, 0.58, 0.43, 0.75),
 (8, 7, 0.58, 0.41, 0.70),
 (8, 8, 0.54, 0.41, 0.76),
 (9, 1, 0.67, 0.53, 0.80),
 (9, 2, 0.66, 0.57, 0.86),
 (9, 3, 0.63, 0.55, 0.88),
 (9, 4, 0.59, 0.53, 0.90),
 (9, 5, 0.58, 0.54, 0.93),
 (9, 6, 0.57, 0.54, 0.94),
 (9, 7, 0.53, 0.57, 1.08),
 (9, 8, 0.52, 0.54, 1.03),
 (10, 1, 0.63, 0.53, 0.84),
 (10, 2, 0.61, 0.57, 0.93),
 (10, 3, 0.60, 0.56, 0.93),
 (10, 4, 0.56, 0.54, 0.97),
 (10, 5, 0.54, 0.56, 1.04),
 (10, 6, 0.53, 0.56, 1.06),
 (10, 7, 0.50, 0.54, 1.07);

INSERT INTO notificacions (usuari_id, tipus, missatge) VALUES
 (2, 'reserva', 'Reserva confirmada: Musculació, demà a les 18:00.'),
 (4, 'reserva', 'Reserva confirmada: Mobilitat, d''aquí a 3 dies a les 10:00.');
