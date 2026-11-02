<?php
// La llibreta d'entrenament: la rutina del client (files) i les setmanes (columnes), com un full d'Excel.
// Cada casella té les sèries d'aquella setmana: les que el client apunta a mà (taula registre)
// i les de les màquines intel·ligents, que arriben soles per l'API (taules series i repeticions).

const DIES = [1 => 'Dilluns', 2 => 'Dimarts', 3 => 'Dimecres', 4 => 'Dijous', 5 => 'Divendres', 6 => 'Dissabte', 7 => 'Diumenge'];

// Pes amb coma i sense ",0" (42,5 / 40)
function kg($valor)
{
    return $valor === null ? null : str_replace('.', ',', rtrim(rtrim(number_format((float) $valor, 1, '.', ''), '0'), '.'));
}

// Text d'una sèrie: "42,5 × 8" o "12 reps" si és amb el pes del cos
function text_serie($s)
{
    return $s['kg'] === null ? $s['reps'] . ' reps' : kg($s['kg']) . ' × ' . $s['reps'];
}

// Per comparar sèries: 1RM estimat amb Epley (el mateix que Càlculs). Sense pes, compten les repeticions.
function forca_serie($s)
{
    return $s['kg'] === null ? $s['reps'] : $s['kg'] * (1 + $s['reps'] / 30);
}

// Els dilluns de les últimes $n setmanes, de la més antiga a l'actual
function setmanes_llibreta($n = 8)
{
    $dilluns = strtotime('monday this week');
    $setmanes = [];
    for ($i = $n - 1; $i >= 0; $i--) {
        $setmanes[] = date('Y-m-d', strtotime("-$i week", $dilluns));
    }
    return $setmanes;
}

function rutina_client(PDO $bd, $usuari_id)
{
    $st = $bd->prepare(
        'SELECT r.id, r.dia, r.ordre, r.exercici, r.series, r.nota, r.maquina_id, m.codi AS maquina
           FROM rutina r LEFT JOIN maquines m ON m.id = r.maquina_id
          WHERE r.usuari_id = ? ORDER BY r.dia, r.ordre, r.id'
    );
    $st->execute([$usuari_id]);
    return $st->fetchAll();
}

// Totes les sèries de cada fila de la rutina: [rutina_id => [['data','num','kg','reps','origen'], ...]]
function series_rutina(PDO $bd, $usuari_id, array $rutina)
{
    $series = array_fill_keys(array_column($rutina, 'id'), []);

    $st = $bd->prepare(
        'SELECT g.rutina_id, g.data, g.num_serie, g.carrega_kg, g.reps
           FROM registre g JOIN rutina r ON r.id = g.rutina_id
          WHERE r.usuari_id = ? ORDER BY g.data, g.num_serie'
    );
    $st->execute([$usuari_id]);
    foreach ($st as $f) {
        $series[$f['rutina_id']][] = ['data' => $f['data'], 'num' => (int) $f['num_serie'],
            'kg' => $f['carrega_kg'] === null ? null : (float) $f['carrega_kg'], 'reps' => (int) $f['reps'], 'origen' => 'ma'];
    }

    // Màquines: si la mateixa màquina surt en dos dies de la rutina, cada sèrie va al dia de la setmana que es va fer
    $vegades = array_count_values(array_filter(array_column($rutina, 'maquina_id')));
    $st = $bd->prepare(
        'SELECT m.id AS maquina_id, v.data, v.num_serie, v.carrega_kg, v.repeticions
           FROM v_resum_series v JOIN maquines m ON m.codi = v.maquina
          WHERE v.usuari_id = ? ORDER BY v.data, v.num_serie'
    );
    $st->execute([$usuari_id]);
    foreach ($st as $f) {
        foreach ($rutina as $r) {
            if ((int) $r['maquina_id'] === (int) $f['maquina_id']
                && ($vegades[$r['maquina_id']] === 1 || (int) date('N', strtotime($f['data'])) === (int) $r['dia'])) {
                $series[$r['id']][] = ['data' => $f['data'], 'num' => (int) $f['num_serie'],
                    'kg' => (float) $f['carrega_kg'], 'reps' => (int) $f['repeticions'], 'origen' => 'maquina'];
            }
        }
    }
    return $series;
}

// La millor sèrie de tota la història d'una fila (el rècord)
function record_fila(array $series)
{
    $millor = null;
    foreach ($series as $s) {
        if (!$millor || forca_serie($s) > forca_serie($millor)) {
            $millor = $s;
        }
    }
    return $millor;
}
