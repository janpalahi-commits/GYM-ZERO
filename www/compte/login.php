<?php
// Inici de sessió (clients i administradors)
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

if (usuari_actual()) {
    header('Location: ../client/');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = $connexio->prepare('SELECT id, nom, rol, password_hash FROM usuaris WHERE email = ? AND actiu = 1');
    $st->execute([$email]);
    $usuari = $st->fetch();

    // Mateix missatge si l'email no existeix o si la contrasenya és incorrecta:
    // així no es pot saber quins correus estan registrats
    if ($usuari && password_verify($pass, $usuari['password_hash'])) {
        session_regenerate_id(true);   // sessió nova en entrar (evita robatori de sessió)
        $_SESSION['usuari'] = [
            'id'  => (int) $usuari['id'],
            'nom' => $usuari['nom'],
            'rol' => $usuari['rol'],
        ];
        header('Location: ' . ($usuari['rol'] === 'admin' ? '../admin/' : '../client/'));
        exit;
    }
    $error = 'Correu o contrasenya incorrectes.';
}

$titol = 'Entrar';
require __DIR__ . '/../inc/capcalera.php';
?>
<?php if ($error): ?>
  <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST">
  <?= camp_csrf() ?>
  <label for="email">Correu electrònic</label>
  <input id="email" name="email" type="email" value="<?= h($email) ?>" required autofocus>

  <label for="password">Contrasenya</label>
  <input id="password" name="password" type="password" required>

  <button type="submit">Entrar</button>
</form>
<p class="subtil">Has oblidat la contrasenya? Demana-la a recepció o <a href="oblit.php">sol·licita'n una de nova aquí</a>.</p>
<p class="subtil">No tens compte? Te'l fem a recepció quan t'apuntes al gimnàs.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
