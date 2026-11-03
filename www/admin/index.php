<?php
// Panell d'administració: resum i properes classes
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

// Clau nova perquè el PC de recepció es pugui descarregar les còpies (api/copia.php).
// Es mostra una sola vegada; a la base de dades només en queda el hash. La d'abans deixa de servir.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clau_copies'])) {
    comprova_csrf();
    $clau = bin2hex(random_bytes(24));
    $connexio->prepare("INSERT INTO configuracio (clau, valor) VALUES ('copies_token_hash', ?)
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor)")
             ->execute([password_hash($clau, PASSWORD_DEFAULT)]);
    $_SESSION['clau_copies'] = $clau;
    header('Location: ./#copies');
    exit;
}
$clau_nova = $_SESSION['clau_copies'] ?? null;
unset($_SESSION['clau_copies']);

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

// Còpies de seguretat: les fa la web sola cada dia (inc/copies.php). Si l'última ha fallat, ho diu l'avís.
$copies = llista_copies();
$st = $connexio->prepare(
    "SELECT missatge, data FROM notificacions
      WHERE usuari_id = ? AND tipus = 'sistema' AND missatge LIKE 'ERROR: la còpia%' AND data >= NOW() - INTERVAL 1 DAY
      ORDER BY data DESC, id DESC LIMIT 1"
);
$st->execute([usuari_actual()['id']]);
$error_copia = $st->fetch();
$dir_copies = carpeta_copies();
$descarregada = $dir_copies && is_file("$dir_copies/.descarregada") ? filemtime("$dir_copies/.descarregada") : null;
$te_clau = (bool) $connexio->query("SELECT COUNT(*) FROM configuracio WHERE clau = 'copies_token_hash'")->fetchColumn();

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

<h2 id="copies">Còpies de seguretat</h2>
<?php if ($error_copia && (!$copies || strtotime($error_copia['data']) > current($copies)['data'])): ?>
  <div class="error"><?= h($error_copia['missatge']) ?> (<?= date('d/m/Y H:i', strtotime($error_copia['data'])) ?>)</div>
<?php endif; ?>
<?php if (!$copies): ?>
  <p class="subtil">Encara no s'ha fet cap còpia. La web en fa una sola cada dia, la primera en acabar de carregar aquesta pàgina.</p>
<?php else: ?>
  <p class="subtil">La web en fa una cada dia i guarda les <?= COPIES_GUARDAR ?> últimes. Descarrega'n una de tant en tant
     i guarda-la fora del servidor. Per restaurar-ne una: phpMyAdmin › Importar.</p>
  <table>
    <tr><th>Còpia</th><th>Feta</th><th>Mida</th><th></th></tr>
    <?php foreach (array_slice($copies, 0, 3, true) as $nom => $c): ?>
      <tr>
        <td><span class="etiqueta verda">OK</span> <?= h($nom) ?></td>
        <td><?= date('d/m/Y H:i', $c['data']) ?></td>
        <td><?= number_format($c['mida'] / 1024, 0, ',', '.') ?> KB</td>
        <td><a href="copia.php?f=<?= urlencode($nom) ?>">Descarregar</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if (count($copies) > 3): ?><p class="subtil">I <?= count($copies) - 3 ?> més d'abans.</p><?php endif; ?>
<?php endif; ?>

<h3>Còpia fora del servidor (PC de recepció)</h3>
<?php if ($clau_nova): ?>
  <div class="ok">Clau nova del PC de recepció: <code><?= h($clau_nova) ?></code><br>
    Escriu-la a l'instal·lador del PC (<code>bash installa_pc.sh</code>). No es tornarà a veure: si la perds, en generes una altra.</div>
<?php endif; ?>
<?php if (!$te_clau): ?>
  <p class="subtil">Encara no està configurada. Genera una clau i posa-la al PC de recepció.</p>
<?php elseif (!$descarregada): ?>
  <p class="subtil">El PC de recepció encara no s'ha descarregat cap còpia.</p>
<?php elseif (time() - $descarregada > 3 * 86400): ?>
  <div class="error">Fa <?= intdiv(time() - $descarregada, 86400) ?> dies que el PC de recepció no es descarrega cap còpia
    (l'última, el <?= date('d/m/Y H:i', $descarregada) ?>). Mira que estigui encès i amb internet.</div>
<?php else: ?>
  <p><span class="etiqueta verda">OK</span> L'última descàrrega del PC de recepció: <?= date('d/m/Y H:i', $descarregada) ?>.</p>
<?php endif; ?>
<form method="POST"<?= $te_clau ? " onsubmit=\"return confirm('La clau d\\'ara deixarà de servir. Segur?')\"" : '' ?>>
  <?= camp_csrf() ?>
  <button type="submit" name="clau_copies" value="1" class="btn-secundari"><?= $te_clau ? 'Canviar la clau del PC' : 'Generar la clau del PC' ?></button>
</form>

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
