<?php
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
$t = requereix_tarifa($connexio, 2);

$usuari_id = usuari_actual()['id'];
if ($t['rol'] === 'admin' && isset($_GET['usuari'])) {
    $usuari_id = (int) $_GET['usuari'];
}

$st = $connexio->prepare('SELECT nom_fitxer, pdf FROM plans WHERE usuari_id = ? ORDER BY pujat DESC, id DESC LIMIT 1');
$st->execute([$usuari_id]);
$pla = $st->fetch();
if (!$pla) {
    pagina_error(404, 'Encara no tens el pla. L\'entrenador te\'l fa quan has enviat el qüestionari.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $pla['nom_fitxer']) . '"');
header('Content-Length: ' . strlen($pla['pdf']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
echo $pla['pdf'];
