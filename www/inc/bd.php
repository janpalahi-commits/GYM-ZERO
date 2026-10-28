<?php
// Connexió a la base de dades amb PDO.
// Les dades de connexió són a config.php (que no es puja a GitHub).

require_once __DIR__ . '/config.php';

try {
    $connexio = new PDO(
        'mysql:host=' . BD_HOST . ';dbname=' . BD_NOM . ';charset=utf8mb4',
        BD_USER,
        BD_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // L'error real només va al log del servidor; a l'usuari no se li ensenya
    error_log('GYM-ZERO BD: ' . $e->getMessage());
    $text = 'Ara mateix no es pot connectar amb la base de dades. Torna-ho a provar més tard.';
    // L'API de les màquines i del torn no carrega sessio.php i ha de rebre text, no una pàgina
    if (function_exists('pagina_error')) {
        pagina_error(500, $text);
    }
    http_response_code(500);
    die($text);
}
