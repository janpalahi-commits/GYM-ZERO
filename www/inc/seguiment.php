<?php
// Seguiment de la tarifa més alta: una revisió cada 2 setmanes i una conversa amb l'entrenador.
// Ho fan servir la pàgina del client (client/seguiment.php) i la fitxa de l'entrenador (admin/client.php).

const DIES_REVISIO = 14;

// Data a partir de la qual el client pot enviar la propera revisió (la primera, des del primer dia)
function propera_revisio(PDO $bd, $usuari_id)
{
    $st = $bd->prepare('SELECT MAX(data) FROM revisions WHERE usuari_id = ?');
    $st->execute([$usuari_id]);
    $ultima = $st->fetchColumn();
    return $ultima ? date('Y-m-d', strtotime($ultima . ' +' . DIES_REVISIO . ' days')) : date('Y-m-d');
}

function revisions_client(PDO $bd, $usuari_id)
{
    $st = $bd->prepare(
        'SELECT r.*, u.nom AS entrenador FROM revisions r LEFT JOIN usuaris u ON u.id = r.respost_per
          WHERE r.usuari_id = ? ORDER BY r.data DESC, r.id DESC'
    );
    $st->execute([$usuari_id]);
    return $st->fetchAll();
}

function missatges_client(PDO $bd, $usuari_id)
{
    $st = $bd->prepare(
        'SELECT m.*, a.nom AS autor, a.rol AS rol_autor FROM missatges m LEFT JOIN usuaris a ON a.id = m.autor_id
          WHERE m.usuari_id = ? ORDER BY m.data, m.id'
    );
    $st->execute([$usuari_id]);
    return $st->fetchAll();
}

// Desa un missatge de la conversa del client $usuari_id. Torna l'error o null si ha anat bé.
function envia_missatge(PDO $bd, $usuari_id, $autor_id, $text)
{
    $text = trim((string) $text);
    if ($text === '' || mb_strlen($text) > 1000) {
        return 'El missatge no pot estar buit ni passar de 1000 caràcters.';
    }
    $bd->prepare('INSERT INTO missatges (usuari_id, autor_id, text) VALUES (?, ?, ?)')->execute([$usuari_id, $autor_id, $text]);
    return null;
}

// La conversa: els missatges propis a la dreta, els de l'altra part a l'esquerra
function mostra_conversa(array $missatges, $jo_id)
{
    if (!$missatges) {
        return '<p class="subtil">Encara no hi ha cap missatge.</p>';
    }
    $html = '<ol class="conversa">';
    foreach ($missatges as $m) {
        $meu = (int) $m['autor_id'] === (int) $jo_id;
        $qui = $meu ? 'Tu' : ($m['autor'] ?? 'Gym Zero') . ($m['rol_autor'] === 'admin' ? ' (entrenador)' : '');
        $html .= '<li class="' . ($meu ? 'meu' : 'seu') . '"><b>' . h($qui) . '</b> <small>' . date('d/m H:i', strtotime($m['data']))
            . '</small><p>' . nl2br(h($m['text'])) . '</p></li>';
    }
    return $html . '</ol>';
}
