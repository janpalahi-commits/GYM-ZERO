<?php
// Registre de clients nous. Els administradors no es poden registrar des d'aquí.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

if (usuari_actual()) {
    header('Location: ../client/');
    exit;
}

$errors = [];
$nom = $cognoms = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();

    $nom      = trim($_POST['nom'] ?? '');
    $cognoms  = trim($_POST['cognoms'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if ($nom === '' || $cognoms === '') {
        $errors[] = 'Cal omplir el nom i els cognoms.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correu electrònic no és vàlid.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'La contrasenya ha de tenir com a mínim 8 caràcters.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Les dues contrasenyes no coincideixen.';
    }

    if (!$errors) {
        try {
            $st = $connexio->prepare(
                "INSERT INTO usuaris (nom, cognoms, email, password_hash, rol)
                 VALUES (?, ?, ?, ?, 'client')"
            );
            $st->execute([$nom, $cognoms, $email, password_hash($pass, PASSWORD_DEFAULT)]);

            // Entra directament amb el compte nou
            session_regenerate_id(true);
            $_SESSION['usuari'] = [
                'id'  => (int) $connexio->lastInsertId(),
                'nom' => $nom,
                'rol' => 'client',
            ];
            avis('ok', 'Compte creat. Benvingut/da a GYM ZERO!');
            header('Location: ../client/');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // email repetit (clau única)
                $errors[] = 'Ja hi ha un compte amb aquest correu.';
            } else {
                throw $e;
            }
        }
    }
}

$titol = 'Crear un compte';
require __DIR__ . '/../inc/capcalera.php';
?>
<?php foreach ($errors as $e): ?>
  <div class="error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="POST">
  <?= camp_csrf() ?>
  <label for="nom">Nom</label>
  <input id="nom" name="nom" value="<?= h($nom) ?>" maxlength="60" required>

  <label for="cognoms">Cognoms</label>
  <input id="cognoms" name="cognoms" value="<?= h($cognoms) ?>" maxlength="100" required>

  <label for="email">Correu electrònic</label>
  <input id="email" name="email" type="email" value="<?= h($email) ?>" maxlength="150" required>

  <label for="password">Contrasenya (mínim 8 caràcters)</label>
  <input id="password" name="password" type="password" minlength="8" required>

  <label for="password2">Repeteix la contrasenya</label>
  <input id="password2" name="password2" type="password" minlength="8" required>

  <button type="submit">Crear compte</button>
</form>
<p>Ja tens compte? <a href="login.php">Entra</a>.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
