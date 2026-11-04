<?php
date_default_timezone_set('Europe/Madrid');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

function h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function usuari_actual()
{
    return $_SESSION['usuari'] ?? null;
}

function requereix_login()
{
    if (!usuari_actual()) {
        header('Location: ../compte/login.php');
        exit;
    }
}

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

function pagina_error($codi, $text, $titol = null)
{
    http_response_code($codi);
    $titol ??= [400 => 'Formulari caducat', 403 => 'Sense permís', 404 => 'No l\'hem trobat'][$codi] ?? 'Hi ha hagut un error';
    $arrel = dirname($_SERVER['SCRIPT_FILENAME']) === dirname(__DIR__) ? '' : '../';
    require __DIR__ . '/capcalera.php';
    echo '<p>' . h($text) . '</p>';
    echo '<a class="btn" href="' . ($arrel ?: './') . '">Tornar a l\'inici</a>';
    require __DIR__ . '/peu.php';
    exit;
}

function color_activitat($id)
{
    $colors = [
        ['var(--vermell)', true], ['var(--pista)', true], ['var(--sorra)', false], ['var(--cel)', false],
        ['var(--lila)', false], ['var(--forca)', true], ['var(--cames)', false], ['var(--cardio)', true],
    ];
    return $colors[($id - 1) % count($colors)];
}
