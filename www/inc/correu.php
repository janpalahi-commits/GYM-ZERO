<?php
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

function url_web()
{
    return rtrim(defined('WEB_URL') ? WEB_URL : 'http://localhost/gymzero', '/');
}
