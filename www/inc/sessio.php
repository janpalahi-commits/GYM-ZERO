<?php
// Sessió de l'usuari i funcions que fan servir totes les pàgines.

date_default_timezone_set('Europe/Madrid');

session_set_cookie_params([
    'httponly' => true,     // JavaScript no pot llegir la cookie de sessió
    'samesite' => 'Lax',
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
        http_response_code(403);
        die('No tens permís per entrar a aquesta pàgina.');
    }
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
        http_response_code(400);
        die('El formulari ha caducat. Torna enrere i recarrega la pàgina.');
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
