<?php
const IDIOMES_QUESTIONARI = ['es', 'ca', 'en', 'fr'];

function questionari()
{
    static $d = null;
    if ($d === null) {
        $d = json_decode(file_get_contents(__DIR__ . '/questionari.json'), true);
    }
    return $d;
}

function idioma_questionari($demanat)
{
    return in_array($demanat, IDIOMES_QUESTIONARI, true) ? $demanat : 'ca';
}

function tq($o, $idioma)
{
    if (!is_array($o)) {
        return (string) $o;
    }
    return ($o[$idioma] ?? '') !== '' ? $o[$idioma] : ($o['es'] ?? '');
}

function pagines_questionari()
{
    $pagines = [];
    $actual = ['titol' => null, 'ajuda' => null, 'id' => null, 'preguntes' => []];
    foreach (questionari()['q'] as $i => $q) {
        if ($q['type'] === 'page') {
            $pagines[] = $actual;
            $actual = ['titol' => $q['title'], 'ajuda' => $q['help'] ?? null, 'id' => $q['id'] ?? null, 'preguntes' => []];
        } else {
            $actual['preguntes'][] = $i;
        }
    }
    $pagines[] = $actual;
    return $pagines;
}

function hores_questionari($spec, $idioma)
{
    $franges = questionari()['franjas'];
    $franja = fn($h) => tq($franges[$h < 12 ? 'm' : ($h < 15 ? 'md' : ($h < 21 ? 't' : 'n'))], $idioma);
    $sortida = [];
    if ($spec[2]) {
        $sortida[] = tq($spec[2], $idioma);
    }
    for ($h = $spec[0]; $h <= $spec[1]; $h++) {
        foreach ([0, 30] as $m) {
            if ($h === $spec[1] && $m > 0) {
                break;
            }
            $sortida[] = sprintf('%02d:%02d (%s)', $h, $m, $franja($h));
        }
    }
    if ($spec[3] === 'MIDNIGHT') {
        foreach (['23:30', '00:00', '00:30', '01:00', '01:30', '02:00'] as $x) {
            $sortida[] = $x . ' (' . tq($franges['n'], $idioma) . ')';
        }
        $sortida[] = '> 02:00';
    } elseif ($spec[3]) {
        $sortida[] = tq($spec[3], $idioma);
    }
    return $sortida;
}

function columna_questionari($q, $fila = null)
{
    return $q['title']['es'] . ($fila === null ? '' : ' [' . $fila['es'] . ']');
}

function pregunta_per_clau($k)
{
    foreach (questionari()['q'] as $q) {
        if (($q['k'] ?? null) === $k) {
            return $q;
        }
    }
    return null;
}

function opcio_triada(array $respostes, $k)
{
    $q = pregunta_per_clau($k);
    $v = $respostes[columna_questionari($q)] ?? '';
    foreach ($q['choices'] as $c => $text) {
        if ($text['es'] === $v) {
            return $c;
        }
    }
    return null;
}

function estat_del_post(array $post)
{
    $p = (array) ($post['p'] ?? []);
    $altres = (array) ($post['o'] ?? []);
    $estat = [];
    foreach (questionari()['q'] as $i => $q) {
        $v = $p[$i] ?? null;
        $o = is_string($altres[$i] ?? null) ? mb_substr(trim($altres[$i]), 0, 300) : '';
        switch ($q['type']) {
            case 'mc':
                if ($v === 'o') {
                    $estat[$i] = ['o' => $o];
                } elseif (is_string($v) && ctype_digit($v) && isset($q['choices'][(int) $v])) {
                    $estat[$i] = ['c' => (int) $v];
                }
                break;
            case 'cb':
                $v = (array) $v;
                $c = [];
                foreach ($v as $x) {
                    if (is_string($x) && ctype_digit($x) && isset($q['choices'][(int) $x])) {
                        $c[] = (int) $x;
                    }
                }
                $c = array_values(array_unique($c));
                sort($c);
                $te_altres = in_array('o', $v, true);
                if ($c || $te_altres) {
                    $estat[$i] = ['c' => $c, 'o' => $te_altres ? $o : null];
                }
                break;
            case 'list':
                if (is_string($v) && ctype_digit($v) && (int) $v < count(hores_questionari($q['hours'], 'es'))) {
                    $estat[$i] = (int) $v;
                }
                break;
            case 'grid':
                $files = [];
                foreach (array_keys($q['rows']) as $f) {
                    $x = ((array) $v)[$f] ?? '';
                    if (is_string($x) && ctype_digit($x) && isset($q['cols'][(int) $x])) {
                        $files[$f] = (int) $x;
                    }
                }
                if ($files) {
                    $estat[$i] = $files;
                }
                break;
            case 'scale':
                if (is_string($v) && ctype_digit($v) && $v >= 1 && $v <= 10) {
                    $estat[$i] = (int) $v;
                }
                break;
            case 'date':
            case 'text':
            case 'para':
                if (is_string($v) && trim($v) !== '') {
                    $estat[$i] = mb_substr(trim($v), 0, 5000);
                }
                break;
        }
    }
    return $estat;
}

function cami_questionari(array $estat)
{
    $pagines = pagines_questionari();
    $per_id = [];
    foreach ($pagines as $n => $p) {
        if ($p['id']) {
            $per_id[$p['id']] = $n;
        }
    }
    $cami = [];
    $n = 0;
    while ($n < count($pagines) && !in_array($n, $cami, true)) {
        $cami[] = $n;
        $desti = null;
        foreach ($pagines[$n]['preguntes'] as $i) {
            $q = questionari()['q'][$i];
            if (!empty($q['nav']) && isset($estat[$i]['c']) && isset($q['nav'][$estat[$i]['c']])) {
                $desti = $per_id[$q['nav'][$estat[$i]['c']]] ?? null;
            }
        }
        $n = $desti ?? $n + 1;
    }
    return $cami;
}

function data_valida($v)
{
    $d = DateTime::createFromFormat('!Y-m-d', (string) $v);
    return $d && $d->format('Y-m-d') === $v && $v >= '1920-01-01' && $v <= date('Y-m-d');
}

function numero_questionari($v)
{
    return filter_var(str_replace(',', '.', trim((string) $v)), FILTER_VALIDATE_FLOAT);
}

function error_pregunta($q, $v, $idioma)
{
    $ui = questionari()['ui'];
    if ($q['type'] === 'header' || $q['type'] === 'page') {
        return null;
    }
    $buida = $v === null
        || ($q['type'] === 'mc' && isset($v['o']) && $v['o'] === '')
        || ($q['type'] === 'cb' && !$v['c'] && ($v['o'] ?? '') === '');
    if (!empty($q['req']) && $buida) {
        return tq($ui['required'], $idioma);
    }
    if ($q['type'] === 'grid' && !empty($q['req']) && count($v) < count($q['rows'])) {
        return tq($ui['rows'], $idioma);
    }
    if (!empty($q['num']) && $v !== null) {
        $n = numero_questionari($v);
        if ($n === false || $n < $q['num'][0] || $n > $q['num'][1]) {
            return tq($q['num'][2], $idioma);
        }
    }
    if ($q['type'] === 'date' && $v !== null && !data_valida($v)) {
        return tq($ui['required'], $idioma);
    }
    if (!empty($q['check4']) && count($v['c'] ?? []) !== count($q['choices'])) {
        return tq(questionari()['v_check'], $idioma);
    }
    return null;
}

function errors_questionari(array $estat, $idioma)
{
    $pagines = pagines_questionari();
    $errors = [];
    foreach (cami_questionari($estat) as $n) {
        foreach ($pagines[$n]['preguntes'] as $i) {
            $e = error_pregunta(questionari()['q'][$i], $estat[$i] ?? null, $idioma);
            if ($e !== null) {
                $errors[$i] = $e;
            }
        }
    }
    return $errors;
}

function respostes_questionari(array $estat, $idioma)
{
    $d = questionari();
    $pagines = pagines_questionari();
    $visitades = [];
    foreach (cami_questionari($estat) as $n) {
        $visitades = array_merge($visitades, $pagines[$n]['preguntes']);
    }
    $r = ['Idioma del formulario' => $d['names'][$idioma]];
    foreach ($d['q'] as $i => $q) {
        if ($q['type'] === 'page' || $q['type'] === 'header') {
            continue;
        }
        $v = in_array($i, $visitades, true) ? ($estat[$i] ?? null) : null;
        switch ($q['type']) {
            case 'grid':
                foreach ($q['rows'] as $f => $fila) {
                    $r[columna_questionari($q, $fila)] = isset($v[$f]) ? $q['cols'][$v[$f]]['es'] : '';
                }
                continue 2;
            case 'mc':
                $valor = $v === null ? '' : (isset($v['o']) ? $v['o'] : $q['choices'][$v['c']]['es']);
                break;
            case 'cb':
                $valor = $v === null ? [] : array_map(fn($c) => $q['choices'][$c]['es'], $v['c']);
                if (($v['o'] ?? '') !== '') {
                    $valor[] = $v['o'];
                }
                break;
            case 'list':
                $valor = $v === null ? '' : hores_questionari($q['hours'], 'es')[$v];
                break;
            case 'date':
                $valor = $v === null ? '' : implode('/', array_reverse(explode('-', $v)));
                break;
            default:
                $valor = $v ?? '';
                if (!empty($q['num']) && $v !== null) {
                    $valor = numero_questionari($v);
                }
        }
        $r[columna_questionari($q)] = $valor;
    }
    return $r;
}

function estat_de_respostes(array $r)
{
    $estat = [];
    $index = fn($llista, $text) => array_search($text, array_map(fn($x) => is_array($x) ? $x['es'] : $x, $llista), true);
    foreach (questionari()['q'] as $i => $q) {
        if ($q['type'] === 'page' || $q['type'] === 'header') {
            continue;
        }
        if ($q['type'] === 'grid') {
            foreach ($q['rows'] as $f => $fila) {
                $c = $index($q['cols'], $r[columna_questionari($q, $fila)] ?? '');
                if ($c !== false) {
                    $estat[$i][$f] = $c;
                }
            }
            continue;
        }
        $v = $r[columna_questionari($q)] ?? '';
        if ($v === '' || $v === []) {
            continue;
        }
        switch ($q['type']) {
            case 'mc':
                $c = $index($q['choices'], $v);
                $estat[$i] = $c === false ? ['o' => (string) $v] : ['c' => $c];
                break;
            case 'cb':
                $c = [];
                $o = [];
                foreach ((array) $v as $x) {
                    $k = $index($q['choices'], $x);
                    if ($k === false) {
                        $o[] = $x;
                    } else {
                        $c[] = $k;
                    }
                }
                $estat[$i] = ['c' => $c, 'o' => $o ? implode(', ', $o) : null];
                break;
            case 'list':
                $c = $index(hores_questionari($q['hours'], 'es'), $v);
                if ($c !== false) {
                    $estat[$i] = $c;
                }
                break;
            case 'date':
                $estat[$i] = implode('-', array_reverse(explode('/', $v)));
                break;
            case 'scale':
                $estat[$i] = (int) $v;
                break;
            default:
                $estat[$i] = (string) $v;
        }
    }
    return $estat;
}

function resposta_llegible($q, array $r, $idioma = 'ca')
{
    $trad = function ($llista, $text) use ($idioma) {
        foreach ($llista as $x) {
            if ($x['es'] === $text) {
                return tq($x, $idioma);
            }
        }
        return $text;
    };
    if ($q['type'] === 'grid') {
        $linies = [];
        foreach ($q['rows'] as $fila) {
            $v = $r[columna_questionari($q, $fila)] ?? '';
            if ($v !== '') {
                $linies[] = tq($fila, $idioma) . ': ' . $trad($q['cols'], $v);
            }
        }
        return implode("\n", $linies);
    }
    $v = $r[columna_questionari($q)] ?? '';
    if ($v === '' || $v === []) {
        return '';
    }
    switch ($q['type']) {
        case 'mc':
            return $trad($q['choices'], $v);
        case 'cb':
            return implode(', ', array_map(fn($x) => $trad($q['choices'], $x), (array) $v));
        case 'list':
            $c = array_search($v, hores_questionari($q['hours'], 'es'), true);
            return $c === false ? $v : hores_questionari($q['hours'], $idioma)[$c];
        default:
            return is_float($v) ? str_replace('.', ',', (string) $v) : (string) $v;
    }
}

function avisos_questionari(array $r)
{
    $avisos = [];
    if (opcio_triada($r, 'cor') === 1 || opcio_triada($r, 'dolor_pit') === 1) {
        $avisos[] = 'Ha marcat problemes de cor o pressió alta, o dolor al pit o marejos fent esforç: demana-li el vistiplau del metge abans de començar.';
    }
    $embaras = opcio_triada($r, 'embaras');
    if ($embaras !== null && $embaras > 0) {
        $avisos[] = 'Està embarassada, ha donat a llum l\'últim any o dona el pit: cal el vistiplau del metge i adaptar el pla.';
    }
    if (opcio_triada($r, 'relacio_menjar') === 1) {
        $avisos[] = 'Diu que té o ha tingut una relació difícil amb el menjar: no li posis una dieta estricta i parla-ho amb el client.';
    }
    if (opcio_triada($r, 'menor') === 1) {
        $avisos[] = 'És menor d\'edat: el pare, la mare o el tutor hi està d\'acord.';
    }
    return $avisos;
}
