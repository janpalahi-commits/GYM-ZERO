<?php
// Gestió d'usuaris: activar/desactivar comptes i canviar el rol
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
requereix_admin($connexio);

$jo = usuari_actual()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $usuari_id = (int) ($_POST['usuari_id'] ?? 0);
    $accio = $_POST['accio'] ?? '';

    if ($usuari_id === $jo) {
        // Així l'administrador no es pot treure l'accés a si mateix per error
        avis('error', 'No pots canviar el teu propi compte des d\'aquí.');
    } elseif ($accio === 'canviar_actiu') {
        $connexio->prepare('UPDATE usuaris SET actiu = 1 - actiu WHERE id = ?')->execute([$usuari_id]);
        avis('ok', 'Compte actualitzat.');
    } elseif ($accio === 'canviar_rol') {
        $connexio->prepare(
            "UPDATE usuaris SET rol = IF(rol = 'admin', 'client', 'admin') WHERE id = ?"
        )->execute([$usuari_id]);
        avis('ok', 'Rol actualitzat.');
    }

    header('Location: usuaris.php');
    exit;
}

$usuaris = $connexio->query(
    "SELECT u.id, u.nom, u.cognoms, u.email, u.rol, u.actiu, u.data_registre,
            (SELECT COUNT(*) FROM reserves r JOIN classes c ON c.id = r.classe_id
              WHERE r.usuari_id = u.id AND r.estat = 'confirmada' AND c.inici > NOW()) AS reserves
       FROM usuaris u
      ORDER BY u.rol, u.nom"
)->fetchAll();

$titol = 'Usuaris';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar al panell</a></p>

<table>
  <tr><th>Nom</th><th class="amaga-mobil">Correu</th><th>Rol</th><th>Reserves</th><th>Estat</th><th></th></tr>
  <?php foreach ($usuaris as $us): ?>
    <tr>
      <td><?= h($us['nom'] . ' ' . $us['cognoms']) ?></td>
      <td class="amaga-mobil"><?= h($us['email']) ?></td>
      <td><?= $us['rol'] === 'admin' ? 'Admin' : 'Client' ?></td>
      <td><?= (int) $us['reserves'] ?></td>
      <td>
        <?php if ($us['actiu']): ?><span class="etiqueta verda">Actiu</span>
        <?php else: ?><span class="etiqueta vermella">Desactivat</span><?php endif; ?>
      </td>
      <td>
        <?php if ((int) $us['id'] !== $jo): ?>
          <div class="accions">
            <form method="POST">
              <?= camp_csrf() ?>
              <input type="hidden" name="accio" value="canviar_actiu">
              <input type="hidden" name="usuari_id" value="<?= (int) $us['id'] ?>">
              <button class="btn-secundari"><?= $us['actiu'] ? 'Desactivar' : 'Activar' ?></button>
            </form>
            <form method="POST">
              <?= camp_csrf() ?>
              <input type="hidden" name="accio" value="canviar_rol">
              <input type="hidden" name="usuari_id" value="<?= (int) $us['id'] ?>">
              <button class="btn-secundari"><?= $us['rol'] === 'admin' ? 'Fer client' : 'Fer admin' ?></button>
            </form>
          </div>
        <?php else: ?>
          <span class="subtil">(tu)</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<p class="subtil">Un compte desactivat no pot entrar. Les seves reserves no s'esborren.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
