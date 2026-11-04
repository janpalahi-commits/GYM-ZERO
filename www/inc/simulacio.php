<?php
const SIM_MAQUINES = [
    'PRESS-01'   => [50, [0.38, 0.45], 0.55, true],
    'SQUAT-01'   => [70, [0.55, 0.65], 0.65, true],
    'REM-01'     => [50, [0.38, 0.44], 0.60, true],
    'CONTR-01'   => [45, [0.42, 0.48], 0.55, false],
    'JALO-01'    => [50, [0.52, 0.60], 0.60, true],
    'MILITAR-01' => [30, [0.46, 0.54], 0.55, true],
    'LATERAL-01' => [10, [0.50, 0.58], 0.50, false],
    'CURL-01'    => [20, [0.42, 0.48], 0.50, false],
    'CURLP-01'   => [20, [0.42, 0.48], 0.50, false],
    'TRICEP-01'  => [25, [0.32, 0.38], 0.50, false],
    'FONS-01'    => [40, [0.36, 0.42], 0.50, true],
    'EXTQ-01'    => [45, [0.38, 0.44], 0.55, false],
    'FEMS-01'    => [35, [0.38, 0.44], 0.50, false],
    'FEME-01'    => [30, [0.38, 0.44], 0.50, false],
    'HIP-01'     => [80, [0.26, 0.32], 0.55, true],
    'ABDUC-01'   => [45, [0.26, 0.32], 0.50, false],
    'TALOD-01'   => [60, [0.08, 0.12], 0.40, false],
    'TALOA-01'   => [40, [0.06, 0.10], 0.40, false],
    'CRUNCH-01'  => [30, [0.22, 0.28], 0.45, false],
    'ROTA-01'    => [25, [0.30, 0.36], 0.45, false],
];

const SIM_SETMANA = [
    1 => ['PRESS-01' => 4, 'REM-01' => 4, 'MILITAR-01' => 3, 'JALO-01' => 3, 'CURL-01' => 3, 'TRICEP-01' => 3],
    2 => ['SQUAT-01' => 4, 'EXTQ-01' => 3, 'FEMS-01' => 3, 'HIP-01' => 3, 'TALOD-01' => 3, 'CRUNCH-01' => 3],
    4 => ['JALO-01' => 4, 'CONTR-01' => 3, 'REM-01' => 3, 'LATERAL-01' => 3, 'CURLP-01' => 3, 'FONS-01' => 3],
    5 => ['HIP-01' => 4, 'SQUAT-01' => 3, 'FEME-01' => 3, 'ABDUC-01' => 3, 'TALOA-01' => 3, 'ROTA-01' => 3],
];

const SIM_LLIURES = [
    'dominad' => null, 'flexion' => null, 'fons' => null, 'planxa' => null, 'plancha' => null, 'burpee' => null,
    'pes mort' => 70, 'peso muerto' => 70, 'sentadilla' => 60, 'esquat' => 60, 'zancad' => 16, 'gambad' => 16,
    'press' => 35, 'rem' => 35, 'remo' => 35, 'curl' => 12, 'elevacion' => 8, 'elevació' => 8, 'tríceps' => 15,
];

function sim_aleatori($min, $max)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function sim_kg($kg)
{
    return max(2.5, round($kg / 2.5) * 2.5);
}

function sim_factor(array $usuari)
{
    return 0.65 + (crc32('gymzero' . $usuari['id'] . $usuari['email']) % 61) / 100;
}

function sim_repeticions($reps, $v_inicial, array $recorregut)
{
    $perdua = min(0.40, 0.12 + 0.02 * ($reps - 6) + sim_aleatori(0, 0.08));
    $rom = sim_aleatori($recorregut[0], $recorregut[1]);
    $llista = [];
    for ($i = 0; $i < $reps; $i++) {
        $part = $reps > 1 ? $i / ($reps - 1) : 0;
        $v = max(0.12, $v_inicial * (1 - $perdua * pow($part, 1.3)) + sim_aleatori(-0.015, 0.015));
        $r = $rom + sim_aleatori(-0.01, 0.01);
        $llista[] = [round($v, 2), round($r, 2), round($r / $v, 2)];
    }
    return $llista;
}

function sim_primer_dia(PDO $bd, $usuari_id)
{
    $st = $bd->prepare(
        'SELECT LEAST(
                  COALESCE((SELECT MIN(data) FROM entrenaments WHERE usuari_id = :u1), \'9999-12-31\'),
                  COALESCE((SELECT MIN(g.data) FROM registre g JOIN rutina r ON r.id = g.rutina_id WHERE r.usuari_id = :u2), \'9999-12-31\'),
                  COALESCE((SELECT MIN(DATE(data_hora)) FROM entrades WHERE usuari_id = :u3), \'9999-12-31\'))'
    );
    $st->execute([':u1' => $usuari_id, ':u2' => $usuari_id, ':u3' => $usuari_id]);
    $dia = $st->fetchColumn();
    return $dia === '9999-12-31' ? null : $dia;
}

function sim_carrega_setmana($kg_mes_nou, $kg_base, $gran, $factor)
{
    $kg_ara = sim_kg($kg_base * $factor);
    if ($kg_mes_nou === null) {
        return $kg_ara;
    }
    $probabilitat = min(0.6, max(0.08, $kg_mes_nou * ($gran ? 0.012 : 0.007) / 2.5));
    $baixa = mt_rand() / mt_getrandmax() < $probabilitat;
    return max(sim_kg($kg_ara * 0.8), $kg_mes_nou - ($baixa ? 2.5 : 0));
}

function sim_rutina_si_cal(PDO $bd, $usuari_id, array $maquines)
{
    $st = $bd->prepare(
        'SELECT t.nivell, (SELECT COUNT(*) FROM rutina r WHERE r.usuari_id = u.id) AS files
           FROM usuaris u LEFT JOIN tarifes t ON t.id = u.tarifa_id WHERE u.id = ?'
    );
    $st->execute([$usuari_id]);
    $u = $st->fetch();
    if (!$u || (int) $u['nivell'] < 2 || (int) $u['files'] > 0) {
        return false;
    }
    $noms = [];
    foreach ($bd->query('SELECT codi, nom FROM maquines') as $m) {
        $noms[$m['codi']] = trim(str_replace('intel·ligent', '', $m['nom']));
    }
    $nova = $bd->prepare('INSERT INTO rutina (usuari_id, dia, ordre, exercici, series, maquina_id) VALUES (?, ?, ?, ?, ?, ?)');
    foreach (SIM_SETMANA as $dia => $exercicis) {
        $ordre = 0;
        foreach ($exercicis as $codi => $series) {
            if (isset($maquines[$codi])) {
                $nova->execute([$usuari_id, $dia, ++$ordre, $noms[$codi] ?? $codi, $series, $maquines[$codi]]);
            }
        }
    }
    return true;
}

function simula_setmana(PDO $bd, $usuari_id)
{
    $st = $bd->prepare("SELECT id, nom, email, targeta_rfid, actiu FROM usuaris WHERE id = ? AND rol = 'client'");
    $st->execute([$usuari_id]);
    $usuari = $st->fetch();
    if (!$usuari) {
        throw new RuntimeException('Només es poden simular dades de clients.');
    }

    $primer = sim_primer_dia($bd, $usuari_id);
    $dilluns = new DateTime($primer ?? 'today', new DateTimeZone('Europe/Madrid'));
    $dilluns->modify('monday this week')->modify('-1 week');
    if ($dilluns < new DateTime('-2 years')) {
        throw new RuntimeException('Ja té dos anys de dades: no cal simular-ne més.');
    }

    $maquines = [];
    foreach ($bd->query("SELECT id, codi FROM maquines WHERE estat = 'activa'") as $m) {
        $maquines[$m['codi']] = (int) $m['id'];
    }
    $codi_maquina = array_flip($maquines);
    $torn = $bd->query("SELECT id FROM torns WHERE estat = 'activa' ORDER BY id LIMIT 1")->fetchColumn();

    $rutina_nova = sim_rutina_si_cal($bd, $usuari_id, $maquines);

    $st = $bd->prepare('SELECT id, dia, exercici, series, maquina_id FROM rutina WHERE usuari_id = ? ORDER BY dia, ordre, id');
    $st->execute([$usuari_id]);
    $dies = [];
    foreach ($st->fetchAll() as $r) {
        $codi = $r['maquina_id'] ? ($codi_maquina[(int) $r['maquina_id']] ?? null) : null;
        $dies[(int) $r['dia']][] = ['rutina_id' => (int) $r['id'], 'maquina' => $codi, 'nom' => $r['exercici'], 'series' => (int) $r['series']];
    }
    if (!$dies) {
        foreach (SIM_SETMANA as $dia => $exercicis) {
            foreach ($exercicis as $codi => $series) {
                if (isset($maquines[$codi])) {
                    $dies[$dia][] = ['rutina_id' => null, 'maquina' => $codi, 'nom' => $codi, 'series' => $series];
                }
            }
        }
    }
    if (count($dies) > 2 && mt_rand(1, 3) === 1) {
        unset($dies[array_rand($dies)]);
    }
    if (!$dies) {
        throw new RuntimeException('No hi ha cap màquina activa per simular els entrenaments.');
    }

    $factor = sim_factor($usuari);
    $hores = [7.5, 9.5, 13.5, 18.0, 19.5];
    $hora_habitual = $hores[crc32('hora' . $usuari['id']) % count($hores)];

    $st = $bd->prepare(
        'SELECT s.maquina_id, s.carrega_kg FROM series s JOIN entrenaments e ON e.id = s.entrenament_id
          WHERE e.usuari_id = ? AND e.data = (SELECT MIN(e2.data) FROM entrenaments e2 JOIN series s2 ON s2.entrenament_id = e2.id
                                               WHERE e2.usuari_id = e.usuari_id AND s2.maquina_id = s.maquina_id)
          ORDER BY s.num_serie'
    );
    $st->execute([$usuari_id]);
    $antiga_maquina = [];
    foreach ($st as $f) {
        $antiga_maquina[(int) $f['maquina_id']] = $antiga_maquina[(int) $f['maquina_id']] ?? (float) $f['carrega_kg'];
    }
    $st = $bd->prepare(
        'SELECT g.rutina_id, g.carrega_kg FROM registre g JOIN rutina r ON r.id = g.rutina_id
          WHERE r.usuari_id = ? ORDER BY g.data DESC, g.num_serie DESC'
    );
    $st->execute([$usuari_id]);
    $antiga_registre = [];
    foreach ($st as $f) {
        $antiga_registre[(int) $f['rutina_id']] = ['kg' => $f['carrega_kg'] === null ? null : (float) $f['carrega_kg']];
    }

    $bd->beginTransaction();
    try {
        $polsera = $usuari['targeta_rfid'];
        if (!$polsera) {
            $comprova = $bd->prepare('SELECT COUNT(*) FROM usuaris WHERE targeta_rfid = ?');
            do {
                $polsera = strtoupper(bin2hex(random_bytes(4)));
                $comprova->execute([$polsera]);
            } while ($comprova->fetchColumn() > 0);
            $bd->prepare('UPDATE usuaris SET targeta_rfid = ? WHERE id = ?')->execute([$polsera, $usuari_id]);
        }

        $nou_entrenament = $bd->prepare('INSERT INTO entrenaments (usuari_id, data) VALUES (?, ?)');
        $nova_serie = $bd->prepare('INSERT INTO series (entrenament_id, maquina_id, num_serie, carrega_kg, inici) VALUES (?, ?, ?, ?, ?)');
        $nova_rep = $bd->prepare('INSERT INTO repeticions (serie_id, num_rep, velocitat_mitjana, recorregut_m, durada_s) VALUES (?, ?, ?, ?, ?)');
        $nou_registre = $bd->prepare('INSERT INTO registre (rutina_id, data, num_serie, carrega_kg, reps) VALUES (?, ?, ?, ?, ?)');
        $nova_entrada = $bd->prepare(
            "INSERT INTO entrades (torn_id, usuari_id, targeta, data_hora, permesa, motiu) VALUES (?, ?, ?, ?, 1, 'Quota pagada')"
        );

        $total = ['dies' => 0, 'series' => 0];
        $setmana_maquina = [];
        $setmana_registre = [];
        ksort($dies);
        foreach ($dies as $dia => $exercicis) {
            $data = (clone $dilluns)->modify('+' . ($dia - 1) . ' day');
            $minuts = (int) round(($hora_habitual + sim_aleatori(-0.4, 0.4)) * 60);
            $hora = (clone $data)->setTime(intdiv($minuts, 60), $minuts % 60, mt_rand(0, 59));

            if ($torn) {
                $nova_entrada->execute([$torn, $usuari_id, $polsera, $hora->format('Y-m-d H:i:s')]);
            }
            $hora->modify('+' . mt_rand(8, 14) . ' minutes');

            $entrenament_id = null;
            $num_maquina = [];
            foreach ($exercicis as $ex) {
                if ($ex['maquina'] !== null) {
                    [$kg_base, $recorregut, $v_base, $gran] = SIM_MAQUINES[$ex['maquina']] ?? [30, [0.35, 0.45], 0.5, false];
                    $maquina_id = $maquines[$ex['maquina']];
                    $kg = $setmana_maquina[$maquina_id] ??= sim_carrega_setmana($antiga_maquina[$maquina_id] ?? null, $kg_base, $gran, $factor);
                    if ($entrenament_id === null) {
                        $nou_entrenament->execute([$usuari_id, $data->format('Y-m-d')]);
                        $entrenament_id = (int) $bd->lastInsertId();
                    }
                    $reps = mt_rand(9, 12);
                    $v = $v_base + sim_aleatori(-0.05, 0.05);
                    for ($s = 0; $s < $ex['series']; $s++) {
                        $num = $num_maquina[$maquina_id] = ($num_maquina[$maquina_id] ?? 0) + 1;
                        $nova_serie->execute([$entrenament_id, $maquina_id, $num, $kg, $hora->format('Y-m-d H:i:s')]);
                        $serie_id = (int) $bd->lastInsertId();
                        $segons = 0;
                        foreach (sim_repeticions($reps, $v, $recorregut) as $i => [$vel, $rom, $durada]) {
                            $nova_rep->execute([$serie_id, $i + 1, $vel, $rom, $durada]);
                            $segons += $durada * 2.5;
                        }
                        $hora->modify('+' . (int) round($segons + mt_rand(90, 150)) . ' seconds');
                        $reps = max(6, $reps - mt_rand(0, 1));
                        $v -= 0.02;
                        $total['series']++;
                    }
                } else {
                    $abans = $antiga_registre[$ex['rutina_id']] ?? null;
                    $nom = mb_strtolower($ex['nom']);
                    $kg_base = 20;
                    foreach (SIM_LLIURES as $paraula => $kg_paraula) {
                        if (str_contains($nom, $paraula)) {
                            $kg_base = $kg_paraula;
                            break;
                        }
                    }
                    if (!array_key_exists($ex['rutina_id'], $setmana_registre)) {
                        if ($abans !== null) {
                            $setmana_registre[$ex['rutina_id']] = $abans['kg'] === null ? null : max(2.5, $abans['kg'] - (mt_rand(0, 1) ? 2.5 : 0));
                        } else {
                            $setmana_registre[$ex['rutina_id']] = $kg_base === null ? null : sim_kg($kg_base * $factor);
                        }
                    }
                    $kg = $setmana_registre[$ex['rutina_id']];
                    $reps = $kg === null ? mt_rand(6, 12) : mt_rand(8, 12);
                    for ($s = 1; $s <= $ex['series']; $s++) {
                        $nou_registre->execute([$ex['rutina_id'], $data->format('Y-m-d'), $s, $kg, $reps]);
                        $reps = max(5, $reps - mt_rand(0, 1));
                        $total['series']++;
                    }
                    $hora->modify('+' . ($ex['series'] * mt_rand(2, 3)) . ' minutes');
                }
                $hora->modify('+' . mt_rand(1, 3) . ' minutes');
            }
            $total['dies']++;
        }
        $bd->commit();
    } catch (Throwable $e) {
        $bd->rollBack();
        throw $e;
    }

    $diumenge = (clone $dilluns)->modify('+6 day');
    return [
        'nom' => $usuari['nom'],
        'des_de' => $dilluns->format('d/m/Y'),
        'fins_a' => $diumenge->format('d/m/Y'),
        'dies' => $total['dies'],
        'series' => $total['series'],
        'polsera_nova' => $usuari['targeta_rfid'] ? null : $polsera,
        'rutina_nova' => $rutina_nova,
    ];
}
