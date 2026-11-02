<?php
// Sessió de l'usuari i funcions que fan servir totes les pàgines.

date_default_timezone_set('Europe/Madrid');

session_set_cookie_params([
    'httponly' => true,     // JavaScript no pot llegir la cookie de sessió
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),   // amb https, la cookie només viatja xifrada
]);
session_start();

// Escapa text abans d'escriure'l a l'HTML (evita XSS)
function h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Usuari connectat, o null si no n'hi ha cap
function usuari_actual()
{
    return $_SESSION['usuari'] ?? null;
}

// Si no hi ha ningú connectat, l'envia al login
function requereix_login()
{
    if (!usuari_actual()) {
        header('Location: ../compte/login.php');
        exit;
    }
}

// Només per a administradors. El rol es torna a mirar a la base de dades,
// així si a algú li treuen el rol d'admin o el desactiven, deixa d'entrar a l'instant.
function requereix_admin(PDO $bd)
{
    requereix_login();
    $st = $bd->prepare('SELECT rol, actiu FROM usuaris WHERE id = ?');
    $st->execute([usuari_actual()['id']]);
    $fila = $st->fetch();
    if (!$fila || $fila['rol'] !== 'admin' || !$fila['actiu']) {
        pagina_error(403, 'No tens permís per entrar a aquesta pàgina.');
    }
}

// Tarifa del client i si té la quota al dia. Es mira a la base de dades cada vegada:
// si recepció li canvia la tarifa o li caduca la quota, es nota a l'instant.
function tarifa_usuari(PDO $bd, $usuari_id)
{
    $st = $bd->prepare(
        'SELECT u.rol, u.actiu, u.quota_fins, u.quota_fins >= CURDATE() AS al_dia,
                t.id AS tarifa_id, t.nom AS tarifa, t.nivell
           FROM usuaris u JOIN tarifes t ON t.id = u.tarifa_id
          WHERE u.id = ?'
    );
    $st->execute([$usuari_id]);
    return $st->fetch();
}

// Serveis de pagament: el pla personalitzat i la llibreta (nivell 2) i el seguiment (nivell 3).
// Cal tenir una tarifa d'aquest nivell i la quota pagada. Els administradors (l'entrenador) hi entren sempre.
function requereix_tarifa(PDO $bd, $nivell)
{
    requereix_login();
    $t = tarifa_usuari($bd, usuari_actual()['id']);
    if (!$t || !$t['actiu']) {
        pagina_error(403, 'No tens permís per entrar a aquesta pàgina.');
    }
    if ($t['rol'] === 'admin') {
        return $t;
    }
    if ($t['nivell'] < $nivell) {
        $st = $bd->prepare('SELECT nom, preu_mes FROM tarifes WHERE nivell = ?');
        $st->execute([$nivell]);
        $cal = $st->fetch();
        pagina_error(403, 'Aquest servei és de la tarifa ' . $cal['nom'] . ' (' . number_format($cal['preu_mes'], 0, ',', '.')
            . ' € al mes). Ara tens la tarifa ' . $t['tarifa'] . '. Si la vols canviar, demana-ho a recepció.', 'Servei de la tarifa ' . $cal['nom']);
    }
    if (!$t['al_dia']) {
        pagina_error(403, 'Tens la quota caducada. Quan la paguis a recepció, tornaràs a tenir aquest servei.', 'Quota caducada');
    }
    return $t;
}

// Token CSRF: cada formulari porta un codi secret de la sessió.
// Així una altra web no pot enviar formularis en nom de l'usuari.
function token_csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function camp_csrf()
{
    return '<input type="hidden" name="csrf" value="' . token_csrf() . '">';
}

function comprova_csrf()
{
    if (!hash_equals(token_csrf(), $_POST['csrf'] ?? '')) {
        pagina_error(400, 'El formulari ha caducat. Torna enrere i recarrega la pàgina.');
    }
}

// Missatges que es mostren una sola vegada després de redirigir
function avis($tipus, $text)
{
    $_SESSION['avis'] = ['tipus' => $tipus, 'text' => $text];
}

function mostra_avis()
{
    if (!empty($_SESSION['avis'])) {
        $a = $_SESSION['avis'];
        unset($_SESSION['avis']);
        echo '<div class="' . ($a['tipus'] === 'ok' ? 'ok' : 'error') . '">' . h($a['text']) . '</div>';
    }
}

// Pàgina d'error amb el disseny de la web, en lloc d'una línia de text sola.
// Es fa servir abans d'haver escrit res a la pàgina.
function pagina_error($codi, $text, $titol = null)
{
    http_response_code($codi);
    $titol ??= [400 => 'Formulari caducat', 403 => 'Sense permís', 404 => 'No l\'hem trobat'][$codi] ?? 'Hi ha hagut un error';
    // La portada és a l'arrel; la resta de pàgines són dins d'una carpeta
    $arrel = dirname($_SERVER['SCRIPT_FILENAME']) === dirname(__DIR__) ? '' : '../';
    require __DIR__ . '/capcalera.php';
    echo '<p>' . h($text) . '</p>';
    echo '<a class="btn" href="' . ($arrel ?: './') . '">Tornar a l\'inici</a>';
    require __DIR__ . '/peu.php';
    exit;
}

// Color de cada activitat (el mateix a la portada, a Activitats i a Classes).
// Va per l'id, així no cal guardar-lo a la base de dades.
// El segon valor diu si el text ha de ser blanc perquè el fons és fosc.
function color_activitat($id)
{
    $colors = [
        ['var(--vermell)', true], ['var(--pista)', true], ['var(--sorra)', false], ['var(--cel)', false],
        ['var(--lila)', false], ['var(--forca)', true], ['var(--cames)', false], ['var(--cardio)', true],
    ];
    return $colors[($id - 1) % count($colors)];
}
