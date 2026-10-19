<?php
// Gestió d'activitats: crear-ne i activar/desactivar-les
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $accio = $_POST['accio'] ?? '';

    if ($accio === 'crear') {
        $nom        = trim($_POST['nom'] ?? '');
        $descripcio = trim($_POST['descripcio'] ?? '');
        $durada     = (int) ($_POST['durada_min'] ?? 0);
        $places     = (int) ($_POST['places_per_defecte'] ?? 0);

        if ($nom === '' || mb_strlen($nom) > 60) {
            avis('error', 'El nom és obligatori (màxim 60 caràcters).');
        } elseif ($durada < 15 || $durada > 240) {
            avis('error', 'La durada ha de ser entre 15 i 240 minuts.');
        } elseif ($places < 1 || $places > 200) {
            avis('error', 'Les places han de ser entre 1 i 200.');
        } else {
            try {
                $st = $connexio->prepare(
                    'INSERT INTO activitats (nom, descripcio, durada_min, places_per_defecte) VALUES (?, ?, ?, ?)'
                );
                $st->execute([$nom, $descripcio !== '' ? $descripcio : null, $durada, $places]);
                avis('ok', 'Activitat creada.');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                avis('error', 'Ja hi ha una activitat amb aquest nom.');
            }
        }
    }

    if ($accio === 'canviar_estat') {
        // Una activitat desactivada no surt a la llista de classes ni es poden crear classes noves
        $st = $connexio->prepare('UPDATE activitats SET activa = 1 - activa WHERE id = ?');
        $st->execute([(int) ($_POST['activitat_id'] ?? 0)]);
        avis('ok', 'Activitat actualitzada.');
    }

    header('Location: activitats.php');
    exit;
}

$activitats = $connexio->query(
    'SELECT a.*,
            (SELECT COUNT(*) FROM classes c WHERE c.activitat_id = a.id AND c.inici > NOW() AND c.cancelada = 0) AS properes
       FROM activitats a
      ORDER BY a.activa DESC, a.nom'
)->fetchAll();

$titol = 'Activitats';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar al panell</a></p>

<table>
  <tr><th>Activitat</th><th class="amaga-mobil">Durada</th><th>Places</th><th>Properes classes</th><th>Estat</th><th></th></tr>
  <?php foreach ($activitats as $a): ?>
    <tr>
      <td><?= h($a['nom']) ?></td>
      <td class="amaga-mobil"><?= (int) $a['durada_min'] ?> min</td>
      <td><?= (int) $a['places_per_defecte'] ?></td>
      <td><?= (int) $a['properes'] ?></td>
      <td>
        <?php if ($a['activa']): ?><span class="etiqueta verda">Activa</span>
        <?php else: ?><span class="etiqueta">Desactivada</span><?php endif; ?>
      </td>
      <td>
        <form method="POST">
          <?= camp_csrf() ?>
          <input type="hidden" name="accio" value="canviar_estat">
          <input type="hidden" name="activitat_id" value="<?= (int) $a['id'] ?>">
          <button class="btn-secundari"><?= $a['activa'] ? 'Desactivar' : 'Activar' ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h2>Nova activitat</h2>
<form method="POST">
  <?= camp_csrf() ?>
  <input type="hidden" name="accio" value="crear">
  <div class="fila-camps">
    <div>
      <label for="nom">Nom</label>
      <input id="nom" name="nom" maxlength="60" required>
    </div>
    <div>
      <label for="durada_min">Durada (minuts)</label>
      <input id="durada_min" name="durada_min" type="number" min="15" max="240" value="60" required>
    </div>
    <div>
      <label for="places_per_defecte">Places per classe</label>
      <input id="places_per_defecte" name="places_per_defecte" type="number" min="1" max="200" value="15" required>
    </div>
  </div>
  <label for="descripcio">Descripció</label>
  <textarea id="descripcio" name="descripcio"></textarea>
  <button type="submit">Crear activitat</button>
</form>
<?php require __DIR__ . '/../inc/peu.php'; ?>
