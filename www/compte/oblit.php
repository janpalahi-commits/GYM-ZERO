<?php
// "Has oblidat la contrasenya?": envia per correu un enllaç per posar-ne una de nova.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/correu.php';

if (usuari_actual()) {
    header('Location: ../client/contrasenya.php');
    exit;
}

$enviat = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $email = trim($_POST['email'] ?? '');

    $st = $connexio->prepare('SELECT id, nom, email FROM usuaris WHERE email = ? AND actiu = 1');
    $st->execute([$email]);
    $usuari = $st->fetch();

    if ($usuari) {
        // Com a molt 3 correus per hora per compte, perquè ningú no pugui omplir la bústia d'una altra persona
        $st = $connexio->prepare('SELECT COUNT(*) FROM recuperacions WHERE usuari_id = ? AND creada > NOW() - INTERVAL 1 HOUR');
        $st->execute([$usuari['id']]);
        if ($st->fetchColumn() < 3) {
            // El codi de l'enllaç només va al correu; a la base de dades se'n guarda el hash
            $codi = bin2hex(random_bytes(32));
            $connexio->prepare('INSERT INTO recuperacions (usuari_id, token_hash, caduca) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)')
                     ->execute([$usuari['id'], hash('sha256', $codi)]);
            envia_correu(
                $usuari['email'],
                'Contrasenya nova de Gym Zero',
                "Hola, {$usuari['nom']}:\n\n"
                . "Per posar una contrasenya nova, obre aquest enllaç:\n"
                . url_web() . "/compte/nova.php?codi=$codi\n\n"
                . "L'enllaç serveix durant 1 hora i només una vegada. Si no ho has demanat tu, no cal que facis res: la teva contrasenya no canvia.\n\n"
                . "Gym Zero"
            );
        }
    }
    // Sempre el mateix missatge, hi hagi compte o no: així no es pot saber quins correus estan registrats
    $enviat = true;
}

$titol = 'Contrasenya oblidada';
require __DIR__ . '/../inc/capcalera.php';
?>
<?php if ($enviat): ?>
  <div class="ok">Si hi ha un compte amb aquest correu, t'hem enviat un enllaç per posar una contrasenya nova. Mira també la carpeta de correu brossa.</div>
  <p><a href="login.php">Tornar a entrar</a></p>
<?php else: ?>
  <p>Escriu el correu del teu compte i t'enviarem un enllaç per posar una contrasenya nova.</p>
  <form method="POST">
    <?= camp_csrf() ?>
    <label for="email">Correu electrònic</label>
    <input id="email" name="email" type="email" value="<?= h($email) ?>" required autofocus>
    <button type="submit">Enviar l'enllaç</button>
  </form>
  <p class="subtil">També la pots demanar a recepció.</p>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
