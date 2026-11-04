<?php
require_once __DIR__ . '/../inc/bd.php';

header('Content-Type: application/json; charset=utf-8');

function respon(int $codi, array $dades)
{
    http_response_code($codi);
    echo json_encode($dades, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

$codi  = (string) ($dades['torn'] ?? '');
$token = $_SERVER['HTTP_X_TOKEN'] ?? '';

$st = $connexio->prepare('SELECT id, estat, token_hash FROM torns WHERE codi = ?');
$st->execute([$codi]);
$torn = $st->fetch();

if (!$torn || $token === '' || !password_verify($token, $torn['token_hash'])) {
    error_api(401, 'Torn o clau incorrectes.');
}
if ($torn['estat'] !== 'activa') {
    error_api(403, 'Aquest torn està donat de baixa.');
}

$targeta = strtoupper((string) ($dades['targeta'] ?? ''));
if (!preg_match('/^[0-9A-F]{8,20}$/', $targeta)) {
    error_api(422, 'El codi de la polsera ha de tenir entre 8 i 20 xifres hexadecimals.');
}

$st = $connexio->prepare(
    'SELECT id, nom, rol, actiu, quota_fins,
            quota_fins >= CURDATE() AS quota_al_dia,
            quota_fins <= CURDATE() + INTERVAL 7 DAY AS caduca_aviat
       FROM usuaris WHERE targeta_rfid = ?'
);
$st->execute([$targeta]);
$usuari = $st->fetch();

$avis = '';
if (!$usuari) {
    [$obrir, $motiu, $missatge] = [false, 'Polsera desconeguda', 'Polsera desconeguda. Passa per recepció'];
} elseif (!$usuari['actiu']) {
    [$obrir, $motiu, $missatge] = [false, 'Compte desactivat', 'Compte desactivat. Passa per recepció'];
} elseif ($usuari['rol'] === 'admin') {
    [$obrir, $motiu, $missatge] = [true, 'Personal', 'Hola, ' . $usuari['nom']];
} elseif (!$usuari['quota_al_dia']) {
    [$obrir, $motiu, $missatge] = [false, 'Quota sense pagar', 'Quota sense pagar. Passa per recepció'];
} else {
    [$obrir, $motiu, $missatge] = [true, 'Quota pagada', 'Benvingut, ' . $usuari['nom']];
    if ($usuari['caduca_aviat']) {
        $avis = 'La quota caduca el ' . date('d/m', strtotime($usuari['quota_fins']));
    }
}

$connexio->prepare(
    'INSERT INTO entrades (torn_id, usuari_id, targeta, data_hora, permesa, motiu) VALUES (?, ?, ?, NOW(), ?, ?)'
)->execute([$torn['id'], $usuari['id'] ?? null, $targeta, (int) $obrir, $motiu]);

respon(200, [
    'ok'       => true,
    'obrir'    => $obrir,
    'usuari'   => $usuari['nom'] ?? null,
    'missatge' => $missatge,
    'avis'     => $avis,
]);
