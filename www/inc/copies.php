<?php
const COPIES_GUARDAR = 14;
const COPIES_CADA    = 86400;
const COPIES_PATRO   = '/^gymzero_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/';

function carpeta_copies()
{
    $opcions = defined('COPIES_DIR') ? [COPIES_DIR] : [];
    $opcions[] = dirname(__DIR__, 2) . '/backup_db';
    $opcions[] = dirname(__DIR__) . '/copies';
    foreach ($opcions as $dir) {
        if ((@is_dir($dir) || @mkdir($dir, 0700, true)) && @is_writable($dir)) {
            $arrels = array_filter([realpath(dirname(__DIR__)), realpath($_SERVER['DOCUMENT_ROOT'] ?? '')]);
            if (array_filter($arrels, fn($a) => str_starts_with(realpath($dir), $a))) {
                if (!is_file("$dir/.htaccess")) {
                    copy(__DIR__ . '/.htaccess', "$dir/.htaccess");
                }
            }
            return realpath($dir);
        }
    }
    return null;
}

function llista_copies($dir = null)
{
    $dir = $dir ?? carpeta_copies();
    $copies = [];
    foreach ($dir ? (glob("$dir/gymzero_*.sql.gz") ?: []) : [] as $f) {
        if (preg_match(COPIES_PATRO, basename($f))) {
            $copies[basename($f)] = ['mida' => filesize($f), 'data' => filemtime($f)];
        }
    }
    uasort($copies, fn($a, $b) => $b['data'] <=> $a['data']);
    return $copies;
}

function avisa_admins_copia(PDO $bd, $text)
{
    $bd->prepare("INSERT INTO notificacions (usuari_id, tipus, missatge)
                  SELECT id, 'sistema', ? FROM usuaris WHERE rol = 'admin' AND actiu = 1")
       ->execute([mb_substr($text, 0, 255)]);
}

function fes_copia(PDO $bd, $dir)
{
    $ara = new DateTime('now', new DateTimeZone('Europe/Madrid'));
    $nom = 'gymzero_' . $ara->format('Y-m-d_His') . '.sql.gz';
    $temporal = "$dir/.$nom.tmp";
    $gz = gzopen($temporal, 'wb6');
    if (!$gz) {
        throw new RuntimeException("no es pot escriure a la carpeta de còpies");
    }
    $escriu = function ($text) use ($gz) {
        if (gzwrite($gz, $text) === false) {
            throw new RuntimeException('no s\'ha pogut escriure el fitxer');
        }
    };

    try {
        $bd->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $bd->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

        $objectes = $bd->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $taules = array_column(array_filter($objectes, fn($o) => $o[1] === 'BASE TABLE'), 0);
        $vistes = array_column(array_filter($objectes, fn($o) => $o[1] === 'VIEW'), 0);

        $binaries = [];
        foreach ($bd->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
                              WHERE TABLE_SCHEMA = DATABASE()
                                AND DATA_TYPE IN ('tinyblob','blob','mediumblob','longblob','binary','varbinary')") as $c) {
            $binaries[$c['TABLE_NAME']][$c['COLUMN_NAME']] = true;
        }

        $escriu("-- Còpia de seguretat de Gym Zero (" . BD_NOM . ") feta el " . $ara->format('d/m/Y H:i:s') . "\n"
              . "-- Per restaurar-la: phpMyAdmin > base de dades > Importar > aquest fitxer.\n\n"
              . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        foreach ($vistes as $v) {
            $escriu("DROP VIEW IF EXISTS `$v`;\n");
        }
        foreach ($taules as $t) {
            $crea = $bd->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            $escriu("\nDROP TABLE IF EXISTS `$t`;\n$crea;\n");

            $bd->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $bd->query("SELECT * FROM `$t`");
            $files = [];
            while ($fila = $st->fetch(PDO::FETCH_ASSOC)) {
                $valors = [];
                foreach ($fila as $col => $v) {
                    if ($v === null) {
                        $valors[] = 'NULL';
                    } elseif (isset($binaries[$t][$col])) {
                        $valors[] = $v === '' ? "''" : '0x' . bin2hex($v);
                    } else {
                        $valors[] = $bd->quote($v);
                    }
                }
                $files[] = '(' . implode(',', $valors) . ')';
                if (count($files) === 100) {
                    $escriu("INSERT INTO `$t` VALUES\n" . implode(",\n", $files) . ";\n");
                    $files = [];
                }
            }
            $st->closeCursor();
            $bd->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            if ($files) {
                $escriu("INSERT INTO `$t` VALUES\n" . implode(",\n", $files) . ";\n");
            }
        }
        foreach ($vistes as $v) {
            $crea = $bd->query("SHOW CREATE VIEW `$v`")->fetch(PDO::FETCH_NUM)[1];
            $crea = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $crea);
            $escriu("\n$crea;\n");
        }
        $escriu("\nSET FOREIGN_KEY_CHECKS = 1;\n-- Fi de la còpia\n");
        $bd->exec('COMMIT');
    } catch (Throwable $e) {
        $bd->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        if ($bd->inTransaction()) {
            $bd->rollBack();
        } else {
            @$bd->exec('ROLLBACK');
        }
        gzclose($gz);
        @unlink($temporal);
        throw $e;
    }
    gzclose($gz);

    $text = '';
    $gz = gzopen($temporal, 'rb');
    while (!gzeof($gz)) {
        $text = substr($text, -40) . gzread($gz, 1 << 20);
    }
    gzclose($gz);
    $creades = 0;
    $gz = gzopen($temporal, 'rb');
    while (($linia = gzgets($gz)) !== false) {
        $creades += str_starts_with($linia, 'CREATE TABLE ');
    }
    gzclose($gz);
    if (!str_ends_with($text, "-- Fi de la còpia\n") || $creades !== count($taules)) {
        @unlink($temporal);
        throw new RuntimeException('la còpia ha quedat incompleta');
    }
    rename($temporal, "$dir/$nom");
    @chmod("$dir/$nom", 0600);

    foreach (array_slice(array_keys(llista_copies($dir)), COPIES_GUARDAR) as $antiga) {
        @unlink("$dir/$antiga");
    }
    return $nom;
}

function copia_si_toca(PDO $bd)
{
    $dir = carpeta_copies();
    if ($dir) {
        $ultima = current(llista_copies($dir));
        if ($ultima && time() - $ultima['data'] < COPIES_CADA) {
            return;
        }
        $intent = "$dir/.ultim_intent";
        if (is_file($intent) && time() - filemtime($intent) < 3600) {
            return;
        }
        $bloqueig = @fopen("$dir/.bloqueig", 'c');
        if (!$bloqueig || !flock($bloqueig, LOCK_EX | LOCK_NB)) {
            return;
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    ignore_user_abort(true);
    @set_time_limit(300);

    if ($dir) {
        touch($intent);
    }
    try {
        if (!$dir) {
            throw new RuntimeException('no hi ha cap carpeta on es puguin guardar les còpies');
        }
        $nom = fes_copia($bd, $dir);
        $mida = filesize("$dir/$nom");
        avisa_admins_copia($bd, 'Còpia de seguretat feta: ' . $nom . ' (' . number_format($mida / 1024, 0, ',', '.') . ' KB).');
    } catch (Throwable $e) {
        error_log('GYM-ZERO còpia: ' . $e->getMessage());
        try {
            $avisat = $bd->query("SELECT COUNT(*) FROM notificacions WHERE tipus = 'sistema'
                                   AND missatge LIKE 'ERROR: la còpia%' AND data >= NOW() - INTERVAL 1 DAY")->fetchColumn();
            if (!$avisat) {
                avisa_admins_copia($bd, 'ERROR: la còpia de seguretat automàtica ha fallat (' . $e->getMessage() . ').');
            }
        } catch (Throwable $e2) {
            error_log('GYM-ZERO còpia: ' . $e2->getMessage());
        }
    } finally {
        if (!empty($bloqueig)) {
            flock($bloqueig, LOCK_UN);
            fclose($bloqueig);
        }
    }
}
