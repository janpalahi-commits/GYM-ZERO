<?php
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

$clients = $connexio->query(
    "SELECT u.id, u.nom, u.cognoms, t.nom AS tarifa, t.nivell, u.quota_fins >= CURDATE() AS al_dia,
            (SELECT MAX(enviat) FROM questionaris q WHERE q.usuari_id = u.id) AS questionari,
            (SELECT MAX(pujat) FROM plans p WHERE p.usuari_id = u.id) AS pla,
            (SELECT COUNT(*) FROM rutina r WHERE r.usuari_id = u.id) AS exercicis,
            (SELECT COUNT(*) FROM revisions v WHERE v.usuari_id = u.id AND v.resposta IS NULL) AS revisions,
            (SELECT COUNT(*) FROM missatges m WHERE m.usuari_id = u.id AND m.autor_id = u.id AND m.llegit = 0) AS missatges
       FROM usuaris u JOIN tarifes t ON t.id = u.tarifa_id
      WHERE u.rol = 'client' AND u.actiu = 1 AND t.nivell >= 2
      ORDER BY u.nom, u.cognoms"
)->fetchAll();

foreach ($clients as &$c) {
    $c['pendent'] = [];
    if ($c['questionari'] && (!$c['pla'] || $c['questionari'] > $c['pla'])) {
        $c['pendent'][] = $c['pla'] ? 'Ha canviat el qüestionari: revisar el pla' : 'Fer el pla';
    }
    if ($c['questionari'] && !$c['exercicis']) {
        $c['pendent'][] = 'Posar la rutina';
    }
    if ($c['revisions']) {
        $c['pendent'][] = $c['revisions'] == 1 ? 'Respondre la revisió' : 'Respondre ' . $c['revisions'] . ' revisions';
    }
    if ($c['missatges']) {
        $c['pendent'][] = $c['missatges'] . ($c['missatges'] == 1 ? ' missatge nou' : ' missatges nous');
    }
}
unset($c);
usort($clients, fn($a, $b) => (bool) $b['pendent'] <=> (bool) $a['pendent']);
$pendents = count(array_filter($clients, fn($c) => $c['pendent']));

$titol = 'Plans i seguiment';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Administració</a></p>
<p>Els clients de les tarifes amb pla personalitzat. <?= $pendents ? "N'hi ha <b>$pendents</b> amb feina pendent." : 'Ho tens tot al dia.' ?></p>

<?php if (!$clients): ?>
  <p>Cap client té una tarifa amb pla. La tarifa es tria quan es cobra la quota a <a href="usuaris.php">Usuaris</a>.</p>
<?php else: ?>
  <table>
    <tr><th>Client</th><th class="amaga-mobil">Tarifa</th><th>Pendent</th><th></th></tr>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><?= h($c['nom'] . ' ' . $c['cognoms']) ?><?= $c['al_dia'] ? '' : ' <span class="etiqueta vermella">Quota</span>' ?></td>
        <td class="amaga-mobil"><?= h($c['tarifa']) ?></td>
        <td>
          <?php if (!$c['questionari']): ?>
            <span class="subtil">Esperant el qüestionari</span>
          <?php elseif (!$c['pendent']): ?>
            <span class="etiqueta verda">Al dia</span>
          <?php else: ?>
            <?php foreach ($c['pendent'] as $p): ?><span class="etiqueta groga"><?= h($p) ?></span> <?php endforeach; ?>
          <?php endif; ?>
        </td>
        <td><a href="client.php?id=<?= (int) $c['id'] ?>">Obrir la fitxa</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<p class="subtil">El PDF del pla es fa a l'ordinador del gimnàs amb l'eina de la carpeta <code>plans/</code> a partir del JSON del qüestionari, i després es puja a la fitxa del client.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
