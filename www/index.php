<?php
// Portada de la web: l'horari dels propers dies amb les places i el gimnàs.
// Tothom la pot veure, no cal haver entrat.
require_once __DIR__ . '/inc/sessio.php';
require_once __DIR__ . '/inc/bd.php';

// Els propers 6 dies que obrim (el diumenge és tancat), començant per avui
$noms_dies = ['Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds'];
$dies = [];
for ($d = 0; count($dies) < 6; $d++) {
    $dia = strtotime("today +$d day");
    if (date('w', $dia) !== '0') {
        $dies[date('Y-m-d', $dia)] = $noms_dies[date('w', $dia)] . ' <small>' . date('j', $dia) . '</small>';
    }
}
$ultim = array_key_last($dies);

$st = $connexio->prepare(
    "SELECT c.id, c.activitat_id, a.nom, c.inici, c.places,
            (SELECT COUNT(*) FROM reserves r WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades
       FROM classes c JOIN activitats a ON a.id = c.activitat_id
      WHERE c.inici > NOW() AND c.inici < ? + INTERVAL 1 DAY AND c.cancelada = 0 AND a.activa = 1
      ORDER BY c.inici"
);
$st->execute([$ultim]);

// $tauler[hora][dia] = classes que comencen aquell dia a aquella hora
$tauler = [];
foreach ($st->fetchAll() as $c) {
    $tauler[date('H:i', strtotime($c['inici']))][date('Y-m-d', strtotime($c['inici']))][] = $c;
}
ksort($tauler);

$activitats = $connexio->query('SELECT id, nom FROM activitats WHERE activa = 1 ORDER BY id')->fetchAll();

// Màquines de força per zones. Els clients només en veuen el nom, no el codi.
$zones_bd = [
    'Sala de força · tren superior' => ['Tren superior', 'var(--forca)'],
    'Sala de força · cames'         => ['Cames i abdominals', 'var(--cames)'],
];
$zones = [];
foreach ($connexio->query("SELECT ubicacio, tipus FROM maquines WHERE estat = 'activa' ORDER BY id") as $m) {
    $zones[$m['ubicacio']][] = mb_strtoupper(mb_substr($m['tipus'], 0, 1)) . mb_substr($m['tipus'], 1);
}
$total_maquines = array_sum(array_map('count', $zones));

$arrel = '';
$portada = true;
$titol = 'Gym Zero';
require __DIR__ . '/inc/capcalera.php';
?>
<?php if (!empty($_SESSION['avis'])): ?>
  <div class="wrap avisos"><?php mostra_avis(); ?></div>
<?php endif; ?>

<section class="hero">
  <div class="wrap">
    <div>
      <h1>Tria la teva <em>classe</em></h1>
      <p>Mira quantes places queden i reserva la teva.</p>
      <a class="btn" href="reserves/">Reservar</a>
      <div class="llegenda">
        <?php foreach ($activitats as $a): [$color] = color_activitat($a['id']); ?>
          <span style="--c: <?= $color ?>"><i></i><?= h($a['nom']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <?php if (!$tauler): ?>
        <p class="nota">Aquests dies no hi ha classes programades.</p>
      <?php else: ?>
        <div class="desplaca">
          <div class="tauler" style="--dies: <?= count($dies) ?>">
            <div class="cap"></div>
            <?php foreach ($dies as $etiqueta): ?>
              <div class="dh"><?= $etiqueta ?></div>
            <?php endforeach; ?>
            <?php foreach ($tauler as $hora => $per_dia): ?>
              <div class="hh"><?= $hora ?></div>
              <?php foreach (array_keys($dies) as $dia): ?>
                <div class="cel">
                  <?php foreach ($per_dia[$dia] ?? [] as $c):
                      [$color, $fosc] = color_activitat($c['activitat_id']);
                      $lliures = max(0, $c['places'] - $c['ocupades']); ?>
                    <a href="reserves/#c<?= (int) $c['id'] ?>" class="<?= $fosc ? 'fosc' : '' ?>" style="--c: <?= $color ?>"
                       title="<?= h($c['nom']) ?>, <?= date('d/m', strtotime($c['inici'])) ?> a les <?= $hora ?>: <?= $lliures ?> places lliures">
                      <b><?= h($c['nom']) ?></b>
                      <?php if ($lliures === 0): ?>
                        <span class="complet">Complet</span>
                      <?php elseif ($c['places'] > 24): ?>
                        <span class="lliures"><?= $lliures ?> lliures</span>
                      <?php else: ?>
                        <span class="pl" style="--n: <?= (int) ceil($c['places'] / ceil($c['places'] / 8)) ?>"><?= str_repeat('<i></i>', min($c['ocupades'], $c['places'])) . str_repeat('<i class="o"></i>', $lliures) ?></span>
                      <?php endif; ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="nota">Cada punt és una plaça. Els buits són les places lliures.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="sala">
  <div class="wrap">
    <div class="titol-sala">
      <h2>El gimnàs</h2>
      <div class="mides"><span><b>34 × 22</b> m</span><span><b><?= $total_maquines ?></b> màquines de força</span><span><b>1</b> sala de classes</span></div>
    </div>
    <div class="plano">
      <img src="img/maqueta.jpg" width="1400" height="520" alt="Maqueta del gimnàs vista des de dalt: sala de força, cardio, sala de classes, recepció i vestidors">
      <span class="pin" style="left:42%; top:30%; --c: var(--forca)"><i></i>Força</span>
      <span class="pin" style="left:65%; top:30%; --c: var(--cames)"><i></i>Cames</span>
      <span class="pin" style="left:57%; top:72%; --c: var(--cardio)"><i></i>Cardio</span>
      <span class="pin" style="left:81%; top:72%; --c: var(--lila)"><i></i>Classes</span>
      <span class="pin" style="left:33%; top:68%; --c: var(--sorra)"><i></i>Recepció</span>
    </div>
    <div class="zones">
      <?php foreach ($zones as $ubicacio => $noms):
          [$nom_zona, $color] = $zones_bd[$ubicacio] ?? [$ubicacio, 'var(--linia)']; ?>
        <div style="--c: <?= $color ?>">
          <div class="zc"><b><?= h($nom_zona) ?></b><span><?= count($noms) ?> màquines</span></div>
          <p><?= h(implode(' · ', $noms)) ?></p>
        </div>
      <?php endforeach; ?>
      <div style="--c: var(--cardio)"><div class="zc"><b>Cardio</b><span>7 aparells</span></div><p>4 bicicletes · 3 cintes de córrer</p></div>
      <div style="--c: #8c96a0"><div class="zc"><b>Pes lliure</b><span>zona oberta</span></div><p>Manuelles i bancs</p></div>
    </div>
    <p class="nota-pols"><span class="ico-pols" aria-hidden="true"></span>Les <?= $total_maquines ?> màquines de força apunten les teves sèries quan hi acostes la polsera.</p>
  </div>
</section>

<section class="obert">
  <div class="wrap">
    <div><b>7–22 h</b><span>Dilluns a divendres</span></div>
    <div><b>9–14 h</b><span>Dissabte</span></div>
    <div class="tancat"><b>Tancat</b><span>Diumenge</span></div>
  </div>
</section>

<?php require __DIR__ . '/inc/peu.php'; ?>
