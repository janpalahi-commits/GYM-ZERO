-- =====================================================================
-- GYM-ZERO · Afegir les 17 màquines noves (20 en total, dues per grup muscular)
-- Per a una base de dades que ja existeix. No esborra res i es pot executar dues vegades.
-- phpMyAdmin > gymzero_db > SQL > enganxa-ho i Continua.
-- Clau de prova de cada màquina: token-<CODI> (ex.: token-CURL-01). Només per a proves.
-- =====================================================================
SET NAMES utf8mb4;

UPDATE maquines SET ubicacio = 'Sala de força · tren superior' WHERE codi IN ('PRESS-01', 'REM-01');
UPDATE maquines SET ubicacio = 'Sala de força · cames' WHERE codi = 'SQUAT-01';

INSERT IGNORE INTO maquines (codi, nom, tipus, ubicacio, token_hash) VALUES
 ('CONTR-01', 'Contractora de pit intel·ligent', 'contractora de pit', 'Sala de força · tren superior', '$2y$10$5gEwkwUrNsXMocF6PCTlY.O34i0K2QYMzI4JlOIzVb0MxEeI.Te8G'),
 ('JALO-01', 'Jaló al pit intel·ligent', 'jaló al pit', 'Sala de força · tren superior', '$2y$10$HDEuBgUBQbmuoeH7fdKhf.FqYKDwmL0OdegX22fedYN3Nsm8PBygO'),
 ('MILITAR-01', 'Press militar intel·ligent', 'press militar', 'Sala de força · tren superior', '$2y$10$GHiXQ5H2oAA0TpicBz7.U.MS.L.BibgrW97ks16zbxJ31Tuycuw3u'),
 ('LATERAL-01', 'Elevacions laterals intel·ligent', 'elevacions laterals', 'Sala de força · tren superior', '$2y$10$Wb/eMCEXXAwLTs3YGqzl0uxkvdpbNFvbOm2EN9CQAfPLTh8UaYnPK'),
 ('CURL-01', 'Curl de bíceps intel·ligent', 'curl de bíceps', 'Sala de força · tren superior', '$2y$10$4I90ZRBfqMEjuJetiWNCl.vdj0NQrdEEdSoXucjCtLraFctz7a45m'),
 ('CURLP-01', 'Curl de bíceps en politja intel·ligent', 'curl de bíceps en politja', 'Sala de força · tren superior', '$2y$10$gvH5C0gjKGCJazHlWJSo7.OsUrvWZvCHVDb3AzgduVodfs3f0UmAK'),
 ('TRICEP-01', 'Extensió de tríceps intel·ligent', 'extensió de tríceps', 'Sala de força · tren superior', '$2y$10$IUfW48f4.M67IXoqww9QIOHSe9GEaA.IT.EZ8Gg446LH/vlH9WUHu'),
 ('FONS-01', 'Fons en màquina intel·ligent', 'fons', 'Sala de força · tren superior', '$2y$10$TiFN5AK3s6zOEVxtwLW4CuftSd/Hv6mdtW62HlXdmuBG6SKfolyRS'),
 ('EXTQ-01', 'Extensió de quàdriceps intel·ligent', 'extensió de quàdriceps', 'Sala de força · cames', '$2y$10$kJWfUd1ZeuoEJwduxRDH2.UMvyz8cv4y5i8MhH1R0.lnUol9xi7bO'),
 ('FEMS-01', 'Curl femoral assegut intel·ligent', 'curl femoral assegut', 'Sala de força · cames', '$2y$10$BSCumrNm01YKwsfacYCOE./Y4iTa3/ox/CsDgcD7JOVXNl7RIXLs.'),
 ('FEME-01', 'Curl femoral estirat intel·ligent', 'curl femoral estirat', 'Sala de força · cames', '$2y$10$T9uA2xOnySRid7SCAUoU..3L6ICwpCHKMgt47bHZpEEjodihK4PKG'),
 ('HIP-01', 'Hip thrust intel·ligent', 'hip thrust', 'Sala de força · cames', '$2y$10$sDgJbEF3aGcXDPD2dINN1O28dlQg5cCnpeZRaCtzpLgf11RIz59lu'),
 ('ABDUC-01', 'Abductora intel·ligent', 'abductora', 'Sala de força · cames', '$2y$10$QmyWFOs4PzjnXaSXaxSclueacaNXFlc2v0uaIouHHIe7bxrm2qZcO'),
 ('TALOD-01', 'Elevació de talons dempeus intel·ligent', 'elevació de talons dempeus', 'Sala de força · cames', '$2y$10$qtUPRzYF/SGSNaOr2sq9GO2Wx8tGPez7LB5/Trk10lVuh.qGTirMK'),
 ('TALOA-01', 'Elevació de talons asseguda intel·ligent', 'elevació de talons asseguda', 'Sala de força · cames', '$2y$10$WnTrKQdO56I28m1v8iIKgukxQ9UamFb7qIMjee5w14Oe0eO6UUovq'),
 ('CRUNCH-01', 'Crunch en màquina intel·ligent', 'crunch', 'Sala de força · cames', '$2y$10$idmlWkdiHrPLnwRZzawol.j5NuRRwAo2qzT.gE1cOJ/t.tf8Zza1K'),
 ('ROTA-01', 'Rotació de tronc intel·ligent', 'rotació de tronc', 'Sala de força · cames', '$2y$10$g9SQl1jNhcr85M0uD9o/t.3qt7u/NaYgI7ekDDCXRbrPOBny3xNFC');

SELECT codi, nom, ubicacio FROM maquines ORDER BY id;
