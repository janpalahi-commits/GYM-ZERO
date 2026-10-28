<?php
// Detall d'una classe: qui l'ha reservada
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

$id = (int) ($_GET['id'] ?? 0);
$st = $connexio->prepare(
    'SELECT a.nom, c.inici, c.places, c.cancelada
       FROM classes c JOIN activitats a ON a.id = c.activitat_id
      WHERE c.id = ?'
);
$st->execute([$id]);
$classe = $st->fetch();

if (!$classe) {
    pagina_error(404, 'Aquesta classe no existeix.');
}

$st = $connexio->prepare(
    'SELECT u.nom, u.cognoms, u.email, r.estat, r.data_reserva, r.data_cancelacio
       FROM reserves r JOIN usuaris u ON u.id = r.usuari_id
      WHERE r.classe_id = ?
      ORDER BY r.estat, r.data_reserva'
);
$st->execute([$id]);
$reserves = $st->fetchAll();
$confirmades = count(array_filter($reserves, fn($r) => $r['estat'] === 'confirmada'));

$titol = $classe['nom'] . ' · ' . date('d/m/Y H:i', strtotime($classe['inici']));
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="classes.php">&larr; Tornar a les classes</a></p>

<?php if ($classe['cancelada']): ?>
  <div class="error">Aquesta classe està cancel·lada.</div>
<?php endif; ?>

<p><b><?= $confirmades ?></b> reserves confirmades de <b><?= (int) $classe['places'] ?></b> places.</p>

<?php if (!$reserves): ?>
  <p>Ningú ha reservat aquesta classe.</p>
<?php else: ?>
  <table>
    <tr><th>Client</th><th>Correu</th><th>Estat</th><th>Data</th></tr>
    <?php foreach ($reserves as $r): ?>
      <tr>
        <td><?= h($r['nom'] . ' ' . $r['cognoms']) ?></td>
        <td><?= h($r['email']) ?></td>
        <td>
          <?php if ($r['estat'] === 'confirmada'): ?><span class="etiqueta verda">Confirmada</span>
          <?php else: ?><span class="etiqueta vermella">Cancel·lada</span><?php endif; ?>
        </td>
        <td class="subtil">
          <?= date('d/m/Y H:i', strtotime($r['estat'] === 'confirmada' ? $r['data_reserva'] : $r['data_cancelacio'])) ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
