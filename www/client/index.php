<?php
// Àrea privada del client: les seves reserves i els seus avisos.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_login();

$u = usuari_actual();

$st = $connexio->prepare(
    "SELECT c.id, a.nom, c.inici
       FROM reserves r
       JOIN classes c    ON c.id = r.classe_id
       JOIN activitats a ON a.id = c.activitat_id
      WHERE r.usuari_id = ? AND r.estat = 'confirmada' AND c.inici > NOW()
      ORDER BY c.inici"
);
$st->execute([$u['id']]);
$reserves = $st->fetchAll();

// La quota es mira a la base de dades: la pot haver canviat recepció mentre el client tenia la sessió oberta
$st = $connexio->prepare(
    'SELECT quota_fins, quota_fins >= CURDATE() AS al_dia, quota_fins <= CURDATE() + INTERVAL 7 DAY AS caduca_aviat
       FROM usuaris WHERE id = ?'
);
$st->execute([$u['id']]);
$quota = $st->fetch();

// El pla personalitzat i el seguiment depenen de la tarifa
$t = tarifa_usuari($connexio, $u['id']);
$tarifes = array_column($connexio->query('SELECT nivell, nom, preu_mes FROM tarifes')->fetchAll(), null, 'nivell');
if ($t['nivell'] >= 2) {
    $st = $connexio->prepare('SELECT MAX(enviat) FROM questionaris WHERE usuari_id = ?');
    $st->execute([$u['id']]);
    $questionari = $st->fetchColumn();
    $st = $connexio->prepare('SELECT MAX(pujat) FROM plans WHERE usuari_id = ?');
    $st->execute([$u['id']]);
    $pla = $st->fetchColumn();
}
if ($t['nivell'] >= 3) {
    require_once __DIR__ . '/../inc/seguiment.php';
    $propera = propera_revisio($connexio, $u['id']);
    $st = $connexio->prepare('SELECT COUNT(*) FROM missatges WHERE usuari_id = ? AND llegit = 0 AND (autor_id IS NULL OR autor_id <> ?)');
    $st->execute([$u['id'], $u['id']]);
    $sense_llegir = (int) $st->fetchColumn();
}

$st = $connexio->prepare(
    'SELECT missatge, data FROM notificacions WHERE usuari_id = ? ORDER BY data DESC, id DESC LIMIT 5'
);
$st->execute([$u['id']]);
$avisos = $st->fetchAll();

// En obrir l'àrea, els avisos es marquen com a llegits
$connexio->prepare('UPDATE notificacions SET llegida = 1 WHERE usuari_id = ?')->execute([$u['id']]);

$titol = 'Hola, ' . $u['nom'];
require __DIR__ . '/../inc/capcalera.php';
?>
<?php if ($u['rol'] === 'admin'): ?>
  <p>Has entrat com a <b>administrador</b>. <a href="../admin/">Anar al panell d'administració</a>.</p>
<?php elseif (!$quota['al_dia']): ?>
  <div class="error">No tens la quota pagada. Passa per recepció per pagar-la; mentrestant no podràs reservar classes i el torn d'entrada a les sales no et deixarà passar.</div>
<?php elseif ($quota['caduca_aviat']): ?>
  <p><span class="etiqueta groga">Quota</span> La teva quota caduca el <?= date('d/m/Y', strtotime($quota['quota_fins'])) ?>. Recorda pagar-la a recepció.</p>
<?php else: ?>
  <p><span class="etiqueta verda">Quota</span> Pagada fins al <?= date('d/m/Y', strtotime($quota['quota_fins'])) ?>.</p>
<?php endif; ?>

<h2>Les meves reserves</h2>
<?php if (!$reserves): ?>
  <p>No tens cap reserva. <a href="../reserves/">Mira les properes classes</a>.</p>
<?php else: ?>
  <table>
    <tr><th>Activitat</th><th>Dia i hora</th><th></th></tr>
    <?php foreach ($reserves as $r): ?>
      <tr>
        <td><?= h($r['nom']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($r['inici'])) ?></td>
        <td>
          <form method="POST" action="../reserves/accio.php">
            <?= camp_csrf() ?>
            <input type="hidden" name="accio" value="cancelar">
            <input type="hidden" name="tornar" value="client">
            <input type="hidden" name="classe_id" value="<?= (int) $r['id'] ?>">
            <button class="btn-cancel">Cancel·lar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <a class="btn" href="../reserves/">Reservar una altra classe</a>
<?php endif; ?>

<?php if ($u['rol'] !== 'admin'): ?>
  <h2>El meu pla</h2>
  <p>Tens la tarifa <b><?= h($t['tarifa']) ?></b>.</p>
  <div class="serveis">
    <?php if ($t['nivell'] >= 2): ?>
      <article>
        <h3>Qüestionari</h3>
        <p><?= $questionari ? 'Enviat el ' . date('d/m/Y', strtotime($questionari)) . '.' : 'És el primer pas: amb les respostes l\'entrenador et fa el pla.' ?></p>
        <a class="btn<?= $questionari ? ' btn-secundari' : '' ?>" href="questionari.php"><?= $questionari ? 'Revisar-lo' : 'Omplir-lo' ?></a>
      </article>
      <article>
        <h3>Pla d'entrenament i alimentació</h3>
        <?php if ($pla): ?>
          <p>Actualitzat el <?= date('d/m/Y', strtotime($pla)) ?>.</p>
          <a class="btn" href="pla.php">Descarregar el PDF</a>
        <?php else: ?>
          <p><?= $questionari ? 'L\'entrenador te l\'està preparant.' : 'Sortirà aquí quan hagis enviat el qüestionari.' ?></p>
        <?php endif; ?>
      </article>
      <article>
        <h3>La meva llibreta</h3>
        <p>La rutina en un full, amb el que aixeques cada setmana.</p>
        <a class="btn btn-secundari" href="llibreta.php">Obrir la llibreta</a>
      </article>
    <?php else: ?>
      <article class="tancat">
        <h3>Pla personalitzat i llibreta</h3>
        <p>Rutina i dieta fetes per a tu, i una llibreta per apuntar el que aixeques. Disponible amb la tarifa <?= h($tarifes[2]['nom']) ?> (<?= number_format($tarifes[2]['preu_mes'], 0, ',', '.') ?> € al mes).</p>
      </article>
    <?php endif; ?>
    <?php if ($t['nivell'] >= 3): ?>
      <article>
        <h3>Seguiment</h3>
        <p><?= $propera <= date('Y-m-d') ? '<span class="etiqueta groga">Toca</span> Ja pots enviar la revisió.' : 'Propera revisió: ' . date('d/m/Y', strtotime($propera)) . '.' ?>
          <?= $sense_llegir ? '<br><span class="etiqueta verda">' . $sense_llegir . ' nou' . ($sense_llegir > 1 ? 's' : '') . '</span> Tens missatges de l\'entrenador.' : '' ?></p>
        <a class="btn btn-secundari" href="seguiment.php">Anar al seguiment</a>
      </article>
    <?php else: ?>
      <article class="tancat">
        <h3>Seguiment</h3>
        <p>Una revisió cada 2 setmanes i missatges amb l'entrenador per als dubtes i els canvis de rutina. Disponible amb la tarifa <?= h($tarifes[3]['nom']) ?> (<?= number_format($tarifes[3]['preu_mes'], 0, ',', '.') ?> € al mes).</p>
      </article>
    <?php endif; ?>
  </div>
  <?php if ($t['nivell'] < 3): ?><p class="subtil">Per canviar de tarifa, demana-ho a recepció.</p><?php endif; ?>
<?php endif; ?>

<h2>El meu entrenament</h2>
<p>Les sèries que fas a les màquines intel·ligents amb la teva polsera.</p>
<a class="btn btn-secundari" href="entrenament.php">Veure el meu entrenament</a>

<h2>El meu compte</h2>
<p><a href="contrasenya.php">Canviar la contrasenya</a></p>

<h2>Avisos</h2>
<?php if (!$avisos): ?>
  <p>No tens avisos.</p>
<?php else: ?>
  <table>
    <?php foreach ($avisos as $a): ?>
      <tr><td><?= h($a['missatge']) ?></td><td><?= date('d/m/Y H:i', strtotime($a['data'])) ?></td></tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
