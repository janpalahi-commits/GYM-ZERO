<?php
// Portada de la web: informació del gimnàs i les properes classes.
// Tothom la pot veure, no cal haver entrat.
require_once __DIR__ . '/inc/sessio.php';
require_once __DIR__ . '/inc/bd.php';

// Unes quantes xifres que surten de la base de dades
$xifres = $connexio->query(
    "SELECT (SELECT COUNT(*) FROM activitats WHERE activa = 1) AS activitats,
            (SELECT COUNT(*) FROM classes c JOIN activitats a ON a.id = c.activitat_id
              WHERE c.cancelada = 0 AND a.activa = 1
                AND c.inici > NOW() AND c.inici < NOW() + INTERVAL 7 DAY) AS classes_setmana,
            (SELECT COUNT(*) FROM maquines WHERE estat = 'activa') AS maquines"
)->fetch();

// Les 4 properes classes amb les places que queden
$classes = $connexio->query(
    "SELECT a.nom, c.inici, c.places,
            (SELECT COUNT(*) FROM reserves r WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades
       FROM classes c JOIN activitats a ON a.id = c.activitat_id
      WHERE c.inici > NOW() AND c.cancelada = 0 AND a.activa = 1
      ORDER BY c.inici
      LIMIT 4"
)->fetchAll();

$arrel = '';
$titol = 'GYM ZERO';
require __DIR__ . '/inc/capcalera.php';
?>
<p>Gimnàs de força i entrenament en grup. Pots reservar les classes des d'aquí, consultar les teves reserves i veure com evoluciones a les màquines de la sala de força.</p>

<div class="xifres">
  <div class="xifra"><b><?= (int) $xifres['activitats'] ?></b><span>activitats</span></div>
  <div class="xifra"><b><?= (int) $xifres['classes_setmana'] ?></b><span>classes els propers 7 dies</span></div>
  <div class="xifra"><b><?= (int) $xifres['maquines'] ?></b><span>màquines que registren les sèries</span></div>
</div>

<h2>Properes classes</h2>
<?php if (!$classes): ?>
  <p>Ara mateix no hi ha classes programades.</p>
<?php else: ?>
  <table>
    <tr><th>Activitat</th><th>Dia i hora</th><th>Places lliures</th></tr>
    <?php foreach ($classes as $c):
        $lliures = max(0, $c['places'] - $c['ocupades']); ?>
      <tr>
        <td><?= h($c['nom']) ?></td>
        <td><?= date('d/m H:i', strtotime($c['inici'])) ?></td>
        <td class="<?= $lliures === 0 ? 'places-0' : '' ?>"><?= $lliures === 0 ? 'Complet' : $lliures ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<a class="btn" href="reserves/">Veure totes les classes i reservar</a>

<h2>Sala de força</h2>
<p>Les màquines de la sala de força tenen un lector RFID. Hi acostes la teva polsera abans de començar i la màquina apunta cada sèrie: el pes, les repeticions i a quina velocitat has mogut la barra. Després ho pots consultar a <a href="client/entrenament.php">El meu entrenament</a>.</p>
<p>A <a href="calculs/">Càlculs</a> pots estimar el teu pes màxim (1RM), l'IMC i les calories que gastes cada dia.</p>

<h2>Horari</h2>
<table>
  <tr><td>Dilluns a divendres</td><td>7:00 a 22:00</td></tr>
  <tr><td>Dissabtes</td><td>9:00 a 14:00</td></tr>
  <tr><td>Diumenges i festius</td><td>Tancat</td></tr>
</table>

<h2>Com començar</h2>
<p>Apunta't a recepció: pagues la quota, et donem la polsera per a les màquines i et fem el compte de la web. Amb el compte pots reservar classes i veure les teves sèries.</p>
<?php require __DIR__ . '/inc/peu.php'; ?>
