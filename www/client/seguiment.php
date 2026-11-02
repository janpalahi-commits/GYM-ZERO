<?php
// Seguiment amb l'entrenador (tarifa Gimnàs + pla + seguiment):
// cada 2 setmanes el client explica com li va i l'entrenador li respon i, si cal, li canvia la rutina.
// Entre revisió i revisió, li pot escriure els dubtes per missatge.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/seguiment.php';
requereix_tarifa($connexio, 3);

$u = usuari_actual();
$errors = [];
$propera = propera_revisio($connexio, $u['id']);
$toca = $propera <= date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    if (($_POST['accio'] ?? '') === 'revisio') {
        $com_va = trim($_POST['com_va'] ?? '');
        $pes = trim(str_replace(',', '.', $_POST['pes_kg'] ?? ''));
        $pes = $pes === '' ? null : filter_var($pes, FILTER_VALIDATE_FLOAT);
        if (!$toca) {
            $errors[] = 'La propera revisió toca el ' . date('d/m/Y', strtotime($propera)) . '. Si tens algun dubte abans, escriu un missatge.';
        }
        if ($com_va === '' || mb_strlen($com_va) > 2000) {
            $errors[] = 'Explica com t\'ha anat (màxim 2000 caràcters).';
        }
        if ($pes === false || ($pes !== null && ($pes < 35 || $pes > 250))) {
            $errors[] = 'El pes ha de ser entre 35 i 250 kg, o deixa\'l buit.';
        }
        if (!$errors) {
            $connexio->prepare('INSERT INTO revisions (usuari_id, data, pes_kg, com_va) VALUES (?, CURDATE(), ?, ?)')
                     ->execute([$u['id'], $pes, $com_va]);
            avis('ok', 'Revisió enviada. L\'entrenador te la respondrà i t\'arribarà un avís.');
            header('Location: seguiment.php');
            exit;
        }
    } else {
        $error = envia_missatge($connexio, $u['id'], $u['id'], $_POST['text'] ?? '');
        if (!$error) {
            avis('ok', 'Missatge enviat.');
            header('Location: seguiment.php#missatges');
            exit;
        }
        $errors[] = $error;
    }
}

$revisions = revisions_client($connexio, $u['id']);
$missatges = missatges_client($connexio, $u['id']);
// En obrir la pàgina, els missatges de l'entrenador queden llegits
$connexio->prepare('UPDATE missatges SET llegit = 1 WHERE usuari_id = ? AND (autor_id IS NULL OR autor_id <> ?)')
         ->execute([$u['id'], $u['id']]);

$titol = 'Seguiment amb l\'entrenador';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; La meva àrea</a></p>

<?php foreach ($errors as $e): ?>
  <div class="error"><?= h($e) ?></div>
<?php endforeach; ?>

<h2>Revisió de cada 2 setmanes</h2>
<?php if ($toca): ?>
  <p>Ja toca la revisió. Explica a l'entrenador com t'han anat aquestes setmanes: si arribes a fer les sèries, si et cansa massa, si tens alguna molèstia, com menges. Amb això t'ajusta la rutina.</p>
  <form method="POST">
    <?= camp_csrf() ?>
    <input type="hidden" name="accio" value="revisio">
    <label for="pes_kg">Pes d'avui (kg) <span class="subtil">(opcional)</span></label>
    <input id="pes_kg" name="pes_kg" inputmode="decimal" value="<?= h($_POST['pes_kg'] ?? '') ?>">
    <label for="com_va">Com t'ha anat?</label>
    <textarea id="com_va" name="com_va" maxlength="2000" required><?= h($_POST['com_va'] ?? '') ?></textarea>
    <button type="submit">Enviar la revisió</button>
  </form>
<?php else: ?>
  <p><span class="etiqueta">Propera</span> La propera revisió toca el <b><?= date('d/m/Y', strtotime($propera)) ?></b>.</p>
<?php endif; ?>

<?php if ($revisions): ?>
  <h3>Revisions anteriors</h3>
  <?php foreach ($revisions as $r): ?>
    <article class="revisio">
      <p><b><?= date('d/m/Y', strtotime($r['data'])) ?></b><?= $r['pes_kg'] !== null ? ' · ' . str_replace('.', ',', (float) $r['pes_kg']) . ' kg' : '' ?></p>
      <p><?= nl2br(h($r['com_va'])) ?></p>
      <?php if ($r['resposta'] !== null): ?>
        <div class="resposta"><b><?= h($r['entrenador'] ?? 'Entrenador') ?>:</b> <?= nl2br(h($r['resposta'])) ?></div>
      <?php else: ?>
        <p class="subtil">L'entrenador encara no l'ha respost.</p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
<?php endif; ?>

<h2 id="missatges">Missatges</h2>
<p>Per als dubtes del dia a dia: un exercici que no saps fer, una màquina ocupada, un canvi d'horari.</p>
<?= mostra_conversa($missatges, $u['id']) ?>
<form method="POST">
  <?= camp_csrf() ?>
  <input type="hidden" name="accio" value="missatge">
  <label for="text">Escriu a l'entrenador</label>
  <textarea id="text" name="text" maxlength="1000" required></textarea>
  <button type="submit">Enviar</button>
</form>
<?php require __DIR__ . '/../inc/peu.php'; ?>
