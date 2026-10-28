<?php
// Contrasenya nova amb l'enllaç que s'ha enviat per correu (compte/oblit.php)
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

// El codi va a l'adreça: que el navegador no l'enviï a cap altra web
header('Referrer-Policy: no-referrer');

$codi = $_POST['codi'] ?? $_GET['codi'] ?? '';
$recuperacio = false;
if (preg_match('/^[0-9a-f]{64}$/', $codi)) {
    $st = $connexio->prepare(
        'SELECT r.id, r.usuari_id
           FROM recuperacions r JOIN usuaris u ON u.id = r.usuari_id
          WHERE r.token_hash = ? AND r.usada = 0 AND r.caduca > NOW() AND u.actiu = 1'
    );
    $st->execute([hash('sha256', $codi)]);
    $recuperacio = $st->fetch();
}

$errors = [];
if ($recuperacio && $_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 8) {
        $errors[] = 'La contrasenya ha de tenir com a mínim 8 caràcters.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Les dues contrasenyes no coincideixen.';
    }
    if (!$errors) {
        $connexio->beginTransaction();
        $connexio->prepare('UPDATE usuaris SET password_hash = ? WHERE id = ?')
                 ->execute([password_hash($pass, PASSWORD_DEFAULT), $recuperacio['usuari_id']]);
        // Aquest enllaç i els altres que hagi demanat deixen de servir
        $connexio->prepare('UPDATE recuperacions SET usada = 1 WHERE usuari_id = ?')
                 ->execute([$recuperacio['usuari_id']]);
        $connexio->commit();
        avis('ok', 'Contrasenya canviada. Ja pots entrar amb la nova.');
        header('Location: login.php');
        exit;
    }
}

$titol = 'Contrasenya nova';
require __DIR__ . '/../inc/capcalera.php';
?>
<?php if (!$recuperacio): ?>
  <div class="error">Aquest enllaç no és vàlid: ja s'ha fet servir o ha caducat.</div>
  <p><a href="oblit.php">Demana'n un de nou</a> o passa per recepció.</p>
<?php else: ?>
  <?php foreach ($errors as $e): ?>
    <div class="error"><?= h($e) ?></div>
  <?php endforeach; ?>
  <form method="POST" action="nova.php">
    <?= camp_csrf() ?>
    <input type="hidden" name="codi" value="<?= h($codi) ?>">
    <label for="password">Contrasenya nova (mínim 8 caràcters)</label>
    <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password" autofocus>

    <label for="password2">Repeteix-la</label>
    <input id="password2" name="password2" type="password" minlength="8" required autocomplete="new-password">

    <button type="submit">Desar la contrasenya</button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
