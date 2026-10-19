<?php
// Rep els formularis de "Reservar" i "Cancel·lar" i torna a la pàgina d'on venia.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/reserves.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}
requereix_login();
comprova_csrf();

$usuari_id = usuari_actual()['id'];
$classe_id = (int) ($_POST['classe_id'] ?? 0);

if ($_POST['accio'] === 'reservar') {
    [$ok, $text] = reservar($connexio, $usuari_id, $classe_id);
} elseif ($_POST['accio'] === 'cancelar') {
    [$ok, $text] = cancelar($connexio, $usuari_id, $classe_id);
} else {
    [$ok, $text] = [false, 'Acció desconeguda.'];
}

avis($ok ? 'ok' : 'error', $text);
header('Location: ' . (($_POST['tornar'] ?? '') === 'client' ? '../client/' : './'));
exit;
