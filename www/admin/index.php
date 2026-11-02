<?php
// Panell d'administració: resum i properes classes
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

$clients = $connexio->query("SELECT COUNT(*) FROM usuaris WHERE rol = 'client' AND actiu = 1")->fetchColumn();
$classes = $connexio->query('SELECT COUNT(*) FROM classes WHERE inici > NOW() AND cancelada = 0')->fetchColumn();
$entrades = $connexio->query(
    'SELECT COUNT(DISTINCT usuari_id) FROM entrades WHERE permesa = 1 AND data_hora >= CURDATE()'
)->fetchColumn();
$reserves = $connexio->query(
    "SELECT COUNT(*) FROM reserves r JOIN classes c ON c.id = r.classe_id
      WHERE r.estat = 'confirmada' AND c.inici > NOW() AND c.cancelada = 0"
)->fetchColumn();

$properes = $connexio->query(
    "SELECT c.id, a.nom, c.inici, c.places,
            (SELECT COUNT(*) FROM reserves r WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades
       FROM classes c JOIN activitats a ON a.id = c.activitat_id
      WHERE c.inici > NOW() AND c.cancelada = 0
      ORDER BY c.inici
      LIMIT 8"
)->fetchAll();

// L'última còpia de seguretat: el script copia_bd.sh deixa un avís 'sistema' als administradors
$st = $connexio->prepare(
    "SELECT missatge, data FROM notificacions WHERE usuari_id = ? AND tipus = 'sistema' ORDER BY data DESC, id DESC LIMIT 1"
);
$st->execute([usuari_actual()['id']]);
$copia = $st->fetch();

// Clients del pla amb feina pendent (els mateixos que surten marcats a plans.php)
$pendent_plans = $connexio->query(
    "SELECT COUNT(*) FROM usuaris u JOIN tarifes t ON t.id = u.tarifa_id
      WHERE u.rol = 'client' AND u.actiu = 1 AND t.nivell >= 2
        AND (EXISTS (SELECT 1 FROM revisions v WHERE v.usuari_id = u.id AND v.resposta IS NULL)
          OR EXISTS (SELECT 1 FROM missatges m WHERE m.usuari_id = u.id AND m.autor_id = u.id AND m.llegit = 0)
          OR (SELECT MAX(enviat) FROM questionaris q WHERE q.usuari_id = u.id)
               > COALESCE((SELECT MAX(pujat) FROM plans p WHERE p.usuari_id = u.id), '2000-01-01')
          OR (EXISTS (SELECT 1 FROM questionaris q WHERE q.usuari_id = u.id)
              AND NOT EXISTS (SELECT 1 FROM rutina r WHERE r.usuari_id = u.id)))"
)->fetchColumn();

$titol = 'Administració';
require __DIR__ . '/../inc/capcalera.php';
?>
<div class="xifres">
  <div class="xifra"><b><?= (int) $clients ?></b><span>clients actius</span></div>
  <div class="xifra"><b><?= (int) $classes ?></b><span>classes programades</span></div>
  <div class="xifra"><b><?= (int) $reserves ?></b><span>reserves confirmades</span></div>
  <div class="xifra"><b><?= (int) $entrades ?></b><span>persones han entrat avui</span></div>
</div>

<p>
  <a class="btn" href="classes.php">Gestionar classes</a>
  <a class="btn btn-secundari" href="activitats.php">Gestionar activitats</a>
  <a class="btn btn-secundari" href="usuaris.php">Gestionar usuaris</a>
  <a class="btn btn-secundari" href="plans.php">Plans i seguiment<?= $pendent_plans ? ' (' . (int) $pendent_plans . ')' : '' ?></a>
  <a class="btn btn-secundari" href="entrades.php">Veure les entrades</a>
</p>

<h2>Còpies de seguretat</h2>
<?php if (!$copia): ?>
  <p class="subtil">Encara no s'ha fet cap còpia de seguretat de la base de dades.</p>
<?php elseif (str_starts_with($copia['missatge'], 'ERROR')): ?>
  <div class="error"><?= h($copia['missatge']) ?> (<?= date('d/m/Y H:i', strtotime($copia['data'])) ?>)</div>
<?php else: ?>
  <p><span class="etiqueta verda">OK</span> <?= h($copia['missatge']) ?> <span class="subtil"><?= date('d/m/Y H:i', strtotime($copia['data'])) ?></span></p>
<?php endif; ?>

<h2>Properes classes</h2>
<?php if (!$properes): ?>
  <p>No hi ha classes programades. <a href="classes.php">Crea'n una</a>.</p>
<?php else: ?>
  <table>
    <tr><th>Activitat</th><th>Dia i hora</th><th>Ocupació</th><th></th></tr>
    <?php foreach ($properes as $c): ?>
      <tr>
        <td><?= h($c['nom']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($c['inici'])) ?></td>
        <td class="<?= $c['ocupades'] >= $c['places'] ? 'places-0' : '' ?>"><?= (int) $c['ocupades'] ?> / <?= (int) $c['places'] ?></td>
        <td><a href="classe.php?id=<?= (int) $c['id'] ?>">Veure reserves</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
