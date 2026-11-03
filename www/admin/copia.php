<?php
// Descarrega una còpia de seguretat de la base de dades (només administradors).
// Les còpies són fora de la web o en una carpeta tancada: només es poden baixar des d'aquí.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

$nom = $_GET['f'] ?? '';
$dir = carpeta_copies();
if (!$dir || !preg_match(COPIES_PATRO, $nom) || !is_file("$dir/$nom")) {
    pagina_error(404, 'Aquesta còpia no existeix.');
}
header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . $nom . '"');
header('Content-Length: ' . filesize("$dir/$nom"));
header('X-Content-Type-Options: nosniff');
readfile("$dir/$nom");
