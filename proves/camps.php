<?php
require __DIR__ . '/../www/inc/questionari.php';
$camps = [];
foreach (array_slice($argv, 1) as $arg) {
    [$k, $v] = explode('=', $arg, 2);
    foreach (questionari()['q'] as $i => $q) {
        if (($q['k'] ?? null) !== $k) {
            continue;
        }
        $index = function ($llista, $text) use ($k) {
            foreach ($llista as $n => $x) {
                if ((is_array($x) ? $x['ca'] : $x) === $text) {
                    return $n;
                }
            }
            return $text;
        };
        switch ($q['type']) {
            case 'mc':
                $camps[] = "p[$i]=" . urlencode($index($q['choices'], $v));
                break;
            case 'cb':
                foreach (explode('|', $v) as $t) {
                    $camps[] = "p[$i][]=" . urlencode($index($q['choices'], $t));
                }
                break;
            case 'list':
                $camps[] = "p[$i]=" . $index(hores_questionari($q['hours'], 'ca'), $v);
                break;
            case 'grid':
                foreach (explode('|', $v) as $f => $t) {
                    $camps[] = "p[$i][$f]=" . $index($q['cols'], $t);
                }
                break;
            default:
                $camps[] = "p[$i]=" . urlencode($v);
        }
    }
}
echo implode('&', $camps);
