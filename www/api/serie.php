<?php
require_once __DIR__ . '/../inc/bd.php';

header('Content-Type: application/json; charset=utf-8');
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

$codi  = (string) ($dades['maquina'] ?? '');
$token = $_SERVER['HTTP_X_TOKEN'] ?? '';

$st = $connexio->prepare('SELECT id, estat, token_hash FROM maquines WHERE codi = ?');
$st->execute([$codi]);
$maquina = $st->fetch();

if (!$maquina || $token === '' || !password_verify($token, $maquina['token_hash'])) {
    error_api(401, 'Màquina o clau incorrectes.');
}
if ($maquina['estat'] !== 'activa') {
    error_api(403, 'Aquesta màquina no està activa (' . $maquina['estat'] . ').');
}

$st = $connexio->prepare('SELECT id, nom FROM usuaris WHERE targeta_rfid = ? AND actiu = 1');
$st->execute([(string) ($dades['targeta'] ?? '')]);
$usuari = $st->fetch();
if (!$usuari) {
    error_api(404, 'Polsera desconeguda o compte desactivat.');
}

$carrega = $dades['carrega_kg'] ?? null;
if (!is_numeric($carrega) || $carrega < 0 || $carrega > 500) {
    error_api(422, 'La càrrega ha de ser un número entre 0 i 500 kg.');
}

$reps = $dades['repeticions'] ?? null;
if (!is_array($reps) || count($reps) < 1 || count($reps) > 50) {
    error_api(422, 'Hi ha d\'haver entre 1 i 50 repeticions.');
}

$limits = ['velocitat' => [0.01, 5], 'recorregut' => [0.01, 3], 'durada' => [0.05, 20]];
foreach (array_values($reps) as $i => $rep) {
    foreach ($limits as $camp => [$min, $max]) {
        $valor = $rep[$camp] ?? null;
        if (!is_numeric($valor) || $valor < $min || $valor > $max) {
            error_api(422, 'Repetició ' . ($i + 1) . ": el camp '$camp' ha de ser entre $min i $max.");
        }
    }
}

$connexio->beginTransaction();

$connexio->prepare(
    'INSERT INTO entrenaments (usuari_id, data) VALUES (?, CURDATE())
     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
)->execute([$usuari['id']]);
$entrenament_id = (int) $connexio->lastInsertId();

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
