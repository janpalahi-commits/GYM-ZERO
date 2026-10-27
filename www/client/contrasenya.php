<?php
// Canvi de contrasenya del client (la primera la posa a recepció quan es dona d'alta)
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_login();

$u = usuari_actual();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();

    $actual = $_POST['actual'] ?? '';
    $nova   = $_POST['password'] ?? '';
    $nova2  = $_POST['password2'] ?? '';

    $st = $connexio->prepare('SELECT password_hash FROM usuaris WHERE id = ?');
    $st->execute([$u['id']]);

    if (!password_verify($actual, $st->fetchColumn())) {
        $errors[] = 'La contrasenya actual no és correcta.';
    }
    if (strlen($nova) < 8) {
        $errors[] = 'La contrasenya nova ha de tenir com a mínim 8 caràcters.';
    }
    if ($nova !== $nova2) {
        $errors[] = 'Les dues contrasenyes noves no coincideixen.';
    }

    if (!$errors) {
        $connexio->prepare('UPDATE usuaris SET password_hash = ? WHERE id = ?')
                 ->execute([password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
        avis('ok', 'Contrasenya canviada.');
        header('Location: ./');
        exit;
    }
}

$titol = 'Canviar la contrasenya';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar a la meva àrea</a></p>

<?php foreach ($errors as $e): ?>
  <div class="error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="POST">
  <?= camp_csrf() ?>
  <label for="actual">Contrasenya actual</label>
  <input id="actual" name="actual" type="password" required autocomplete="current-password">

  <label for="password">Contrasenya nova (mínim 8 caràcters)</label>
  <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">

  <label for="password2">Repeteix la contrasenya nova</label>
  <input id="password2" name="password2" type="password" minlength="8" required autocomplete="new-password">

  <button type="submit">Canviar</button>
</form>
<?php require __DIR__ . '/../inc/peu.php'; ?>
