-- =====================================================================
-- GYM-ZERO · Dades de prova de la fase 12 (tarifes, pla, llibreta i seguiment)
-- NOMÉS PER A L'ORDINADOR DE PROVES. No s'ha d'executar mai a gymzero.cat.
-- Cal haver carregat abans dades_prova.sql i actualitza_fase12.sql.
-- Les persones són inventades. Es pot tornar a executar: primer esborra el que va crear.
-- Les dates són relatives a avui perquè la llibreta sempre tingui les últimes 8 setmanes plenes.
-- =====================================================================

SET NAMES utf8mb4;

SET @admin = (SELECT id FROM usuaris WHERE rol = 'admin' ORDER BY id LIMIT 1);   -- el primer administrador
SET @marc  = (SELECT id FROM usuaris WHERE email = 'marc@example.com');
SET @laia  = (SELECT id FROM usuaris WHERE email = 'laia@example.com');
SET @pau   = (SELECT id FROM usuaris WHERE email = 'pau@example.com');
SET @dilluns = CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY;   -- dilluns d'aquesta setmana

DELETE FROM pagaments   WHERE usuari_id IN (@marc, @laia, @pau);
DELETE FROM questionaris WHERE usuari_id IN (@marc, @laia, @pau);
DELETE FROM plans       WHERE usuari_id IN (@marc, @laia, @pau);
DELETE FROM rutina      WHERE usuari_id IN (@marc, @laia, @pau);   -- el registre s'esborra en cascada
DELETE FROM revisions   WHERE usuari_id IN (@marc, @laia, @pau);
DELETE FROM missatges   WHERE usuari_id IN (@marc, @laia, @pau);

-- Tarifes: el Marc té la de seguiment, el Pau la del pla i la Laia la bàsica (i caducada)
UPDATE usuaris SET tarifa_id = 3 WHERE id = @marc;
UPDATE usuaris SET tarifa_id = 2 WHERE id = @pau;
UPDATE usuaris SET tarifa_id = 1 WHERE id = @laia;

INSERT INTO pagaments (usuari_id, tarifa_id, mesos, import, fins, data, cobrat_per)
SELECT id, tarifa_id, 1, CASE tarifa_id WHEN 3 THEN 110 WHEN 2 THEN 80 ELSE 55 END,
       quota_fins, quota_fins - INTERVAL 1 MONTH + INTERVAL 10 HOUR, @admin
  FROM usuaris WHERE id IN (@marc, @laia, @pau);

-- Qüestionaris: el del Marc ja té pla fet; el del Pau és nou i l'entrenador encara l'ha de fer.
-- Les respostes són com una fila de la fulla de Google del formulari d'APP GYM (títols en castellà).
INSERT INTO questionaris (usuari_id, respostes, consentiment, enviat) VALUES
 (@marc, '{"Idioma del formulario": "Català", "¿En qué idioma quieres tu plan?": "Català", "Fecha de nacimiento": "14/03/2002", "Altura (cm)": 178, "Peso actual (kg)": 76.5, "¿Cómo ha cambiado tu peso en los últimos 6 meses?": "Estable", "¿Tienes báscula para pesarte en casa?": "Sí", "¿Cómo te ves ahora mismo?": "Normal, con algo de grasa en la tripa", "Sexo": "Hombre", "¿Estás embarazada, has dado a luz en el último año o das el pecho?": "", "¿Cómo es tu ciclo menstrual?": "", "¿Algún médico te ha dicho que tienes un problema de corazón o tensión alta?": "No", "¿Sientes dolor en el pecho, mareos o pierdes el conocimiento al hacer esfuerzo?": "No", "¿Tienes alguna enfermedad diagnosticada?": ["Ninguna"], "¿Tomas alguna medicación de forma habitual? ¿Cuál?": "Cap", "¿Tienes alguna alergia o intolerancia alimentaria?": ["Ninguna"], "¿Te has lesionado u operado alguna vez, aunque ya estés recuperado? ¿Qué te pasó?": "", "¿Tienes ahora mismo alguna lesión, dolor o molestia?": "No", "¿Dónde?": [], "Cuéntame qué te pasa y desde cuándo": "", "¿Qué ejercicios o movimientos te duelen?": "", "¿Te está tratando un médico o fisioterapeuta?": "", "¿Cuál es tu objetivo principal?": "Ganar músculo", "¿Qué zonas quieres mejorar más?": ["Pecho", "Brazos"], "¿Tienes una fecha o evento en mente? ¿Cuál y cuándo?": "", "¿Qué te ha hecho dejarlo o no avanzar otras veces?": ["No saber qué hacer"], "¿Cuánto compromiso puedes poner ahora mismo?": 8, "¿Por qué quieres empezar ahora?": "Vull fer-ho bé d''una vegada.", "¿A qué ritmo quieres avanzar?": "Normal: el equilibrio (recomendado)", "¿Cuánto tiempo llevas entrenando con pesas de forma constante?": "Entre 6 meses y 2 años", "¿Estás entrenando ahora?": "Sí", "¿Qué rutina hacías? (días por semana y cómo la repartías)": "3 dies, cos sencer, d''un vídeo de YouTube", "¿Qué ejercicios sabes hacer con buena técnica?": ["Sentadilla", "Press banca", "Remo con barra"], "Si los haces: ¿cuánto peso mueves y a cuántas repeticiones?": "Press banca 60 kg x 8, esquat 70 kg x 8", "¿Sabes lo que es el RIR (repeticiones en recámara)?": "Me suena", "¿Cuántos días a la semana puedes entrenar de verdad?": "3", "¿Qué días podrías ir? (marca todos los posibles)": ["Lunes", "Miércoles", "Viernes"], "¿Cuánto tiempo tienes por sesión?": "60-90 min", "¿A qué hora sueles empezar a entrenar?": "18:00 (tarde)", "Si entrenas a horas distintas según el día, explícalo": "", "¿Qué ejercicios te gustan mucho?": ["Press banca", "Ejercicios de brazos"], "¿Qué ejercicios odias o no quieres hacer?": ["Ninguno"], "¿Quieres incluir cardio?": "No", "Si haces cardio, ¿cuál prefieres?": [], "Aparte del gimnasio, ¿practicas algún deporte o actividad física?": "No", "¿Qué deporte o actividad?": "", "¿Qué días lo practicas?": [], "¿A qué hora empiezas normalmente?": "", "¿Cuánto dura cada sesión o partido?": "", "¿Cómo de intenso es?": "", "Si tienes más de un deporte o cambian los días u horas, explícalo": "", "¿Cómo es tu trabajo o estudio?": "Sentado casi todo el día", "¿Qué días trabajas o estudias?": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"], "¿A qué hora empiezas a trabajar o estudiar?": "08:00 (mañana)", "¿A qué hora terminas?": "16:00 (tarde)", "Si tienes jornada partida o tu horario cambia según el día, explícalo": "", "¿Cuántos pasos haces al día más o menos?": "5.000-8.000", "¿Tienes turnos rotativos? (por ejemplo, una semana de mañanas y otra de tardes)": "No", "¿Cómo son tus turnos y a qué hora entrenarías en cada uno?": "", "¿A qué hora te levantas normalmente entre semana?": "07:30 (mañana)", "¿A qué hora te acuestas normalmente entre semana?": "23:30 (noche)", "¿Cómo duermes?": "Regular", "¿Cuánto estrés tienes últimamente?": 5, "¿Fumas o bebes alcohol?": ["Alcohol los fines de semana"], "¿Cómo es tu apetito?": "Normal", "Cuéntame todo lo que comiste y bebiste AYER, con la hora y la cantidad aproximada": "Torrades amb pernil, macarrons amb tonyina, iogurt i fruita, truita amb amanida.", "¿A qué hora haces cada comida en un día normal entre semana? [Desayuno]": "7-9 h", "¿A qué hora haces cada comida en un día normal entre semana? [Media mañana]": "No la hago", "¿A qué hora haces cada comida en un día normal entre semana? [Comida]": "13-15 h", "¿A qué hora haces cada comida en un día normal entre semana? [Merienda]": "17-19 h", "¿A qué hora haces cada comida en un día normal entre semana? [Cena]": "21-23 h", "¿A qué hora haces cada comida en un día normal entre semana? [Algo antes de dormir]": "No la hago", "¿Cuántas comidas al día te verías capaz de hacer?": "4", "¿Dónde sueles comer entre semana?": ["En casa", "De tupper en el trabajo o estudio"], "¿Quién cocina normalmente en casa?": "Un poco de todo", "¿Cuánto tiempo puedes dedicar a cocinar?": "Algo", "¿Tienes báscula de cocina?": "Sí", "¿Sigues alguna dieta especial?": "Ninguna", "¿Qué alimentos NO comes o no te gustan nada?": ["Como de todo"], "¿Qué alimentos te encantan?": ["Pollo", "Arroz", "Pasta"], "¿Cómo son tus fines de semana con la comida?": "Como fuera alguna vez", "¿Tomas algún suplemento?": ["Ninguno"], "¿Cuánta agua bebes al día?": "1-2 litros", "¿Qué presupuesto tienes para la compra?": "Normal", "¿Tienes o has tenido una relación difícil con la comida? (atracones, comer muy poco a propósito, compensar con ejercicio)": "No", "¿Algo más que deba saber?": "", "Confirmo que:": ["La información que he dado es verdadera y avisaré si mi salud cambia.", "Entiendo que es un plan de entrenamiento y orientación nutricional general, no un tratamiento médico.", "Si tengo alguna enfermedad o tomo medicación, consultaré el plan con mi médico antes de empezar.", "Doy permiso para que mis datos se usen solo para preparar y revisar mi plan."], "¿Me dejas usar tus resultados de forma anónima como ejemplo?": "Sí", "Si eres menor de 18 años: ¿tu padre, madre o tutor está de acuerdo?": "Soy mayor de edad"}',
  1, CURDATE() - INTERVAL 52 DAY + INTERVAL 20 HOUR),
 (@pau, '{"Idioma del formulario": "Català", "¿En qué idioma quieres tu plan?": "Català", "Fecha de nacimiento": "02/06/1995", "Altura (cm)": 172, "Peso actual (kg)": 88, "¿Cómo ha cambiado tu peso en los últimos 6 meses?": "Ha subido", "¿Tienes báscula para pesarte en casa?": "Sí", "¿Cómo te ves ahora mismo?": "Con bastante grasa", "Sexo": "Hombre", "¿Estás embarazada, has dado a luz en el último año o das el pecho?": "", "¿Cómo es tu ciclo menstrual?": "", "¿Algún médico te ha dicho que tienes un problema de corazón o tensión alta?": "No", "¿Sientes dolor en el pecho, mareos o pierdes el conocimiento al hacer esfuerzo?": "No", "¿Tienes alguna enfermedad diagnosticada?": ["Ninguna"], "¿Tomas alguna medicación de forma habitual? ¿Cuál?": "Cap", "¿Tienes alguna alergia o intolerancia alimentaria?": ["Lactosa"], "¿Te has lesionado u operado alguna vez, aunque ya estés recuperado? ¿Qué te pasó?": "", "¿Tienes ahora mismo alguna lesión, dolor o molestia?": "Sí", "¿Dónde?": ["Rodilla"], "Cuéntame qué te pasa y desde cuándo": "Em molesta el genoll dret quan salto o baixo escales.", "¿Qué ejercicios o movimientos te duelen?": "Saltar", "¿Te está tratando un médico o fisioterapeuta?": "No", "¿Cuál es tu objetivo principal?": "Perder grasa", "¿Qué zonas quieres mejorar más?": ["Ninguna en especial"], "¿Tienes una fecha o evento en mente? ¿Cuál y cuándo?": "", "¿Qué te ha hecho dejarlo o no avanzar otras veces?": ["No ser constante", "Falta de tiempo"], "¿Cuánto compromiso puedes poner ahora mismo?": 8, "¿Por qué quieres empezar ahora?": "", "¿A qué ritmo quieres avanzar?": "Tranquilo: más fácil de mantener, resultados más lentos", "¿Cuánto tiempo llevas entrenando con pesas de forma constante?": "Nunca he entrenado", "¿Estás entrenando ahora?": "", "¿Qué rutina hacías? (días por semana y cómo la repartías)": "", "¿Qué ejercicios sabes hacer con buena técnica?": [], "Si los haces: ¿cuánto peso mueves y a cuántas repeticiones?": "", "¿Sabes lo que es el RIR (repeticiones en recámara)?": "", "¿Cuántos días a la semana puedes entrenar de verdad?": "3", "¿Qué días podrías ir? (marca todos los posibles)": ["Lunes", "Miércoles", "Viernes"], "¿Cuánto tiempo tienes por sesión?": "45-60 min", "¿A qué hora sueles empezar a entrenar?": "07:00 (mañana)", "Si entrenas a horas distintas según el día, explícalo": "", "¿Qué ejercicios te gustan mucho?": [], "¿Qué ejercicios odias o no quieres hacer?": [], "¿Quieres incluir cardio?": "Sí, poco (1-2 días)", "Si haces cardio, ¿cuál prefieres?": ["Caminar", "Bici"], "Aparte del gimnasio, ¿practicas algún deporte o actividad física?": "No", "¿Qué deporte o actividad?": "", "¿Qué días lo practicas?": [], "¿A qué hora empiezas normalmente?": "", "¿Cuánto dura cada sesión o partido?": "", "¿Cómo de intenso es?": "", "Si tienes más de un deporte o cambian los días u horas, explícalo": "", "¿Cómo es tu trabajo o estudio?": "Sentado casi todo el día", "¿Qué días trabajas o estudias?": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"], "¿A qué hora empiezas a trabajar o estudiar?": "09:00 (mañana)", "¿A qué hora terminas?": "18:00 (tarde)", "Si tienes jornada partida o tu horario cambia según el día, explícalo": "", "¿Cuántos pasos haces al día más o menos?": "8.000-12.000", "¿Tienes turnos rotativos? (por ejemplo, una semana de mañanas y otra de tardes)": "Sí", "¿Cómo son tus turnos y a qué hora entrenarías en cada uno?": "Alguna setmana faig tardes (14-22). Llavors entrenaria a les 10:00.", "¿A qué hora te levantas normalmente entre semana?": "06:00 (mañana)", "¿A qué hora te acuestas normalmente entre semana?": "23:00 (noche)", "¿Cómo duermes?": "Regular", "¿Cuánto estrés tienes últimamente?": 5, "¿Fumas o bebes alcohol?": ["Alcohol los fines de semana"], "¿Cómo es tu apetito?": "Siempre tengo hambre", "Cuéntame todo lo que comiste y bebiste AYER, con la hora y la cantidad aproximada": "", "¿A qué hora haces cada comida en un día normal entre semana? [Desayuno]": "5-7 h", "¿A qué hora haces cada comida en un día normal entre semana? [Media mañana]": "No la hago", "¿A qué hora haces cada comida en un día normal entre semana? [Comida]": "13-15 h", "¿A qué hora haces cada comida en un día normal entre semana? [Merienda]": "No la hago", "¿A qué hora haces cada comida en un día normal entre semana? [Cena]": "19-21 h", "¿A qué hora haces cada comida en un día normal entre semana? [Algo antes de dormir]": "No la hago", "¿Cuántas comidas al día te verías capaz de hacer?": "3", "¿Dónde sueles comer entre semana?": ["En casa", "De tupper en el trabajo o estudio"], "¿Quién cocina normalmente en casa?": "Un poco de todo", "¿Cuánto tiempo puedes dedicar a cocinar?": "Algo", "¿Tienes báscula de cocina?": "Sí", "¿Sigues alguna dieta especial?": "Ninguna", "¿Qué alimentos NO comes o no te gustan nada?": ["Como de todo"], "¿Qué alimentos te encantan?": [], "¿Cómo son tus fines de semana con la comida?": "Me descontrolo bastante", "¿Tomas algún suplemento?": ["Ninguno"], "¿Cuánta agua bebes al día?": "1-2 litros", "¿Qué presupuesto tienes para la compra?": "Normal", "¿Tienes o has tenido una relación difícil con la comida? (atracones, comer muy poco a propósito, compensar con ejercicio)": "No", "¿Algo más que deba saber?": "Treballo a torns alguna setmana.", "Confirmo que:": ["La información que he dado es verdadera y avisaré si mi salud cambia.", "Entiendo que es un plan de entrenamiento y orientación nutricional general, no un tratamiento médico.", "Si tengo alguna enfermedad o tomo medicación, consultaré el plan con mi médico antes de empezar.", "Doy permiso para que mis datos se usen solo para preparar y revisar mi plan."], "¿Me dejas usar tus resultados de forma anónima como ejemplo?": "Sí", "Si eres menor de 18 años: ¿tu padre, madre o tutor está de acuerdo?": "Soy mayor de edad"}',
  1, CURDATE() - INTERVAL 1 DAY + INTERVAL 21 HOUR);

-- Rutina del Marc: dilluns pit i braços, dimecres esquena, divendres cames.
-- base/augment: pes de la primera setmana i quant puja cada 2 setmanes (buit = pes corporal)
CREATE TEMPORARY TABLE pla_prova (dia TINYINT, ordre TINYINT, exercici VARCHAR(80), series TINYINT,
                                  maquina VARCHAR(20), nota VARCHAR(120), base DECIMAL(5,1), augment DECIMAL(4,1));
INSERT INTO pla_prova VALUES
 (1, 1, 'Press de banca', 3, 'PRESS-01', NULL, 55, 2.5),
 (1, 2, 'Press inclinat amb manuelles', 3, NULL, 'banc a 30°', 18, 2),
 (1, 3, 'Extensió de tríceps', 3, 'TRICEP-01', NULL, 25, 2.5),
 (1, 4, 'Flexions', 2, NULL, 'fins que no en surtin més', NULL, NULL),
 (3, 1, 'Rem assegut', 3, 'REM-01', 'agafada neutra', 40, 2.5),
 (3, 2, 'Jaló al pit', 3, 'JALO-01', NULL, 45, 2.5),
 (3, 3, 'Curl de bíceps amb manuelles', 3, NULL, NULL, 10, 1),
 (5, 1, 'Sentadilla', 3, 'SQUAT-01', 'amb cinturó', 70, 5),
 (5, 2, 'Hip thrust', 3, 'HIP-01', NULL, 60, 5),
 (5, 3, 'Gambades amb manuelles', 3, NULL, 'per cama', 12, 2);

INSERT INTO rutina (usuari_id, dia, ordre, exercici, series, maquina_id, nota)
SELECT @marc, p.dia, p.ordre, p.exercici, p.series, m.id, p.nota
  FROM pla_prova p LEFT JOIN maquines m ON m.codi = p.maquina;

-- Les últimes 8 setmanes (0 = aquesta) i números per a sèries i repeticions
CREATE TEMPORARY TABLE setmanes_prova (n TINYINT PRIMARY KEY);
INSERT INTO setmanes_prova VALUES (0), (1), (2), (3), (4), (5), (6), (7);
CREATE TEMPORARY TABLE nums_prova (n TINYINT PRIMARY KEY);
INSERT INTO nums_prova VALUES (1), (2), (3), (4), (5), (6), (7), (8), (9), (10), (11), (12), (13), (14), (15), (16), (17), (18), (19), (20);

-- Dies que el Marc ha entrenat: els de la rutina fins avui, menys el divendres de fa 4 setmanes (hi va faltar)
CREATE TEMPORARY TABLE dies_prova AS
SELECT DISTINCT w.n AS setmana, p.dia, @dilluns - INTERVAL w.n WEEK + INTERVAL (p.dia - 1) DAY AS data
  FROM pla_prova p JOIN setmanes_prova w
 WHERE @dilluns - INTERVAL w.n WEEK + INTERVAL (p.dia - 1) DAY < CURDATE()
   AND w.n <= 6
   AND NOT (p.dia = 5 AND w.n = 4);

INSERT IGNORE INTO entrenaments (usuari_id, data) SELECT @marc, data FROM dies_prova;

-- Màquines amb polsera: les sèries que hauria enviat la màquina
INSERT IGNORE INTO series (entrenament_id, maquina_id, num_serie, carrega_kg, inici)
SELECT e.id, m.id, s.n, p.base + p.augment * FLOOR((6 - d.setmana) / 2),
       d.data + INTERVAL 18 HOUR + INTERVAL (p.ordre * 12 + s.n * 3) MINUTE
  FROM pla_prova p
  JOIN maquines m ON m.codi = p.maquina
  JOIN dies_prova d ON d.dia = p.dia
  JOIN entrenaments e ON e.usuari_id = @marc AND e.data = d.data
  JOIN nums_prova s ON s.n <= p.series;

-- Repeticions de les sèries noves: de 7 a 10, i cada una una mica més lenta
INSERT IGNORE INTO repeticions (serie_id, num_rep, velocitat_mitjana, recorregut_m, durada_s)
SELECT s.id, r.n, ROUND(0.80 - r.n * 0.025 - s.num_serie * 0.01, 2), 0.40 + (s.maquina_id MOD 3) * 0.08, ROUND(0.55 + r.n * 0.035, 2)
  FROM series s
  JOIN entrenaments e ON e.id = s.entrenament_id
  JOIN nums_prova r ON r.n <= 10 - s.num_serie + (DAYOFYEAR(e.data) MOD 2)
 WHERE e.usuari_id = @marc
   AND s.id NOT IN (SELECT serie_id FROM (SELECT DISTINCT serie_id FROM repeticions) ja);

-- Exercicis sense màquina: el que el Marc ha apuntat a mà
INSERT INTO registre (rutina_id, data, num_serie, carrega_kg, reps)
SELECT r.id, d.data, s.n,
       CASE WHEN p.base IS NULL THEN NULL ELSE p.base + p.augment * FLOOR((6 - d.setmana) / 2) END,
       CASE WHEN p.base IS NULL THEN 14 + (6 - d.setmana) - 2 * s.n ELSE 11 - s.n + (d.setmana MOD 2) END
  FROM pla_prova p
  JOIN rutina r ON r.usuari_id = @marc AND r.dia = p.dia AND r.ordre = p.ordre
  JOIN dies_prova d ON d.dia = p.dia
  JOIN nums_prova s ON s.n <= p.series
 WHERE p.maquina IS NULL
   AND NOT (p.ordre = 4 AND d.setmana = 2);          -- un dilluns no va fer flexions

-- Seguiment del Marc: dues revisions respostes (l'última fa 16 dies, així ara li toca la següent)
INSERT INTO revisions (usuari_id, data, pes_kg, com_va, resposta, respost_per, respost) VALUES
 (@marc, CURDATE() - INTERVAL 30 DAY, 76.5, 'Les dues primeres setmanes bé. El press inclinat amb manuelles em costa d''aguantar les 3 sèries.',
  'Normal al principi. Baixa a 16 kg al press inclinat i quan facis 3 x 10 tornes a 18.', @admin, CURDATE() - INTERVAL 29 DAY + INTERVAL 10 HOUR),
 (@marc, CURDATE() - INTERVAL 16 DAY, 77.2, 'Ara sí que arribo a tot. He pujat una mica de pes i em veig més fort de pit.',
  'Molt bé, el pes puja al ritme que volíem. Seguim igual dues setmanes més i després canviem les flexions per fons.', @admin, CURDATE() - INTERVAL 15 DAY + INTERVAL 9 HOUR);

INSERT INTO missatges (usuari_id, autor_id, text, data, llegit) VALUES
 (@marc, @marc, 'Divendres no podré venir, faig la rutina de cames dissabte al matí?', CURDATE() - INTERVAL 9 DAY + INTERVAL 13 HOUR, 1),
 (@marc, @admin, 'Sí, cap problema. Dissabte obrim de 9 a 14 h.', CURDATE() - INTERVAL 9 DAY + INTERVAL 17 HOUR, 1),
 (@marc, @admin, 'Recorda que aquesta setmana et toca la revisió.', CURDATE() - INTERVAL 1 DAY + INTERVAL 12 HOUR, 0);

DROP TEMPORARY TABLE pla_prova, setmanes_prova, nums_prova, dies_prova;

SELECT u.nom, t.nom AS tarifa,
       (SELECT COUNT(*) FROM rutina r WHERE r.usuari_id = u.id) AS exercicis,
       (SELECT COUNT(*) FROM registre g JOIN rutina r ON r.id = g.rutina_id WHERE r.usuari_id = u.id) AS series_a_ma,
       (SELECT COUNT(*) FROM series s JOIN entrenaments e ON e.id = s.entrenament_id WHERE e.usuari_id = u.id) AS series_maquina
  FROM usuaris u JOIN tarifes t ON t.id = u.tarifa_id WHERE u.rol = 'client';
