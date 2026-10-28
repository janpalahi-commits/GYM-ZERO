<?php
// Enviament de correus de la web (de moment, només el de recuperar la contrasenya).
// A Dinahosting s'envien amb mail() de PHP. A XAMPP no hi ha servidor de correu:
// el correu es desa en un fitxer de text de la carpeta temporal (/tmp/gymzero-correus.txt)
// per poder-lo obrir i provar l'enllaç. Es tria a config.php amb CORREU_MODE ('mail' o 'fitxer').

function envia_correu($a, $assumpte, $text)
{
    $mode = defined('CORREU_MODE') ? CORREU_MODE : 'fitxer';
    if ($mode === 'mail') {
        $de = defined('CORREU_DE') ? CORREU_DE : 'no-respondre@gymzero.cat';
        $capcaleres = "From: Gym Zero <$de>\r\nContent-Type: text/plain; charset=UTF-8";
        $ok = mail($a, '=?UTF-8?B?' . base64_encode($assumpte) . '?=', $text, $capcaleres);
    } else {
        $correu = 'Data: ' . date('d/m/Y H:i:s') . "\nPer a: $a\nAssumpte: $assumpte\n\n$text\n" . str_repeat('-', 60) . "\n";
        $ok = file_put_contents(sys_get_temp_dir() . '/gymzero-correus.txt', $correu, FILE_APPEND | LOCK_EX) !== false;
    }
    if (!$ok) {
        error_log('GYM-ZERO correu: no s\'ha pogut enviar a ' . $a);
    }
    return $ok;
}

// Adreça de la web per als enllaços dels correus. Es posa a config.php i no es treu de la petició:
// si es fes servir el nom que envia el navegador, algú podria fer que l'enllaç portés a una altra web.
function url_web()
{
    return rtrim(defined('WEB_URL') ? WEB_URL : 'http://localhost/gymzero', '/');
}
