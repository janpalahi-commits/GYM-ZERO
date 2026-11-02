<?php
// Fitxa d'un client per a l'entrenador: tarifa i pagaments, respostes del qüestionari,
// el PDF del pla, la rutina de la llibreta i, si té la tarifa de seguiment, les revisions i els missatges.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/pagaments.php';
require_once __DIR__ . '/../inc/questionari.php';
require_once __DIR__ . '/../inc/llibreta.php';
require_once __DIR__ . '/../inc/seguiment.php';
requereix_admin($connexio);

$jo = usuari_actual()['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $connexio->prepare(
    "SELECT u.id, u.nom, u.cognoms, u.email, u.quota_fins, u.quota_fins >= CURDATE() AS al_dia, t.nom AS tarifa, t.nivell
       FROM usuaris u JOIN tarifes t ON t.id = u.tarifa_id WHERE u.id = ? AND u.rol = 'client'"
);
$st->execute([$id]);
$c = $st->fetch() ?: pagina_error(404, 'Aquest client no existeix.');

$st = $connexio->prepare('SELECT respostes, enviat FROM questionaris WHERE usuari_id = ? ORDER BY enviat DESC, id DESC LIMIT 1');
$st->execute([$id]);
$questionari = $st->fetch();

// Les respostes en JSON, per passar-les a l'eina de Python que fa el PDF del pla (carpeta plans/)
if (isset($_GET['json'])) {
    if (!$questionari) {
        pagina_error(404, 'Aquest client encara no ha enviat el qüestionari.');
    }
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="questionari_' . $id . '.json"');
    header('Cache-Control: private, no-store');
    // Com una fila de la fulla de Google d'APP GYM: hi afegim el que la web no pregunta (nom i lloc)
    $fila = ['Fecha de envío' => date('d/m/Y H:i:s', strtotime($questionari['enviat']))]
        + json_decode($questionari['respostes'], true)
        + array_replace(questionari()['fixos'], ['Nombre y apellido' => $c['nom'] . ' ' . $c['cognoms']]);
    echo json_encode(['client' => $c['nom'], 'id' => $id, 'enviat' => $questionari['enviat'], 'respostes' => $fila],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Avís a l'àrea del client quan l'entrenador li respon o li canvia alguna cosa
function avisa_client(PDO $bd, $usuari_id, $text)
{
    $bd->prepare("INSERT INTO notificacions (usuari_id, tipus, missatge) VALUES (?, 'sistema', ?)")->execute([$usuari_id, $text]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $accio = $_POST['accio'] ?? '';
    $torna = '';

    if ($accio === 'pujar_pla') {
        $f = $_FILES['pdf'] ?? null;
        $error = match (true) {
            !$f || $f['error'] === UPLOAD_ERR_NO_FILE => 'Tria el PDF del pla.',
            in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > 10 * 1024 * 1024 => 'El PDF no pot passar de 10 MB.',
            $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']) => 'No s\'ha pogut pujar el fitxer. Torna-ho a provar.',
            file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-' => 'El fitxer no és un PDF.',
            default => null,
        };
        if ($error) {
            avis('error', $error);
        } else {
            $nom = mb_substr(preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name'])), 0, 120);
            $st = $connexio->prepare('INSERT INTO plans (usuari_id, nom_fitxer, pdf, pujat_per) VALUES (?, ?, ?, ?)');
            $st->bindValue(1, $id, PDO::PARAM_INT);
            $st->bindValue(2, str_ends_with(strtolower($nom), '.pdf') ? $nom : $nom . '.pdf');
            $st->bindValue(3, file_get_contents($f['tmp_name']), PDO::PARAM_LOB);
            $st->bindValue(4, $jo, PDO::PARAM_INT);
            $st->execute();
            avisa_client($connexio, $id, 'Ja tens el pla nou. El pots descarregar a La meva àrea.');
            avis('ok', 'Pla pujat. Al client li surt un avís.');
        }
        $torna = '#pla';
    } elseif ($accio === 'afegir_exercici') {
        $dia = (int) ($_POST['dia'] ?? 0);
        $exercici = trim($_POST['exercici'] ?? '');
        $series = (int) ($_POST['series'] ?? 0);
        $maquina = (int) ($_POST['maquina_id'] ?? 0) ?: null;
        $nota = trim($_POST['nota'] ?? '');
        if (!isset(DIES[$dia]) || $exercici === '' || mb_strlen($exercici) > 80 || $series < 1 || $series > 10 || mb_strlen($nota) > 120) {
            avis('error', 'Revisa l\'exercici: cal el dia, el nom (màxim 80 caràcters) i d\'1 a 10 sèries.');
        } else {
            $connexio->prepare(
                'INSERT INTO rutina (usuari_id, dia, ordre, exercici, series, maquina_id, nota)
                 SELECT ?, ?, COALESCE(MAX(ordre), 0) + 1, ?, ?, ?, ? FROM rutina WHERE usuari_id = ? AND dia = ?'
            )->execute([$id, $dia, $exercici, $series, $maquina, $nota === '' ? null : $nota, $id, $dia]);
            avis('ok', $exercici . ' afegit al ' . strtolower(DIES[$dia]) . '.');
        }
        $torna = '#rutina';
    } elseif ($accio === 'treure_exercici') {
        // També s'esborra el que el client hi havia apuntat a mà (ON DELETE CASCADE); les sèries de màquina no es toquen
        $connexio->prepare('DELETE FROM rutina WHERE id = ? AND usuari_id = ?')->execute([(int) ($_POST['rutina_id'] ?? 0), $id]);
        avis('ok', 'Exercici tret de la rutina.');
        $torna = '#rutina';
    } elseif ($accio === 'avisar_rutina') {
        avisa_client($connexio, $id, 'L\'entrenador t\'ha canviat la rutina. Mira-la a la llibreta.');
        avis('ok', 'Avís enviat al client.');
        $torna = '#rutina';
    } elseif ($accio === 'respondre_revisio') {
        $resposta = trim($_POST['resposta'] ?? '');
        if ($resposta === '' || mb_strlen($resposta) > 2000) {
            avis('error', 'La resposta no pot estar buida ni passar de 2000 caràcters.');
        } else {
            $connexio->prepare('UPDATE revisions SET resposta = ?, respost_per = ?, respost = NOW() WHERE id = ? AND usuari_id = ?')
                     ->execute([$resposta, $jo, (int) ($_POST['revisio_id'] ?? 0), $id]);
            avisa_client($connexio, $id, 'L\'entrenador ha respost la teva revisió.');
            avis('ok', 'Resposta enviada.');
        }
        $torna = '#seguiment';
    } elseif ($accio === 'esborrar_dades_pla' && !empty($_POST['confirmo'])) {
        // El client retira el consentiment o deixa el pla: fora les dades de salut i el seguiment (la rutina i la llibreta es queden)
        foreach (['questionaris', 'plans', 'revisions', 'missatges'] as $taula) {
            $connexio->prepare("DELETE FROM $taula WHERE usuari_id = ?")->execute([$id]);
        }
        avis('ok', 'Qüestionari, plans, revisions i missatges esborrats.');
    } elseif ($accio === 'missatge') {
        $error = envia_missatge($connexio, $id, $jo, $_POST['text'] ?? '');
        if (!$error) {
            avisa_client($connexio, $id, 'Tens un missatge nou de l\'entrenador.');
        }
        avis($error ? 'error' : 'ok', $error ?? 'Missatge enviat.');
        $torna = '#missatges';
    }

    header('Location: client.php?id=' . $id . $torna);
    exit;
}

$st = $connexio->prepare(
    'SELECT p.data, p.mesos, p.import, p.fins, t.nom AS tarifa, u.nom AS cobrat_per
       FROM pagaments p JOIN tarifes t ON t.id = p.tarifa_id LEFT JOIN usuaris u ON u.id = p.cobrat_per
      WHERE p.usuari_id = ? ORDER BY p.data DESC, p.id DESC LIMIT 6'
);
$st->execute([$id]);
$pagaments = $st->fetchAll();

$st = $connexio->prepare('SELECT id, nom_fitxer, pujat, LENGTH(pdf) AS mida FROM plans WHERE usuari_id = ? ORDER BY pujat DESC, id DESC');
$st->execute([$id]);
$plans = $st->fetchAll();

$rutina = rutina_client($connexio, $id);
$maquines = $connexio->query("SELECT id, codi, nom FROM maquines WHERE estat <> 'baixa' ORDER BY codi")->fetchAll();

$revisions = revisions_client($connexio, $id);
$missatges = missatges_client($connexio, $id);
// L'entrenador ha llegit els missatges del client
$connexio->prepare('UPDATE missatges SET llegit = 1 WHERE usuari_id = ? AND autor_id = ?')->execute([$id, $id]);

$respostes = $questionari ? json_decode($questionari['respostes'], true) : null;

$titol = $c['nom'] . ' ' . $c['cognoms'];
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="plans.php">&larr; Plans i seguiment</a> · <a href="usuaris.php">Usuaris</a></p>
<p><?= h($c['email']) ?> · Tarifa <b><?= h($c['tarifa']) ?></b> ·
  <?= $c['al_dia'] ? 'quota pagada fins al ' . date('d/m/Y', strtotime($c['quota_fins'])) : '<span class="etiqueta vermella">Quota no pagada</span>' ?></p>
<?php if ($c['nivell'] < 2): ?>
  <div class="error">Amb la tarifa <?= h($c['tarifa']) ?> el client no veu el pla, la llibreta ni el seguiment. Per donar-los-hi, cobra-li una tarifa més alta a <a href="usuaris.php">Usuaris</a>.</div>
<?php endif; ?>

<?php if ($pagaments): ?>
  <h2>Pagaments</h2>
  <table>
    <tr><th>Dia</th><th>Tarifa</th><th>Import</th><th class="amaga-mobil">Paga fins al</th><th class="amaga-mobil">Cobrat per</th></tr>
    <?php foreach ($pagaments as $p): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($p['data'])) ?></td>
        <td><?= h($p['tarifa']) ?>, <?= (int) $p['mesos'] ?> <?= $p['mesos'] == 1 ? 'mes' : 'mesos' ?></td>
        <td><?= euros($p['import']) ?></td>
        <td class="amaga-mobil"><?= date('d/m/Y', strtotime($p['fins'])) ?></td>
        <td class="amaga-mobil"><?= h($p['cobrat_per'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<h2 id="questionari">Qüestionari</h2>
<?php if (!$respostes): ?>
  <p>Encara no l'ha enviat.</p>
<?php else: ?>
  <p class="subtil">Enviat el <?= date('d/m/Y H:i', strtotime($questionari['enviat'])) ?>. Conté dades de salut: només per fer el pla i el seguiment d'aquest client.</p>
  <?php foreach (avisos_questionari($respostes) as $a): ?>
    <div class="error"><?= h($a) ?></div>
  <?php endforeach; ?>
  <p class="subtil">L'ha omplert en <?= h($respostes['Idioma del formulario'] ?? '—') ?>. Aquí surt en català i només el que ha respost.</p>
  <table class="respostes">
    <?php foreach (pagines_questionari() as $p):
        $files = [];
        foreach ($p['preguntes'] as $i) {
            $q = questionari()['q'][$i];
            if ($q['type'] !== 'header' && ($text = resposta_llegible($q, $respostes)) !== '') {
                $files[] = [tq($q['title'], 'ca'), $text];
            }
        }
        if (!$files) continue; ?>
      <tr class="dia"><th colspan="2"><?= h(tq($p['titol'] ?? null, 'ca') ?: 'Dades bàsiques') ?></th></tr>
      <?php foreach ($files as [$pregunta, $text]): ?>
        <tr><td><?= h($pregunta) ?></td><td><?= nl2br(h($text)) ?></td></tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </table>
  <a class="btn btn-secundari" href="?id=<?= $id ?>&amp;json=1">Descarregar les respostes (JSON per a l'eina del pla)</a>
<?php endif; ?>

<h2 id="pla">Pla en PDF</h2>
<?php if ($plans): ?>
  <table>
    <tr><th>Pujat</th><th>Fitxer</th><th></th></tr>
    <?php foreach ($plans as $i => $p): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($p['pujat'])) ?></td>
        <td><?= h($p['nom_fitxer']) ?> <span class="subtil">(<?= number_format($p['mida'] / 1024, 0, ',', '.') ?> kB)</span></td>
        <td><?= $i === 0 ? '<a href="../client/pla.php?usuari=' . $id . '">Descarregar</a> <span class="etiqueta verda">El que veu el client</span>' : '' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p>Encara no té cap pla.</p>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="quota">
  <?= camp_csrf() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <input type="hidden" name="accio" value="pujar_pla">
  <input type="file" name="pdf" accept="application/pdf" required aria-label="PDF del pla">
  <button>Pujar el pla</button>
</form>

<h2 id="rutina">Rutina de la llibreta</h2>
<p class="subtil">El que el client veu a la llibreta. Si l'exercici és d'una màquina amb polsera, les sèries s'hi apunten soles.
  <a href="../client/llibreta.php?usuari=<?= $id ?>">Veure la llibreta</a></p>
<?php if ($rutina): ?>
  <table>
    <?php $dia_anterior = null;
    foreach ($rutina as $r): ?>
      <?php if ($r['dia'] !== $dia_anterior): $dia_anterior = $r['dia']; ?>
        <tr class="dia"><th colspan="4"><?= DIES[$r['dia']] ?></th></tr>
      <?php endif; ?>
      <tr>
        <td><?= h($r['exercici']) ?><?= $r['nota'] ? ' <span class="subtil">· ' . h($r['nota']) . '</span>' : '' ?></td>
        <td><?= (int) $r['series'] ?> sèries</td>
        <td><?= $r['maquina'] ? '<span class="etiqueta verda">' . h($r['maquina']) . '</span>' : '<span class="etiqueta">A mà</span>' ?></td>
        <td>
          <form method="POST">
            <?= camp_csrf() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="accio" value="treure_exercici">
            <input type="hidden" name="rutina_id" value="<?= (int) $r['id'] ?>">
            <button class="btn-cancel">Treure</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<form method="POST">
  <?= camp_csrf() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <input type="hidden" name="accio" value="afegir_exercici">
  <div class="fila-camps">
    <div>
      <label for="dia">Dia</label>
      <select id="dia" name="dia"><?php foreach (DIES as $n => $nom): ?><option value="<?= $n ?>"><?= $nom ?></option><?php endforeach; ?></select>
    </div>
    <div>
      <label for="exercici">Exercici</label>
      <input id="exercici" name="exercici" maxlength="80" required>
    </div>
    <div>
      <label for="series">Sèries</label>
      <input id="series" name="series" type="number" min="1" max="10" value="3" required>
    </div>
  </div>
  <div class="fila-camps">
    <div>
      <label for="maquina_id">Màquina amb polsera</label>
      <select id="maquina_id" name="maquina_id">
        <option value="">Cap (s'apunta a mà)</option>
        <?php foreach ($maquines as $m): ?><option value="<?= (int) $m['id'] ?>"><?= h($m['codi'] . ' · ' . $m['nom']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="nota">Nota <span class="subtil">(opcional)</span></label>
      <input id="nota" name="nota" maxlength="120" placeholder="ex.: banc a 30°">
    </div>
  </div>
  <button>Afegir a la rutina</button>
</form>
<?php if ($rutina): ?>
  <form method="POST">
    <?= camp_csrf() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="accio" value="avisar_rutina">
    <button class="btn-secundari">Avisar el client que li he canviat la rutina</button>
  </form>
<?php endif; ?>

<?php if ($c['nivell'] >= 3 || $revisions || $missatges): ?>
  <h2 id="seguiment">Revisions</h2>
  <?php if (!$revisions): ?>
    <p>Encara no n'ha enviat cap.</p>
  <?php endif; ?>
  <?php foreach ($revisions as $r): ?>
    <article class="revisio">
      <p><b><?= date('d/m/Y', strtotime($r['data'])) ?></b><?= $r['pes_kg'] !== null ? ' · ' . str_replace('.', ',', (float) $r['pes_kg']) . ' kg' : '' ?></p>
      <p><?= nl2br(h($r['com_va'])) ?></p>
      <?php if ($r['resposta'] !== null): ?>
        <div class="resposta"><b><?= h($r['entrenador'] ?? 'Entrenador') ?>:</b> <?= nl2br(h($r['resposta'])) ?></div>
      <?php else: ?>
        <form method="POST">
          <?= camp_csrf() ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="accio" value="respondre_revisio">
          <input type="hidden" name="revisio_id" value="<?= (int) $r['id'] ?>">
          <label for="resposta-<?= (int) $r['id'] ?>">Resposta</label>
          <textarea id="resposta-<?= (int) $r['id'] ?>" name="resposta" maxlength="2000" required></textarea>
          <button>Respondre</button>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>

  <h2 id="missatges">Missatges</h2>
  <?= mostra_conversa($missatges, $jo) ?>
  <form method="POST">
    <?= camp_csrf() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="accio" value="missatge">
    <label for="text">Escriu a <?= h($c['nom']) ?></label>
    <textarea id="text" name="text" maxlength="1000" required></textarea>
    <button>Enviar</button>
  </form>
<?php endif; ?>

<?php if ($questionari || $plans || $revisions || $missatges): ?>
  <h2>Esborrar les dades del pla</h2>
  <p class="subtil">Si el client retira el consentiment o deixa la tarifa amb pla i ho demana. S'esborren el qüestionari, els PDF, les revisions i els missatges. El compte, els pagaments i la llibreta es queden.</p>
  <form method="POST" class="quota">
    <?= camp_csrf() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="accio" value="esborrar_dades_pla">
    <label class="opcio"><input type="checkbox" name="confirmo" value="1" required> Ho ha demanat el client</label>
    <button class="btn-cancel">Esborrar</button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
