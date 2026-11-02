<?php
// Gestió d'usuaris (els clients nous es donen d'alta a alta.php): activar/desactivar comptes, canviar el rol, donar la polsera RFID i apuntar la quota
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/pagaments.php';
requereix_admin($connexio);

$tarifes = $connexio->query('SELECT * FROM tarifes ORDER BY nivell')->fetchAll();

$jo = usuari_actual()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $usuari_id = (int) ($_POST['usuari_id'] ?? 0);
    $accio = $_POST['accio'] ?? '';

    if ($accio === 'canviar_polsera') {
        // Codi (UID) del xip de la polsera, en hexadecimal: 4, 7 o 10 bytes (8, 14 o 20 xifres)
        $codi = strtoupper(preg_replace('/[^0-9a-fA-F]/', '', $_POST['polsera'] ?? ''));
        if ($codi !== '' && !in_array(strlen($codi), [8, 14, 20], true)) {
            avis('error', 'El codi de la polsera ha de tenir 8, 14 o 20 xifres hexadecimals.');
        } else {
            try {
                $connexio->prepare('UPDATE usuaris SET targeta_rfid = ? WHERE id = ?')
                         ->execute([$codi === '' ? null : $codi, $usuari_id]);
                avis('ok', $codi === '' ? 'Polsera treta.' : 'Polsera ' . $codi . ' assignada.');
            } catch (PDOException $e) {
                // La columna és UNIQUE: una polsera no pot ser de dues persones
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                avis('error', 'Aquesta polsera ja és d\'un altre usuari.');
            }
        }
    } elseif ($accio === 'cobrar') {
        // Mateixa tarifa: s'allarga el que ja tenia. Tarifa nova (o quota caducada): compta des d'avui.
        $mesos = (int) ($_POST['mesos'] ?? 0);
        $cobrat = in_array($mesos, [1, 3], true)
            ? cobra($connexio, $usuari_id, (int) ($_POST['tarifa_id'] ?? 0), $mesos, $jo)
            : null;
        avis($cobrat ? 'ok' : 'error', $cobrat
            ? 'Cobrat: ' . euros($cobrat['import']) . ' (' . $cobrat['tarifa'] . ', ' . $mesos . ($mesos === 1 ? ' mes' : ' mesos')
              . '). Quota pagada fins al ' . date('d/m/Y', strtotime($cobrat['fins'])) . '.'
            : 'No s\'ha pogut cobrar: revisa la tarifa i els mesos.');
    } elseif ($accio === 'canviar_quota') {
        // Per corregir la data a mà. Buida vol dir que no té cap quota pagada.
        $data = $_POST['quota_fins'] ?? '';
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if ($data !== '' && (!$dt || $dt->format('Y-m-d') !== $data)) {
            avis('error', 'La data de la quota no és correcta.');
        } else {
            $connexio->prepare("UPDATE usuaris SET quota_fins = ? WHERE id = ? AND rol = 'client'")
                     ->execute([$data === '' ? null : $data, $usuari_id]);
            avis('ok', 'Quota desada.');
        }
    } elseif ($usuari_id === $jo) {
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
    "SELECT u.id, u.nom, u.cognoms, u.email, u.rol, u.actiu, u.data_registre, u.targeta_rfid, u.quota_fins, u.tarifa_id,
            (SELECT nom FROM tarifes t WHERE t.id = u.tarifa_id) AS tarifa,
            (SELECT nivell FROM tarifes t WHERE t.id = u.tarifa_id) AS nivell,
            u.quota_fins >= CURDATE() AS quota_al_dia,
            u.quota_fins <= CURDATE() + INTERVAL 7 DAY AS caduca_aviat,
            (SELECT COUNT(*) FROM reserves r JOIN classes c ON c.id = r.classe_id
              WHERE r.usuari_id = u.id AND r.estat = 'confirmada' AND c.inici > NOW()) AS reserves
       FROM usuaris u
      ORDER BY u.rol, u.nom"
)->fetchAll();

$titol = 'Usuaris';
require __DIR__ . '/../inc/capcalera.php';
?>
<p><a href="./">&larr; Tornar al panell</a></p>
<p><a class="btn" href="alta.php">Donar d'alta un client</a></p>

<div class="taula">
<table>
  <tr><th>Nom</th><th class="amaga-mobil">Reserves</th><th class="amaga-mobil">Polsera</th><th>Quota</th><th>Estat</th><th></th></tr>
  <?php foreach ($usuaris as $us): ?>
    <tr>
      <td>
        <?= h($us['nom'] . ' ' . $us['cognoms']) ?>
        <?php if ($us['rol'] === 'admin'): ?><span class="etiqueta">Admin</span><?php endif; ?>
        <?php if ($us['rol'] === 'client' && $us['nivell'] >= 2): ?><br><a href="client.php?id=<?= (int) $us['id'] ?>">Pla i seguiment</a><?php endif; ?>
        <br><span class="subtil amaga-mobil"><?= h($us['email']) ?></span>
      </td>
      <td class="amaga-mobil"><?= (int) $us['reserves'] ?></td>
      <td class="amaga-mobil">
        <form method="POST" class="polsera">
          <?= camp_csrf() ?>
          <input type="hidden" name="accio" value="canviar_polsera">
          <input type="hidden" name="usuari_id" value="<?= (int) $us['id'] ?>">
          <input name="polsera" value="<?= h($us['targeta_rfid']) ?>" placeholder="sense polsera" maxlength="29" aria-label="Codi de la polsera">
          <button class="btn-secundari">Desar</button>
        </form>
      </td>
      <td>
        <?php if ($us['rol'] === 'admin'): ?>
          <span class="subtil">Personal</span>
        <?php else: ?>
          <?php if (!$us['quota_al_dia']): ?><span class="etiqueta vermella">Sense pagar</span>
          <?php elseif ($us['caduca_aviat']): ?><span class="etiqueta groga">Caduca aviat</span>
          <?php else: ?><span class="etiqueta verda">Pagada</span><?php endif; ?>
          <span class="subtil"><?= h($us['tarifa']) ?></span>
          <form method="POST" class="quota">
            <?= camp_csrf() ?>
            <input type="hidden" name="usuari_id" value="<?= (int) $us['id'] ?>">
            <input type="hidden" name="accio" value="cobrar">
            <select name="tarifa_id" aria-label="Tarifa que paga">
              <?php foreach ($tarifes as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === (int) $us['tarifa_id'] ? 'selected' : '' ?>><?= h($t['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn-secundari" name="mesos" value="1">Cobrar 1 mes</button>
            <button class="btn-secundari" name="mesos" value="3">Cobrar 3 mesos</button>
          </form>
          <form method="POST" class="quota">
            <?= camp_csrf() ?>
            <input type="hidden" name="usuari_id" value="<?= (int) $us['id'] ?>">
            <input type="date" name="quota_fins" value="<?= h($us['quota_fins']) ?>" aria-label="Quota pagada fins al">
            <button class="btn-secundari" name="accio" value="canviar_quota">Corregir la data</button>
          </form>
        <?php endif; ?>
      </td>
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
</div>
<p class="subtil">Un compte desactivat no pot entrar. Les seves reserves no s'esborren.</p>
<p class="subtil">Quota: quan un client paga a recepció, tria la seva tarifa i prem <b>Cobrar 1 mes</b> o <b>Cobrar 3 mesos</b>. Si continua amb la mateixa tarifa, els mesos s'afegeixen al final del que ja tenia pagat; si canvia de tarifa, la nova compta des d'avui. Cada cobrament queda apuntat amb el preu. La data és l'últim dia que la té pagada; a partir de l'endemà el torn de les sales no el deixa passar (a recepció sí que hi pot entrar). Els administradors (personal) entren sempre.</p>
<p class="subtil">Polsera: posa el cursor al quadre i acosta la polsera al lector RFID USB de recepció, que hi escriu el codi sol (també es pot escriure a mà). Si un client la perd, posa-hi el codi de la nova i l'antiga deixa de funcionar. Deixa-ho buit per treure-la.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
