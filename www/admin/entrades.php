<?php
// Entrades d'un dia: qui ha passat la polsera pel torn de les sales, i si l'ha deixat passar o no
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

// Dia que es vol veure (per defecte, avui)
$dia = $_GET['dia'] ?? date('Y-m-d');
$dt = DateTime::createFromFormat('Y-m-d', $dia);
if (!$dt || $dt->format('Y-m-d') !== $dia) {
    $dia = date('Y-m-d');
}

$st = $connexio->prepare(
    "SELECT e.data_hora, e.usuari_id, e.targeta, e.permesa, e.motiu, t.nom AS torn,
            u.nom, u.cognoms
       FROM entrades e
       JOIN torns t ON t.id = e.torn_id
       LEFT JOIN usuaris u ON u.id = e.usuari_id
      WHERE e.data_hora >= ? AND e.data_hora < ? + INTERVAL 1 DAY
      ORDER BY e.data_hora DESC, e.id DESC"
);
$st->execute([$dia, $dia]);
$entrades = $st->fetchAll();

$permeses = count(array_filter($entrades, fn($e) => $e['permesa']));
$persones = count(array_unique(array_column(array_filter($entrades, fn($e) => $e['permesa']), 'usuari_id')));

$titol = 'Entrades';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar al panell</a></p>

<form method="GET">
  <div class="fila-camps">
    <div>
      <label for="dia">Dia</label>
      <input type="date" id="dia" name="dia" value="<?= h($dia) ?>">
    </div>
  </div>
  <button type="submit">Veure</button>
</form>

<div class="xifres">
  <div class="xifra"><b><?= $persones ?></b><span>persones han entrat</span></div>
  <div class="xifra"><b><?= $permeses ?></b><span>vegades ha deixat passar</span></div>
  <div class="xifra"><b><?= count($entrades) - $permeses ?></b><span>vegades no ha deixat passar</span></div>
</div>

<?php if (!$entrades): ?>
  <p>Aquest dia ningú ha passat la polsera pel torn.</p>
<?php else: ?>
  <table>
    <tr><th>Hora</th><th>Qui</th><th class="amaga-mobil">Torn</th><th>Passa</th><th>Motiu</th></tr>
    <?php foreach ($entrades as $e): ?>
      <tr>
        <td><?= date('H:i', strtotime($e['data_hora'])) ?></td>
        <td>
          <?php if ($e['nom'] !== null): ?><?= h($e['nom'] . ' ' . $e['cognoms']) ?>
          <?php else: ?><span class="subtil">Polsera <?= h($e['targeta']) ?></span><?php endif; ?>
        </td>
        <td class="amaga-mobil"><?= h($e['torn']) ?></td>
        <td>
          <?php if ($e['permesa']): ?><span class="etiqueta verda">Sí</span>
          <?php else: ?><span class="etiqueta vermella">No</span><?php endif; ?>
        </td>
        <td><?= h($e['motiu']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<p class="subtil">Cada vegada que algú acosta la polsera al torn d'entrada a les sales queda apuntat aquí, tant si el deixa passar com si no. Per sortir no cal la polsera, així que no es pot saber qui és a dins en aquest moment.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
