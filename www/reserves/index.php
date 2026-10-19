<?php
// Llista de les properes classes amb les places lliures.
// Tothom la pot veure; per reservar cal haver entrat.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

$u = usuari_actual();

$st = $connexio->prepare(
    "SELECT c.id, a.nom, a.durada_min, c.inici, c.places,
            (SELECT COUNT(*) FROM reserves r
              WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades,
            (SELECT COUNT(*) FROM reserves r
              WHERE r.classe_id = c.id AND r.estat = 'confirmada' AND r.usuari_id = ?) AS meva
       FROM classes c
       JOIN activitats a ON a.id = c.activitat_id
      WHERE c.inici > NOW() AND c.cancelada = 0 AND a.activa = 1
      ORDER BY c.inici"
);
$st->execute([$u['id'] ?? 0]);
$classes = $st->fetchAll();

$titol = 'Properes classes';
require __DIR__ . '/../inc/capcalera.php';
?>
<?php if (!$u): ?>
  <p>Per reservar una plaça has d'<a href="../compte/login.php">entrar</a> o <a href="../compte/registre.php">crear un compte</a>.</p>
<?php endif; ?>

<?php if (!$classes): ?>
  <p>Ara mateix no hi ha classes programades.</p>
<?php else: ?>
  <table>
    <tr><th>Activitat</th><th>Dia i hora</th><th class="amaga-mobil">Durada</th><th>Places lliures</th><th></th></tr>
    <?php foreach ($classes as $c):
        $lliures = $c['places'] - $c['ocupades']; ?>
      <tr>
        <td><?= h($c['nom']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($c['inici'])) ?></td>
        <td class="amaga-mobil"><?= (int) $c['durada_min'] ?> min</td>
        <td class="<?= $lliures <= 0 ? 'places-0' : '' ?>"><?= max(0, $lliures) ?> de <?= (int) $c['places'] ?></td>
        <td>
          <?php if ($u && $c['meva']): ?>
            <form method="POST" action="accio.php">
              <?= camp_csrf() ?>
              <input type="hidden" name="accio" value="cancelar">
              <input type="hidden" name="classe_id" value="<?= (int) $c['id'] ?>">
              <button class="btn-cancel">Cancel·lar</button>
            </form>
          <?php elseif ($u): ?>
            <form method="POST" action="accio.php">
              <?= camp_csrf() ?>
              <input type="hidden" name="accio" value="reservar">
              <input type="hidden" name="classe_id" value="<?= (int) $c['id'] ?>">
              <button <?= $lliures <= 0 ? 'disabled' : '' ?>><?= $lliures <= 0 ? 'Complet' : 'Reservar' ?></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
