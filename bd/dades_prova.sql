-- =====================================================================
-- GYM-ZERO · Dades de prova (Fase 2)
-- Importa-ho DESPRÉS de gymzero_bd.sql. Només per a proves:
-- contrasenyes de prova -> admin: Admin1234 · clients: Client1234
-- Les dates són relatives al dia que s'importa (CURDATE()).
-- =====================================================================
SET NAMES utf8mb4;

-- Quota: el Marc la té pagada fins a final de mes, la Laia la té caducada i al Pau li caduca d'aquí a 5 dies
INSERT INTO usuaris (id, nom, cognoms, email, password_hash, rol, targeta_rfid, quota_fins) VALUES
 (1, 'Admin', 'GYM-ZERO', 'admin@gymzero.cat', '$2y$10$eRJUxortN4WJSfhhfLdcHuU75awd6ySVm8c1C6HF42c8V0D1dv5Qa', 'admin', 'AD000001', NULL),
 (2, 'Marc', 'Puig Serra', 'marc@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04A1B2C3', LAST_DAY(CURDATE())),
 (3, 'Laia', 'Vidal Roca', 'laia@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04D4E5F6', CURDATE() - INTERVAL 3 DAY),
 (4, 'Pau', 'Costa Mas', 'pau@example.com', '$2y$10$FTfrEng3/G2qWY5JCEqwC./Y/4ftFp2Hk.ISNgGZe6cQFeykluye2', 'client', '04078899', CURDATE() + INTERVAL 5 DAY);

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

-- 20 màquines, dues per grup muscular (vegeu xarxa/disseny-xarxa.md)
-- Clau de prova de cada màquina: token-<CODI> (ex.: token-PRESS-01, token-CURL-01)
INSERT INTO maquines (id, codi, nom, tipus, ubicacio, token_hash) VALUES
 (1, 'PRESS-01', 'Press de banca intel·ligent', 'press de banca', 'Sala de força · tren superior', '$2y$10$sF5gXHi2ts6Z8f4jUe9kEuSITWYPg4po5ez/cO/LPnOY155gp08n6'),
 (2, 'SQUAT-01', 'Rack de sentadilla intel·ligent', 'sentadilla', 'Sala de força · cames', '$2y$10$Zib2GmLU20H7qVzRSFsp7.WIRZZq2VYDHcJDal/1/ONpyeYc5zDYO'),
 (3, 'REM-01', 'Rem assegut intel·ligent', 'rem', 'Sala de força · tren superior', '$2y$10$rEMI8etkhnKc8gCR9CxNK.GbsFDvPX9qNjeAEHsyBkswZI2k.u/MG'),
 (4, 'CONTR-01', 'Contractora de pit intel·ligent', 'contractora de pit', 'Sala de força · tren superior', '$2y$10$5gEwkwUrNsXMocF6PCTlY.O34i0K2QYMzI4JlOIzVb0MxEeI.Te8G'),
 (5, 'JALO-01', 'Jaló al pit intel·ligent', 'jaló al pit', 'Sala de força · tren superior', '$2y$10$HDEuBgUBQbmuoeH7fdKhf.FqYKDwmL0OdegX22fedYN3Nsm8PBygO'),
 (6, 'MILITAR-01', 'Press militar intel·ligent', 'press militar', 'Sala de força · tren superior', '$2y$10$GHiXQ5H2oAA0TpicBz7.U.MS.L.BibgrW97ks16zbxJ31Tuycuw3u'),
 (7, 'LATERAL-01', 'Elevacions laterals intel·ligent', 'elevacions laterals', 'Sala de força · tren superior', '$2y$10$Wb/eMCEXXAwLTs3YGqzl0uxkvdpbNFvbOm2EN9CQAfPLTh8UaYnPK'),
 (8, 'CURL-01', 'Curl de bíceps intel·ligent', 'curl de bíceps', 'Sala de força · tren superior', '$2y$10$4I90ZRBfqMEjuJetiWNCl.vdj0NQrdEEdSoXucjCtLraFctz7a45m'),
 (9, 'CURLP-01', 'Curl de bíceps en politja intel·ligent', 'curl de bíceps en politja', 'Sala de força · tren superior', '$2y$10$gvH5C0gjKGCJazHlWJSo7.OsUrvWZvCHVDb3AzgduVodfs3f0UmAK'),
 (10, 'TRICEP-01', 'Extensió de tríceps intel·ligent', 'extensió de tríceps', 'Sala de força · tren superior', '$2y$10$IUfW48f4.M67IXoqww9QIOHSe9GEaA.IT.EZ8Gg446LH/vlH9WUHu'),
 (11, 'FONS-01', 'Fons en màquina intel·ligent', 'fons', 'Sala de força · tren superior', '$2y$10$TiFN5AK3s6zOEVxtwLW4CuftSd/Hv6mdtW62HlXdmuBG6SKfolyRS'),
 (12, 'EXTQ-01', 'Extensió de quàdriceps intel·ligent', 'extensió de quàdriceps', 'Sala de força · cames', '$2y$10$kJWfUd1ZeuoEJwduxRDH2.UMvyz8cv4y5i8MhH1R0.lnUol9xi7bO'),
 (13, 'FEMS-01', 'Curl femoral assegut intel·ligent', 'curl femoral assegut', 'Sala de força · cames', '$2y$10$BSCumrNm01YKwsfacYCOE./Y4iTa3/ox/CsDgcD7JOVXNl7RIXLs.'),
 (14, 'FEME-01', 'Curl femoral estirat intel·ligent', 'curl femoral estirat', 'Sala de força · cames', '$2y$10$T9uA2xOnySRid7SCAUoU..3L6ICwpCHKMgt47bHZpEEjodihK4PKG'),
 (15, 'HIP-01', 'Hip thrust intel·ligent', 'hip thrust', 'Sala de força · cames', '$2y$10$sDgJbEF3aGcXDPD2dINN1O28dlQg5cCnpeZRaCtzpLgf11RIz59lu'),
 (16, 'ABDUC-01', 'Abductora intel·ligent', 'abductora', 'Sala de força · cames', '$2y$10$QmyWFOs4PzjnXaSXaxSclueacaNXFlc2v0uaIouHHIe7bxrm2qZcO'),
 (17, 'TALOD-01', 'Elevació de talons dempeus intel·ligent', 'elevació de talons dempeus', 'Sala de força · cames', '$2y$10$qtUPRzYF/SGSNaOr2sq9GO2Wx8tGPez7LB5/Trk10lVuh.qGTirMK'),
 (18, 'TALOA-01', 'Elevació de talons asseguda intel·ligent', 'elevació de talons asseguda', 'Sala de força · cames', '$2y$10$WnTrKQdO56I28m1v8iIKgukxQ9UamFb7qIMjee5w14Oe0eO6UUovq'),
 (19, 'CRUNCH-01', 'Crunch en màquina intel·ligent', 'crunch', 'Sala de força · cames', '$2y$10$idmlWkdiHrPLnwRZzawol.j5NuRRwAo2qzT.gE1cOJ/t.tf8Zza1K'),
 (20, 'ROTA-01', 'Rotació de tronc intel·ligent', 'rotació de tronc', 'Sala de força · cames', '$2y$10$g9SQl1jNhcr85M0uD9o/t.3qt7u/NaYgI7ekDDCXRbrPOBny3xNFC');

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

-- El torn d'entrada a les sales. Clau de prova: token-TORN-01
INSERT INTO torns (id, codi, nom, token_hash) VALUES
 (1, 'TORN-01', 'Torn d''entrada a les sales', '$2y$10$VWAHG8kjSXJERqXJqkTLV.SOzmc.U91e/tdl0W1mUQaEDRLnd64f.');

-- Entrades dels dies que el Marc va entrenar, i un intent de la Laia (quota caducada)
INSERT INTO entrades (torn_id, usuari_id, targeta, data_hora, permesa, motiu) VALUES
 (1, 2, '04A1B2C3', CURDATE() - INTERVAL 7 DAY + INTERVAL '17:58' HOUR_MINUTE, 1, 'Quota pagada'),
 (1, 2, '04A1B2C3', CURDATE() - INTERVAL 2 DAY + INTERVAL '17:57' HOUR_MINUTE, 1, 'Quota pagada'),
 (1, 3, '04D4E5F6', CURDATE() - INTERVAL 1 DAY + INTERVAL '19:12' HOUR_MINUTE, 0, 'Quota sense pagar');

INSERT INTO notificacions (usuari_id, tipus, missatge) VALUES
 (2, 'reserva', 'Reserva confirmada: Musculació, demà a les 18:00.'),
 (4, 'reserva', 'Reserva confirmada: Mobilitat, d''aquí a 3 dies a les 10:00.');
