<?php
// El meu entrenament: les sèries que han enviat les màquines (o el simulador)
// i com evoluciona el client a cada màquina.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_login();

$u = usuari_actual();

// Xifres dels últims 30 dies
$st = $connexio->prepare(
    'SELECT COUNT(DISTINCT e.id) AS dies, COUNT(s.id) AS series,
            COALESCE(SUM(s.carrega_kg * (SELECT COUNT(*) FROM repeticions r WHERE r.serie_id = s.id)), 0) AS kg_totals
       FROM entrenaments e JOIN series s ON s.entrenament_id = e.id
      WHERE e.usuari_id = ? AND e.data >= CURDATE() - INTERVAL 30 DAY'
);
$st->execute([$u['id']]);
$xifres = $st->fetch();

// Evolució: per a cada màquina i dia, la càrrega màxima i com de ràpid s'ha mogut
$st = $connexio->prepare(
    'SELECT m.nom, v.data, MAX(v.carrega_kg) AS carrega_max,
            ROUND(AVG(v.velocitat_mitjana), 2) AS velocitat, ROUND(AVG(v.perdua_velocitat_pct), 1) AS perdua
       FROM v_resum_series v JOIN maquines m ON m.codi = v.maquina
      WHERE v.usuari_id = ? AND v.data >= CURDATE() - INTERVAL 90 DAY
      GROUP BY m.nom, v.data
      ORDER BY m.nom, v.data'
);
$st->execute([$u['id']]);
$evolucio = [];
foreach ($st->fetchAll() as $fila) {
    $evolucio[$fila['nom']][] = $fila;
}

// Historial: les sèries dels últims 10 dies que ha entrenat
$st = $connexio->prepare('SELECT data FROM entrenaments WHERE usuari_id = ? ORDER BY data DESC LIMIT 10');
$st->execute([$u['id']]);
$dies = $st->fetchAll(PDO::FETCH_COLUMN);

$historial = [];
if ($dies) {
    $st = $connexio->prepare(
        'SELECT v.data, m.nom, v.num_serie, v.carrega_kg, v.repeticions, v.velocitat_mitjana, v.perdua_velocitat_pct
           FROM v_resum_series v JOIN maquines m ON m.codi = v.maquina
          WHERE v.usuari_id = ? AND v.data >= ?
          ORDER BY v.data DESC, m.nom, v.num_serie'
    );
    $st->execute([$u['id'], end($dies)]);
    foreach ($st->fetchAll() as $fila) {
        $historial[$fila['data']][] = $fila;
    }
}

// 67.5 -> "67,5" (amb coma, com s'escriu en català)
function kg($valor)
{
    return str_replace('.', ',', (string) (float) $valor);
}

// Com de dura ha estat una sèrie, segons quanta velocitat s'ha perdut
// (valors orientatius, els que se solen fer servir en entrenament per velocitat)
function esforc($perdua)
{
    if ($perdua < 20) {
        return '<span class="etiqueta verda">Suau</span>';
    }
    if ($perdua < 35) {
        return '<span class="etiqueta">Moderada</span>';
    }
    return '<span class="etiqueta vermella">Molt exigent</span>';
}

// Gràfic de línia en SVG de la càrrega màxima de cada dia (sense cap llibreria)
function grafic(array $punts)
{
    $amplada = 600;
    $alcada = 150;
    $marge = 50;
    $valors = array_map(fn($p) => (float) $p['carrega_max'], $punts);
    $min = min($valors) - 5;
    $max = max($valors) + 5;
    $n = count($punts);

    $coords = [];
    foreach ($punts as $i => $p) {
        $x = $n > 1 ? $marge + $i * ($amplada - 2 * $marge) / ($n - 1) : $amplada / 2;
        $y = $alcada - $marge - ((float) $p['carrega_max'] - $min) / ($max - $min) * ($alcada - 2 * $marge);
        $coords[] = [round($x), round($y), $p];
    }

    $svg = '<svg class="grafic" viewBox="0 0 ' . $amplada . ' ' . $alcada . '" role="img" aria-label="Evolució de la càrrega màxima">';
    $svg .= '<polyline points="' . implode(' ', array_map(fn($c) => $c[0] . ',' . $c[1], $coords)) . '" />';
    foreach ($coords as [$x, $y, $p]) {
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="5" />';
        $svg .= '<text x="' . $x . '" y="' . ($y - 10) . '" class="valor">' . kg($p['carrega_max']) . ' kg</text>';
        $svg .= '<text x="' . $x . '" y="' . ($alcada - 8) . '">' . date('d/m', strtotime($p['data'])) . '</text>';
    }
    return $svg . '</svg>';
}

$titol = 'El meu entrenament';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar a la meva àrea</a></p>

<?php if (!$historial): ?>
  <p>Encara no tens cap entrenament registrat. Quan facis servir una màquina intel·ligent amb la teva polsera, les sèries sortiran aquí.</p>
<?php else: ?>
  <div class="xifres">
    <div class="xifra"><b><?= (int) $xifres['dies'] ?></b><span>dies entrenats (30 dies)</span></div>
    <div class="xifra"><b><?= (int) $xifres['series'] ?></b><span>sèries (30 dies)</span></div>
    <div class="xifra"><b><?= number_format((float) $xifres['kg_totals'], 0, ',', '.') ?></b><span>kg aixecats (30 dies)</span></div>
  </div>

  <h2>Evolució per màquina</h2>
  <p class="subtil">Càrrega més alta de cada dia (últims 90 dies).</p>
  <?php foreach ($evolucio as $maquina => $punts): ?>
    <h3><?= h($maquina) ?></h3>
    <?= grafic($punts) ?>
  <?php endforeach; ?>

  <h2>Historial</h2>
  <p class="subtil">La pèrdua de velocitat és quant baixa la velocitat de la barra des de la repetició més ràpida fins a l'última. Com més alta, més cansat acabes la sèrie.</p>
  <?php foreach ($historial as $dia => $series): ?>
    <h3><?= date('d/m/Y', strtotime($dia)) ?></h3>
    <table>
      <tr><th>Màquina</th><th>Sèrie</th><th>Càrrega</th><th>Reps</th><th class="amaga-mobil">Vel. mitjana</th><th>Pèrdua</th><th class="amaga-mobil"></th></tr>
      <?php foreach ($series as $s): ?>
        <tr>
          <td><?= h($s['nom']) ?></td>
          <td><?= (int) $s['num_serie'] ?></td>
          <td><?= kg($s['carrega_kg']) ?> kg</td>
          <td><?= (int) $s['repeticions'] ?></td>
          <td class="amaga-mobil"><?= number_format((float) $s['velocitat_mitjana'], 2, ',', '') ?> m/s</td>
          <td><?= number_format((float) $s['perdua_velocitat_pct'], 1, ',', '') ?>%</td>
          <td class="amaga-mobil"><?= esforc((float) $s['perdua_velocitat_pct']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
