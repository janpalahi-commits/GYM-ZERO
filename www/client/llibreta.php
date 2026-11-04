<?php
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/llibreta.php';
$t = requereix_tarifa($connexio, 2);

$usuari_id = usuari_actual()['id'];
$de_admin = $t['rol'] === 'admin' && isset($_GET['usuari']);
if ($de_admin) {
    $usuari_id = (int) $_GET['usuari'];
    $st = $connexio->prepare("SELECT nom, cognoms FROM usuaris WHERE id = ? AND rol = 'client'");
    $st->execute([$usuari_id]);
    $client = $st->fetch() ?: pagina_error(404, 'Aquest client no existeix.');
}

$rutina = rutina_client($connexio, $usuari_id);
$series = series_rutina($connexio, $usuari_id, $rutina);

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="llibreta.csv"');
    header('Cache-Control: private, no-store');
    $sortida = fopen('php://output', 'w');
    fwrite($sortida, "\xEF\xBB\xBF");
    fputcsv($sortida, ['Dia de la rutina', 'Exercici', 'Data', 'Sèrie', 'Kg', 'Repeticions', 'Apuntat per'], ';');
    foreach ($rutina as $r) {
        foreach ($series[$r['id']] as $s) {
            fputcsv($sortida, [DIES[$r['dia']], $r['exercici'], date('d/m/Y', strtotime($s['data'])), $s['num'],
                kg($s['kg']) ?? 'pes corporal', $s['reps'], $s['origen'] === 'maquina' ? 'Polsera' : 'A mà'], ';');
        }
    }
    exit;
}

$dies = array_values(array_unique(array_map('intval', array_column($rutina, 'dia'))));
$dia = (int) ($_GET['dia'] ?? date('N'));
if (!in_array($dia, $dies, true)) {
    $dia = $dies[0] ?? 1;
}
$files = array_filter($rutina, fn($r) => (int) $r['dia'] === $dia);

$setmanes = setmanes_llibreta(8);
$avui = date('Y-m-d');
$caselles = [];
$records = [];
foreach ($files as $r) {
    $records[$r['id']] = record_fila($series[$r['id']]);
    foreach ($series[$r['id']] as $s) {
        $dilluns = date('Y-m-d', strtotime($s['data'] . ' -' . (date('N', strtotime($s['data'])) - 1) . ' days'));
        $caselles[$r['id']][$dilluns][] = $s;
    }
}

$enllac = fn($params) => '?' . http_build_query(($de_admin ? ['usuari' => $usuari_id] : []) + $params);

$titol = $de_admin ? 'Llibreta de ' . $client['nom'] . ' ' . $client['cognoms'] : 'La meva llibreta';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><?= $de_admin ? '<a href="../admin/client.php?id=' . $usuari_id . '">&larr; Fitxa del client</a>' : '<a href="./">&larr; La meva àrea</a>' ?></p>

<?php if (!$rutina): ?>
  <p>Encara no hi ha rutina. Quan l'entrenador la prepari a partir del qüestionari, sortirà aquí amb els exercicis de cada dia.</p>
<?php else: ?>
  <p>Totes les sèries, setmana a setmana. Les de les màquines amb polsera s'apunten soles; les altres les apuntes tu.</p>
  <div class="accions-llibreta">
    <?php if (!$de_admin): ?><a class="btn" href="apunta.php?dia=<?= $dia ?>">+ Apuntar una sèrie</a><?php endif; ?>
    <a class="btn btn-secundari" href="<?= h($enllac(['csv' => 1])) ?>">Descarregar (Excel)</a>
  </div>

  <nav class="dies" aria-label="Dia de la rutina">
    <?php foreach ($dies as $d): ?>
      <a href="<?= h($enllac(['dia' => $d])) ?>"<?= $d === $dia ? ' aria-current="page"' : '' ?>><?= DIES[$d] ?></a>
    <?php endforeach; ?>
  </nav>

  <p class="llegenda-llibreta">
    <span><span class="etiqueta verda">Polsera</span> l'ha apuntat la màquina</span>
    <span><span class="etiqueta">A mà</span> l'ha apuntat el client</span>
    <span><b class="rec">Vermell</b> el rècord (1RM estimat més alt)</span>
  </p>

  <div class="taula">
    <table class="full">
      <thead>
        <tr>
          <th class="ex" scope="col">Exercici</th>
          <?php foreach ($setmanes as $i => $dilluns): ?>
            <th scope="col">Setm. <?= $i + 1 ?><small><?= date('d/m', strtotime($dilluns . ' +' . ($dia - 1) . ' days')) ?></small></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($files as $r):
            $rec = $records[$r['id']]; ?>
          <tr>
            <th class="ex" scope="row">
              <b><?= h($r['exercici']) ?></b>
              <span class="subtil"><?= (int) $r['series'] ?> sèries<?= $r['maquina'] ? ' · ' . h($r['maquina']) : '' ?><?= $r['nota'] ? ' · ' . h($r['nota']) : '' ?></span>
              <?php if ($r['maquina']): ?>
                <span class="etiqueta verda">Polsera</span>
              <?php else: ?>
                <span class="etiqueta">A mà</span>
                <?php if (!$de_admin): ?><a class="apunta" href="apunta.php?r=<?= (int) $r['id'] ?>">Apuntar</a><?php endif; ?>
              <?php endif; ?>
            </th>
            <?php foreach ($setmanes as $dilluns):
                $ss = $caselles[$r['id']][$dilluns] ?? [];
                $te_record = $rec && in_array($rec, $ss, true);
                $dates = array_unique(array_column($ss, 'data')); ?>
              <?php if (!$ss): ?>
                <td class="buida"><?= date('Y-m-d', strtotime($dilluns . ' +' . ($dia - 1) . ' days')) > $avui ? 'Pendent' : '—' ?></td>
              <?php else: ?>
                <td<?= $te_record ? ' class="te-record"' : '' ?>>
                  <?php foreach ($ss as $k => $s): ?>
                    <?php if (count($dates) > 1 && ($k === 0 || $ss[$k - 1]['data'] !== $s['data'])): ?>
                      <small><?= date('d/m', strtotime($s['data'])) ?></small>
                    <?php endif; ?>
                    <span class="set<?= $s === $rec ? ' rec' : '' ?>"><?= text_serie($s) ?></span>
                  <?php endforeach; ?>
                  <?php if ($te_record): ?><span class="etiqueta vermella">Rècord</span><?php endif; ?>
                </td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="subtil">Cada casella és el que s'ha fet aquella setmana: una línia per sèrie, amb el pes i les repeticions. En el mòbil, llisca la taula cap a l'esquerra per veure les setmanes anteriors.</p>
  <script>
    document.querySelector('.taula').scrollLeft = 99999;
  </script>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
