<?php
// Activitats del gimnàs amb les seves properes classes.
// Tothom la pot veure; per reservar s'envia a la llista de classes.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

$activitats = $connexio->query(
    'SELECT id, nom, descripcio, durada_min FROM activitats WHERE activa = 1 ORDER BY nom'
)->fetchAll();

// Classes dels propers 14 dies, agrupades per activitat
$st = $connexio->query(
    "SELECT c.activitat_id, c.inici, c.places,
            (SELECT COUNT(*) FROM reserves r WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades
       FROM classes c
      WHERE c.inici > NOW() AND c.inici < NOW() + INTERVAL 14 DAY AND c.cancelada = 0
      ORDER BY c.inici"
);
$classes = [];
foreach ($st->fetchAll() as $c) {
    $classes[$c['activitat_id']][] = $c;
}

$titol = 'Activitats';
require __DIR__ . '/../inc/capcalera.php';
?>
<p>Aquestes són les activitats que fem al gimnàs. Les classes tenen places limitades i s'han de reservar.</p>

<?php if (!$activitats): ?>
  <p>Encara no hi ha activitats.</p>
<?php endif; ?>

<?php foreach ($activitats as $a): ?>
  <h2><?= h($a['nom']) ?></h2>
  <p><?= h($a['descripcio']) ?> <span class="subtil">(<?= (int) $a['durada_min'] ?> min)</span></p>
  <?php if (empty($classes[$a['id']])): ?>
    <p class="subtil">No hi ha classes programades els propers 14 dies.</p>
  <?php else: ?>
    <p class="subtil">Properes classes:
      <?php foreach (array_slice($classes[$a['id']], 0, 4) as $i => $c):
          $lliures = max(0, $c['places'] - $c['ocupades']); ?>
        <?= $i > 0 ? ' · ' : '' ?><?= date('d/m H:i', strtotime($c['inici'])) ?>
        (<?= $lliures === 0 ? '<span class="places-0">complet</span>' : $lliures . ($lliures === 1 ? ' lliure' : ' lliures') ?>)
      <?php endforeach; ?>
    </p>
  <?php endif; ?>
<?php endforeach; ?>

<a class="btn" href="../reserves/">Reservar una classe</a>
<?php require __DIR__ . '/../inc/peu.php'; ?>
