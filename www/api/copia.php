<?php
require_once __DIR__ . '/../inc/bd.php';

header('X-Content-Type-Options: nosniff');

function respon_copia($codi, $text)
{
    http_response_code($codi);
    header('Content-Type: text/plain; charset=utf-8');
    exit($text . "\n");
}

if (($_SERVER['HTTPS'] ?? '') !== 'on' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https'
    && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    respon_copia(403, 'Només per https.');
}

$st = $connexio->prepare("SELECT valor FROM configuracio WHERE clau = 'copies_token_hash'");
$st->execute();
$hash = $st->fetchColumn();
$token = $_SERVER['HTTP_X_TOKEN'] ?? '';
if (!$hash || $token === '' || !password_verify($token, $hash)) {
    sleep(1);
    respon_copia(401, 'Clau incorrecta.');
}

$dir = carpeta_copies();
$copies = llista_copies($dir);
if (!$copies) {
    respon_copia(404, 'Encara no hi ha cap còpia.');
}
$nom = array_key_first($copies);

touch("$dir/.descarregada");

header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . $nom . '"');
header('Content-Length: ' . filesize("$dir/$nom"));
header('X-Copia: ' . $nom);
readfile("$dir/$nom");
