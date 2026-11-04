<?php
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
    error_log('GYM-ZERO BD: ' . $e->getMessage());
    $text = 'Ara mateix no es pot connectar amb la base de dades. Torna-ho a provar més tard.';
    if (function_exists('pagina_error')) {
        pagina_error(500, $text);
    }
    http_response_code(500);
    die($text);
}

require_once __DIR__ . '/copies.php';
register_shutdown_function('copia_si_toca', $connexio);
