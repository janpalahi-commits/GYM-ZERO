<?php
// Gestió de classes: crear-ne i cancel·lar-ne
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $accio = $_POST['accio'] ?? '';

    if ($accio === 'crear') {
        $activitat_id = (int) ($_POST['activitat_id'] ?? 0);
        $data   = $_POST['data'] ?? '';
        $hora   = $_POST['hora'] ?? '';
        $places = (int) ($_POST['places'] ?? 0);

        $st = $connexio->prepare('SELECT places_per_defecte FROM activitats WHERE id = ? AND activa = 1');
        $st->execute([$activitat_id]);
        $per_defecte = $st->fetchColumn();
        $inici = DateTime::createFromFormat('Y-m-d H:i', "$data $hora");

        if ($per_defecte === false) {
            avis('error', 'Tria una activitat.');
        } elseif (!$inici || $inici->format('Y-m-d H:i') !== "$data $hora") {
            avis('error', 'La data o l\'hora no són vàlides.');
        } elseif ($inici <= new DateTime()) {
            avis('error', 'La classe ha de ser en una data futura.');
        } else {
            if ($places <= 0) {
                $places = (int) $per_defecte;   // si es deixa buit, les places de l'activitat
            }
            if ($places > 200) {
                avis('error', 'Com a màxim 200 places per classe.');
            } else {
                try {
                    $st = $connexio->prepare('INSERT INTO classes (activitat_id, inici, places) VALUES (?, ?, ?)');
                    $st->execute([$activitat_id, $inici->format('Y-m-d H:i:00'), $places]);
                    avis('ok', 'Classe creada.');
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                    avis('error', 'Ja hi ha una classe d\'aquesta activitat a aquesta hora.');
                }
            }
        }
    }

    if ($accio === 'cancelar') {
        // Cancel·lar la classe, anul·lar les seves reserves i avisar cada client
        $classe_id = (int) ($_POST['classe_id'] ?? 0);
        $connexio->beginTransaction();
        $st = $connexio->prepare(
            'SELECT a.nom, c.inici FROM classes c JOIN activitats a ON a.id = c.activitat_id
              WHERE c.id = ? AND c.cancelada = 0 AND c.inici > NOW() FOR UPDATE'
        );
        $st->execute([$classe_id]);
        $classe = $st->fetch();

        if (!$classe) {
            $connexio->rollBack();
            avis('error', 'Aquesta classe no es pot cancel·lar (no existeix, ja ha passat o ja està cancel·lada).');
        } else {
            $connexio->prepare('UPDATE classes SET cancelada = 1 WHERE id = ?')->execute([$classe_id]);

            $text = 'Classe cancel·lada pel gimnàs: ' . $classe['nom'] . ', ' . date('d/m/Y H:i', strtotime($classe['inici'])) . '.';
            $st = $connexio->prepare(
                "INSERT INTO notificacions (usuari_id, tipus, missatge)
                 SELECT usuari_id, 'classe_cancelada', ? FROM reserves
                  WHERE classe_id = ? AND estat = 'confirmada'"
            );
            $st->execute([$text, $classe_id]);
            $avisats = $st->rowCount();

            $connexio->prepare(
                "UPDATE reserves SET estat = 'cancelada', data_cancelacio = NOW()
                  WHERE classe_id = ? AND estat = 'confirmada'"
            )->execute([$classe_id]);

            $connexio->commit();
            avis('ok', "Classe cancel·lada. S'ha avisat $avisats client(s).");
        }
    }

    header('Location: classes.php');
    exit;
}

$activitats = $connexio->query('SELECT id, nom, places_per_defecte FROM activitats WHERE activa = 1 ORDER BY nom')->fetchAll();

$classes = $connexio->query(
    "SELECT c.id, a.nom, c.inici, c.places, c.cancelada, c.inici <= NOW() AS passada,
            (SELECT COUNT(*) FROM reserves r WHERE r.classe_id = c.id AND r.estat = 'confirmada') AS ocupades
       FROM classes c JOIN activitats a ON a.id = c.activitat_id
      WHERE c.inici > NOW() - INTERVAL 7 DAY
      ORDER BY c.inici"
)->fetchAll();

$titol = 'Classes';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar al panell</a></p>

<h2>Nova classe</h2>
<form method="POST">
  <?= camp_csrf() ?>
  <input type="hidden" name="accio" value="crear">
  <div class="fila-camps">
    <div>
      <label for="activitat_id">Activitat</label>
      <select id="activitat_id" name="activitat_id" required>
        <option value="">Tria...</option>
        <?php foreach ($activitats as $a): ?>
          <option value="<?= (int) $a['id'] ?>"><?= h($a['nom']) ?> (<?= (int) $a['places_per_defecte'] ?> places)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="data">Dia</label>
      <input id="data" name="data" type="date" min="<?= date('Y-m-d') ?>" required>
    </div>
    <div>
      <label for="hora">Hora</label>
      <input id="hora" name="hora" type="time" required>
    </div>
    <div>
      <label for="places">Places <span class="subtil">(buit = les de l'activitat)</span></label>
      <input id="places" name="places" type="number" min="1" max="200">
    </div>
  </div>
  <button type="submit">Crear classe</button>
</form>

<h2>Classes</h2>
<p class="subtil">Les classes programades i les dels últims 7 dies.</p>
<?php if (!$classes): ?>
  <p>No hi ha classes.</p>
<?php else: ?>
  <table>
    <tr><th>Activitat</th><th>Dia i hora</th><th>Ocupació</th><th class="amaga-mobil">Estat</th><th></th></tr>
    <?php foreach ($classes as $c): ?>
      <tr>
        <td><?= h($c['nom']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($c['inici'])) ?></td>
        <td><?= (int) $c['ocupades'] ?> / <?= (int) $c['places'] ?></td>
        <td class="amaga-mobil">
          <?php if ($c['cancelada']): ?><span class="etiqueta vermella">Cancel·lada</span>
          <?php elseif ($c['passada']): ?><span class="etiqueta">Feta</span>
          <?php else: ?><span class="etiqueta verda">Programada</span><?php endif; ?>
        </td>
        <td>
          <div class="accions">
            <a href="classe.php?id=<?= (int) $c['id'] ?>">Veure</a>
            <?php if (!$c['cancelada'] && !$c['passada']): ?>
              <form method="POST" onsubmit="return confirm('Segur que vols cancel·lar aquesta classe? S\'avisarà els clients que l\'han reservada.');">
                <?= camp_csrf() ?>
                <input type="hidden" name="accio" value="cancelar">
                <input type="hidden" name="classe_id" value="<?= (int) $c['id'] ?>">
                <button class="btn-cancel">Cancel·lar</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
