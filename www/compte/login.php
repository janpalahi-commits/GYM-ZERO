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

    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
    // Si la petició arriba d'una IP interna, és el proxy del hosting: la IP del client és a X-Forwarded-For
    // (només llavors se'n fa cas; si no, qualsevol podria inventar-se-la)
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $reenviada = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($reenviada, FILTER_VALIDATE_IP)) {
            $ip = $reenviada;
        }
    }

    // Límit d'intents: 5 errors amb aquest correu o 20 des d'aquesta IP en 15 minuts i queda bloquejat.
    // Així ningú no pot provar contrasenyes una rere l'altra fins a encertar-la.
    $st = $connexio->prepare(
        "SELECT SUM(email = ?) AS pel_correu, SUM(ip = ?) AS per_ip,
                TIMESTAMPDIFF(SECOND, NOW(), MIN(data) + INTERVAL 15 MINUTE) AS falten
           FROM intents_login WHERE (email = ? OR ip = ?) AND data > NOW() - INTERVAL 15 MINUTE"
    );
    $st->execute([$email, $ip, $email, $ip]);
    $intents = $st->fetch();
    if ($intents['pel_correu'] >= 5 || $intents['per_ip'] >= 20) {
        $minuts = max(1, (int) ceil($intents['falten'] / 60));
        $error = 'Massa intents seguits. Espera ' . $minuts . ($minuts === 1 ? ' minut' : ' minuts')
               . " i torna-ho a provar, o demana a recepció que t'ajudin.";
    } else {
        $st = $connexio->prepare('SELECT id, nom, rol, password_hash FROM usuaris WHERE email = ? AND actiu = 1');
        $st->execute([$email]);
        $usuari = $st->fetch();

        // Si el correu no existeix també es comprova un hash, perquè la resposta trigui el mateix
        // i no es pugui saber pel temps quins correus estan registrats
        $correcta = password_verify($pass, $usuari['password_hash'] ?? '$2y$10$eHonHvS3vGsy4RQbt.0w8.p1AemOn3zRXWYEUAcm5tUHUGEIcZYrK');
    }

    // Mateix missatge si l'email no existeix o si la contrasenya és incorrecta:
    // així no es pot saber quins correus estan registrats
    if (!$error && $usuari && $correcta) {
        $connexio->prepare('DELETE FROM intents_login WHERE email = ?')->execute([$email]);
        session_regenerate_id(true);   // sessió nova en entrar (evita robatori de sessió)
        $_SESSION['usuari'] = [
            'id'  => (int) $usuari['id'],
            'nom' => $usuari['nom'],
            'rol' => $usuari['rol'],
        ];
        header('Location: ' . ($usuari['rol'] === 'admin' ? '../admin/' : '../client/'));
        exit;
    }
    if (!$error) {
        $connexio->prepare('INSERT INTO intents_login (email, ip) VALUES (?, ?)')->execute([mb_substr($email, 0, 150), $ip]);
        $connexio->exec('DELETE FROM intents_login WHERE data < NOW() - INTERVAL 1 DAY');   // els vells ja no serveixen
        $error = 'Correu o contrasenya incorrectes.';
    }
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
