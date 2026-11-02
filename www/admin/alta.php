<?php
// Alta d'un client nou. Els comptes només els crea recepció, quan el client s'apunta al gimnàs:
// així no hi ha comptes de gent que no és client.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/pagaments.php';
requereix_admin($connexio);

$tarifes = $connexio->query('SELECT * FROM tarifes ORDER BY nivell')->fetchAll();

$errors = [];
$nom = $cognoms = $email = $polsera = '';
$mesos = 1;
$tarifa_id = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();

    $nom     = trim($_POST['nom'] ?? '');
    $cognoms = trim($_POST['cognoms'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $polsera = strtoupper(preg_replace('/[^0-9a-fA-F]/', '', $_POST['polsera'] ?? ''));
    $pass    = $_POST['password'] ?? '';
    $mesos   = (int) ($_POST['mesos'] ?? 0);
    $tarifa_id = (int) ($_POST['tarifa_id'] ?? 0);

    if ($nom === '' || $cognoms === '') {
        $errors[] = 'Cal omplir el nom i els cognoms.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correu electrònic no és vàlid.';
    }
    if ($polsera !== '' && !in_array(strlen($polsera), [8, 14, 20], true)) {
        $errors[] = 'El codi de la polsera ha de tenir 8, 14 o 20 xifres hexadecimals.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'La contrasenya ha de tenir com a mínim 8 caràcters.';
    }
    if (!in_array($mesos, [0, 1, 3], true)) {
        $errors[] = 'La quota ha de ser d\'1 o 3 mesos.';
    }
    if (!in_array($tarifa_id, array_map('intval', array_column($tarifes, 'id')), true)) {
        $errors[] = 'Tria una tarifa.';
    }

    if (!$errors) {
        try {
            $st = $connexio->prepare(
                "INSERT INTO usuaris (nom, cognoms, email, password_hash, rol, targeta_rfid, tarifa_id)
                 VALUES (?, ?, ?, ?, 'client', ?, ?)"
            );
            $st->execute([$nom, $cognoms, $email, password_hash($pass, PASSWORD_DEFAULT),
                          $polsera === '' ? null : $polsera, $tarifa_id]);
            $text = 'Compte de ' . $nom . ' ' . $cognoms . ' creat. Ja pot entrar a la web amb el seu correu.';
            // El primer cobrament queda apuntat a pagaments, com tots els altres
            if ($mesos > 0) {
                $cobrat = cobra($connexio, (int) $connexio->lastInsertId(), $tarifa_id, $mesos, usuari_actual()['id']);
                $text .= ' Cobrat: ' . euros($cobrat['import']) . ', quota fins al ' . date('d/m/Y', strtotime($cobrat['fins'])) . '.';
            }
            avis('ok', $text);
            header('Location: usuaris.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            // Clau única repetida: l'email o la polsera ja són d'un altre usuari
            $st = $connexio->prepare('SELECT COUNT(*) FROM usuaris WHERE email = ?');
            $st->execute([$email]);
            $errors[] = $st->fetchColumn() ? 'Ja hi ha un compte amb aquest correu.' : 'Aquesta polsera ja és d\'un altre usuari.';
        }
    }
}

$titol = 'Alta d\'un client';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="usuaris.php">&larr; Tornar als usuaris</a></p>

<?php foreach ($errors as $e): ?>
  <div class="error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="POST">
  <?= camp_csrf() ?>
  <label for="nom">Nom</label>
  <input id="nom" name="nom" value="<?= h($nom) ?>" maxlength="60" required autofocus>

  <label for="cognoms">Cognoms</label>
  <input id="cognoms" name="cognoms" value="<?= h($cognoms) ?>" maxlength="100" required>

  <label for="email">Correu electrònic</label>
  <input id="email" name="email" type="email" value="<?= h($email) ?>" maxlength="150" required>

  <label for="polsera">Polsera (opcional)</label>
  <input id="polsera" name="polsera" value="<?= h($polsera) ?>" maxlength="29" placeholder="acosta-la al lector">

  <label for="password">Contrasenya (mínim 8 caràcters)</label>
  <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">

  <label for="tarifa_id">Tarifa</label>
  <select id="tarifa_id" name="tarifa_id">
    <?php foreach ($tarifes as $t): ?>
      <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $tarifa_id ? 'selected' : '' ?>>
        <?= h($t['nom']) ?> · <?= euros($t['preu_mes']) ?> al mes · <?= euros($t['preu_3mesos']) ?> 3 mesos
      </option>
    <?php endforeach; ?>
  </select>

  <label for="mesos">Quota que paga ara</label>
  <select id="mesos" name="mesos">
    <option value="1" <?= $mesos === 1 ? 'selected' : '' ?>>1 mes</option>
    <option value="3" <?= $mesos === 3 ? 'selected' : '' ?>>3 mesos</option>
    <option value="0" <?= $mesos === 0 ? 'selected' : '' ?>>Encara no paga</option>
  </select>

  <button type="submit">Donar d'alta</button>
</form>
<p class="subtil">La contrasenya l'escriu el client mateix al teclat de recepció. Després la pot canviar des de la seva àrea.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
