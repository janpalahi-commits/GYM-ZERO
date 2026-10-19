<?php
// API de les màquines: rep una sèrie (amb les seves repeticions) i la guarda.
//
// La màquina real (dissenyada, no construïda) i el simulador fan el mateix:
//   POST /api/serie.php
//   Capçalera  X-Token: <clau de la màquina>
//   Cos (JSON):
//   {
//     "maquina":    "PRESS-01",
//     "targeta":    "04A1B2C3",          <- UID de la targeta RFID de l'usuari
//     "carrega_kg": 62.5,
//     "repeticions": [
//       {"velocitat": 0.77, "recorregut": 0.41, "durada": 0.52},
//       ...
//     ]
//   }
// Respon sempre en JSON: {"ok": true, ...} o {"ok": false, "error": "..."}

require_once __DIR__ . '/../inc/bd.php';

header('Content-Type: application/json; charset=utf-8');
// Perquè json_encode escrigui 0.65 i no 0.65000000000000002 (l'XAMPP ve configurat així)
ini_set('serialize_precision', '-1');

function respon(int $codi, array $dades)
{
    http_response_code($codi);
    echo json_encode($dades, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_api(int $codi, string $missatge)
{
    respon($codi, ['ok' => false, 'error' => $missatge]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    error_api(405, 'Només s\'accepta POST.');
}

$dades = json_decode(file_get_contents('php://input'), true);
if (!is_array($dades)) {
    error_api(400, 'El cos de la petició no és un JSON vàlid.');
}

// 1. Quina màquina és i si la clau és correcta
$codi  = (string) ($dades['maquina'] ?? '');
$token = $_SERVER['HTTP_X_TOKEN'] ?? '';

$st = $connexio->prepare('SELECT id, estat, token_hash FROM maquines WHERE codi = ?');
$st->execute([$codi]);
$maquina = $st->fetch();

// El mateix missatge si la màquina no existeix o si la clau no és bona,
// així no es pot endevinar quins codis existeixen
if (!$maquina || $token === '' || !password_verify($token, $maquina['token_hash'])) {
    error_api(401, 'Màquina o clau incorrectes.');
}
if ($maquina['estat'] !== 'activa') {
    error_api(403, 'Aquesta màquina no està activa (' . $maquina['estat'] . ').');
}

// 2. Qui entrena (per la targeta)
$st = $connexio->prepare('SELECT id, nom FROM usuaris WHERE targeta_rfid = ? AND actiu = 1');
$st->execute([(string) ($dades['targeta'] ?? '')]);
$usuari = $st->fetch();
if (!$usuari) {
    error_api(404, 'Targeta desconeguda o compte desactivat.');
}

// 3. Comprovar les dades de la sèrie
$carrega = $dades['carrega_kg'] ?? null;
if (!is_numeric($carrega) || $carrega < 0 || $carrega > 500) {
    error_api(422, 'La càrrega ha de ser un número entre 0 i 500 kg.');
}

$reps = $dades['repeticions'] ?? null;
if (!is_array($reps) || count($reps) < 1 || count($reps) > 50) {
    error_api(422, 'Hi ha d\'haver entre 1 i 50 repeticions.');
}

// Límits que no poden passar en una màquina de força (si arriben, el sensor falla)
$limits = ['velocitat' => [0.01, 5], 'recorregut' => [0.01, 3], 'durada' => [0.05, 20]];
foreach (array_values($reps) as $i => $rep) {
    foreach ($limits as $camp => [$min, $max]) {
        $valor = $rep[$camp] ?? null;
        if (!is_numeric($valor) || $valor < $min || $valor > $max) {
            error_api(422, 'Repetició ' . ($i + 1) . ": el camp '$camp' ha de ser entre $min i $max.");
        }
    }
}

// 4. Guardar-ho tot dins d'una transacció: o es guarda la sèrie sencera o res
$connexio->beginTransaction();

// Entrenament d'avui (es crea amb la primera sèrie del dia)
$connexio->prepare(
    'INSERT INTO entrenaments (usuari_id, data) VALUES (?, CURDATE())
     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
)->execute([$usuari['id']]);
$entrenament_id = (int) $connexio->lastInsertId();

// Bloquejar l'entrenament perquè dues sèries que arriben alhora no tinguin el mateix número
$st = $connexio->prepare('SELECT id FROM entrenaments WHERE id = ? FOR UPDATE');
$st->execute([$entrenament_id]);

$st = $connexio->prepare(
    'SELECT COALESCE(MAX(num_serie), 0) + 1 FROM series WHERE entrenament_id = ? AND maquina_id = ?'
);
$st->execute([$entrenament_id, $maquina['id']]);
$num_serie = (int) $st->fetchColumn();

$connexio->prepare(
    'INSERT INTO series (entrenament_id, maquina_id, num_serie, carrega_kg, inici) VALUES (?, ?, ?, ?, NOW())'
)->execute([$entrenament_id, $maquina['id'], $num_serie, $carrega]);
$serie_id = (int) $connexio->lastInsertId();

$st = $connexio->prepare(
    'INSERT INTO repeticions (serie_id, num_rep, velocitat_mitjana, recorregut_m, durada_s) VALUES (?, ?, ?, ?, ?)'
);
foreach (array_values($reps) as $i => $rep) {
    $st->execute([$serie_id, $i + 1, $rep['velocitat'], $rep['recorregut'], $rep['durada']]);
}

$connexio->commit();

// 5. Tornar el resum, perquè la màquina el pugui ensenyar a la pantalla
$st = $connexio->prepare(
    'SELECT repeticions, velocitat_mitjana, perdua_velocitat_pct FROM v_resum_series WHERE serie_id = ?'
);
$st->execute([$serie_id]);
$resum = $st->fetch();

respon(201, [
    'ok'                   => true,
    'usuari'               => $usuari['nom'],
    'serie_id'             => $serie_id,
    'num_serie'            => $num_serie,
    'repeticions'          => (int) $resum['repeticions'],
    'velocitat_mitjana'    => (float) $resum['velocitat_mitjana'],
    'perdua_velocitat_pct' => (float) $resum['perdua_velocitat_pct'],
]);
