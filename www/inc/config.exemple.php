<?php
// GYM-ZERO · Configuració de la base de dades
//
// 1. Copia aquest fitxer i anomena'l config.php (a la mateixa carpeta).
// 2. Posa-hi les dades de la teva base de dades.
// 3. config.php NO es puja mai a GitHub (està al .gitignore).
//    Al servidor de Dinahosting es puja només amb FileZilla.

// XAMPP (local)
const BD_HOST = 'localhost';
const BD_NOM  = 'gymzero_db';
const BD_USER = 'root';
const BD_PASS = '';

// Correus (recuperar la contrasenya). A XAMPP es desen a /tmp/gymzero-correus.txt.
const CORREU_MODE = 'fitxer';
const WEB_URL     = 'http://localhost/gymzero';

// Dinahosting (exemple: canvia-ho a la còpia del servidor)
// const BD_HOST = 'localhost';
// const BD_NOM  = 'nom_de_la_bd';
// const BD_USER = 'usuari_de_la_bd';
// const BD_PASS = 'contrasenya';
// const CORREU_MODE = 'mail';
// const CORREU_DE   = 'no-respondre@gymzero.cat';
// const WEB_URL     = 'https://gymzero.cat';
