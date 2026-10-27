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
