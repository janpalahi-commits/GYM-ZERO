<?php
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/llibreta.php';
requereix_tarifa($connexio, 2);

$u = usuari_actual();
$errors = [];

$st = $connexio->prepare('SELECT * FROM rutina WHERE usuari_id = ? AND maquina_id IS NULL ORDER BY dia, ordre, id');
$st->execute([$u['id']]);
$manuals = array_column($st->fetchAll(), null, 'id');

$r = $manuals[(int) ($_POST['r'] ?? $_GET['r'] ?? 0)] ?? null;

$data = $_POST['data'] ?? $_GET['data'] ?? date('Y-m-d');
$dt = DateTime::createFromFormat('!Y-m-d', $data);
if (!$dt || $dt->format('Y-m-d') !== $data || $data > date('Y-m-d') || $data < date('Y-m-d', strtotime('-60 days'))) {
    $errors[] = 'La data ha de ser d\'avui o dels últims 60 dies.';
    $data = date('Y-m-d');
}

if ($r && $_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    comprova_csrf();
    $valors = [];
    $post_reps = (array) ($_POST['reps'] ?? []);
    $post_kg = (array) ($_POST['kg'] ?? []);
    for ($i = 1; $i <= $r['series']; $i++) {
        $reps = trim((string) ($post_reps[$i] ?? ''));
        $carrega = trim(str_replace(',', '.', (string) ($post_kg[$i] ?? '')));
        if ($reps === '') {
            $valors[$i] = null;
            continue;
        }
        $reps = filter_var($reps, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        $carrega = $carrega === '' ? null : filter_var($carrega, FILTER_VALIDATE_FLOAT);
        if ($reps === false || $carrega === false || ($carrega !== null && ($carrega < 0 || $carrega > 500))) {
            $errors[] = "Sèrie $i: les repeticions van d'1 a 100 i el pes de 0 a 500 kg (buit si és amb el pes del cos).";
            continue;
        }
        $valors[$i] = [$carrega === null ? null : round($carrega, 1), $reps];
    }
    if (!$errors) {
        $desa = $connexio->prepare(
            'INSERT INTO registre (rutina_id, data, num_serie, carrega_kg, reps) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE carrega_kg = VALUES(carrega_kg), reps = VALUES(reps)'
        );
        $esborra = $connexio->prepare('DELETE FROM registre WHERE rutina_id = ? AND data = ? AND num_serie = ?');
        $connexio->beginTransaction();
        foreach ($valors as $i => $v) {
            $v ? $desa->execute([$r['id'], $data, $i, $v[0], $v[1]]) : $esborra->execute([$r['id'], $data, $i]);
        }
        $connexio->prepare('DELETE FROM registre WHERE rutina_id = ? AND data = ? AND num_serie > ?')
                 ->execute([$r['id'], $data, $r['series']]);
        $connexio->commit();
        $fetes = count(array_filter($valors));
        avis('ok', $r['exercici'] . ': ' . ($fetes ? $fetes . ($fetes === 1 ? ' sèrie apuntada' : ' sèries apuntades') : 'sèries esborrades')
            . ' el ' . date('d/m/Y', strtotime($data)) . '.');
        header('Location: llibreta.php?dia=' . (int) $r['dia']);
        exit;
    }
}

$apuntat = [];
$ultima = [];
if ($r) {
    $st = $connexio->prepare('SELECT num_serie, carrega_kg, reps FROM registre WHERE rutina_id = ? AND data = ?');
    $st->execute([$r['id'], $data]);
    foreach ($st as $f) {
        $apuntat[$f['num_serie']] = ['kg' => $f['carrega_kg'] === null ? null : (float) $f['carrega_kg'], 'reps' => (int) $f['reps']];
    }
    $st = $connexio->prepare(
        'SELECT data, num_serie, carrega_kg, reps FROM registre
          WHERE rutina_id = ? AND data = (SELECT MAX(data) FROM registre WHERE rutina_id = ? AND data < ?)
          ORDER BY num_serie'
    );
    $st->execute([$r['id'], $r['id'], $data]);
    foreach ($st as $f) {
        $ultima[] = ['data' => $f['data'], 'kg' => $f['carrega_kg'] === null ? null : (float) $f['carrega_kg'], 'reps' => (int) $f['reps']];
    }
}

$titol = $r ? $r['exercici'] : 'Apuntar una sèrie';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="llibreta.php<?= $r ? '?dia=' . (int) $r['dia'] : '' ?>">&larr; La meva llibreta</a></p>

<?php foreach ($errors as $e): ?>
  <div class="error"><?= h($e) ?></div>
<?php endforeach; ?>

<?php if (!$manuals): ?>
  <p>La teva rutina no té exercicis per apuntar a mà: totes les sèries les apunten les màquines amb la polsera.</p>
<?php elseif (!$r):
    $dia = (int) ($_GET['dia'] ?? date('N')); ?>
  <p>Tria l'exercici. Els de màquina amb polsera no surten aquí perquè s'apunten sols.</p>
  <table>
    <?php foreach ($manuals as $m): ?>
      <tr<?= (int) $m['dia'] === $dia ? ' class="avui"' : '' ?>>
        <td><?= DIES[$m['dia']] ?></td>
        <td><a href="?r=<?= (int) $m['id'] ?>"><?= h($m['exercici']) ?></a></td>
        <td class="subtil"><?= (int) $m['series'] ?> sèries<?= $m['nota'] ? ' · ' . h($m['nota']) : '' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p class="subtil"><?= DIES[$r['dia']] ?> · <?= (int) $r['series'] ?> sèries<?= $r['nota'] ? ' · ' . h($r['nota']) : '' ?></p>
  <?php if ($ultima): ?>
    <p>L'última vegada (<?= date('d/m', strtotime($ultima[0]['data'])) ?>): <span class="set-linia"><?= h(implode(' · ', array_map('text_serie', $ultima))) ?></span></p>
  <?php endif; ?>

  <form method="GET" class="quota">
    <input type="hidden" name="r" value="<?= (int) $r['id'] ?>">
    <label for="data">Dia</label>
    <input type="date" id="data" name="data" value="<?= h($data) ?>" max="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d', strtotime('-60 days')) ?>">
    <button class="btn-secundari">Canviar el dia</button>
  </form>

  <form method="POST" class="apunta">
    <?= camp_csrf() ?>
    <input type="hidden" name="r" value="<?= (int) $r['id'] ?>">
    <input type="hidden" name="data" value="<?= h($data) ?>">
    <table>
      <tr><th>Sèrie</th><th>Pes (kg)</th><th>Repeticions</th></tr>
      <?php for ($i = 1; $i <= $r['series']; $i++):
          $v = $apuntat[$i] ?? null; ?>
        <tr>
          <td><?= $i ?></td>
          <td><input name="kg[<?= $i ?>]" inputmode="decimal" value="<?= h(kg($v['kg'] ?? null)) ?>" placeholder="<?= h(kg($ultima[$i - 1]['kg'] ?? null) ?? '') ?>" aria-label="Pes de la sèrie <?= $i ?>"></td>
          <td><input name="reps[<?= $i ?>]" type="number" min="1" max="100" value="<?= h($v['reps'] ?? '') ?>" placeholder="<?= h($ultima[$i - 1]['reps'] ?? '') ?>" aria-label="Repeticions de la sèrie <?= $i ?>"></td>
        </tr>
      <?php endfor; ?>
    </table>
    <p class="subtil">Si és amb el pes del cos (fons, dominades...), deixa el pes buit. Una sèrie sense repeticions no es desa.</p>
    <button type="submit">Desar el <?= date('d/m', strtotime($data)) ?></button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
