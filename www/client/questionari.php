<?php
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';
require_once __DIR__ . '/../inc/questionari.php';
requereix_tarifa($connexio, 2);

$u = usuari_actual();
$d = questionari();
$ui = $d['ui'];
$errors = [];
$pagina = 0;

$st = $connexio->prepare('SELECT respostes, enviat FROM questionaris WHERE usuari_id = ? ORDER BY enviat DESC, id DESC LIMIT 1');
$st->execute([$u['id']]);
$anterior = $st->fetch();
$respostes_anteriors = $anterior ? json_decode($anterior['respostes'], true) : [];
$estat = estat_de_respostes($respostes_anteriors);
$idioma = idioma_questionari($_GET['lang'] ?? (string) array_search($respostes_anteriors['Idioma del formulario'] ?? '', $d['names'], true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprova_csrf();
    $estat = estat_del_post($_POST);
    $pagina = (int) ($_POST['pagina'] ?? 0);
    if (isset($_POST['idioma'])) {
        $idioma = idioma_questionari($_POST['idioma']);
    } else {
        $idioma = idioma_questionari($_POST['lang'] ?? '');
        $errors = errors_questionari($estat, $idioma);
        if (!$errors) {
            $connexio->prepare('INSERT INTO questionaris (usuari_id, respostes, consentiment) VALUES (?, ?, 1)')
                     ->execute([$u['id'], json_encode(respostes_questionari($estat, $idioma), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            avis('ok', tq($d['form']['gracias'], $idioma));
            header('Location: ./');
            exit;
        }
    }
}

function pinta_pregunta($i, $q, $v, $error, $L)
{
    $ui = questionari()['ui'];
    $nom = 'p[' . $i . ']';
    $atributs = ' data-tipus="' . $q['type'] . '"' . (!empty($q['req']) ? ' data-req' : '')
        . (!empty($q['nav']) ? " data-nav='" . h(json_encode($q['nav'])) . "'" : '')
        . (!empty($q['num']) ? ' data-min="' . $q['num'][0] . '" data-max="' . $q['num'][1] . '" data-error-num="' . h(tq($q['num'][2], $L)) . '"' : '')
        . (!empty($q['check4']) ? ' data-check4' : '');
    $un_camp = in_array($q['type'], ['text', 'para', 'date', 'list'], true);
    echo '<div class="pregunta' . ($error ? ' err' : '') . '" id="q' . $i . '"' . $atributs
        . ($un_camp ? '' : ' role="group" aria-labelledby="t' . $i . '"') . '>';
    $titol = h(tq($q['title'], $L)) . (!empty($q['req']) ? ' <span class="obligatoria">*</span>' : '');
    echo $un_camp ? '<label class="enunciat" id="t' . $i . '" for="c' . $i . '">' . $titol . '</label>'
                  : '<p class="enunciat" id="t' . $i . '">' . $titol . '</p>';
    if (!empty($q['help'])) {
        echo '<p class="ajuda">' . h(tq($q['help'], $L)) . '</p>';
    }

    switch ($q['type']) {
        case 'text':
            echo '<input type="text" id="c' . $i . '" name="' . $nom . '" value="' . h($v) . '" maxlength="300"'
                . (!empty($q['num']) ? ' inputmode="decimal" placeholder="' . $q['num'][0] . ' – ' . $q['num'][1] . '"'
                                     : ' placeholder="' . h(tq($ui['type_ph'], $L)) . '"') . '>';
            break;
        case 'para':
            echo '<textarea id="c' . $i . '" name="' . $nom . '" maxlength="5000" placeholder="' . h(tq($ui['type_ph'], $L)) . '">' . h($v) . '</textarea>';
            break;
        case 'date':
            echo '<input type="date" id="c' . $i . '" name="' . $nom . '" value="' . h($v) . '" min="1920-01-01" max="' . date('Y-m-d') . '">';
            break;
        case 'list':
            echo '<select id="c' . $i . '" name="' . $nom . '"><option value="">' . h(tq($ui['choose'], $L)) . '…</option>';
            foreach (hores_questionari($q['hours'], $L) as $k => $hora) {
                echo '<option value="' . $k . '"' . ($v === $k ? ' selected' : '') . '>' . h($hora) . '</option>';
            }
            echo '</select>';
            break;
        case 'grid':
            foreach ($q['rows'] as $f => $fila) {
                echo '<div class="fila-graella"><label for="c' . $i . '_' . $f . '">' . h(tq($fila, $L)) . '</label>'
                    . '<select id="c' . $i . '_' . $f . '" name="' . $nom . '[' . $f . ']"><option value="">' . h(tq($ui['choose'], $L)) . '…</option>';
                foreach ($q['cols'] as $c => $col) {
                    echo '<option value="' . $c . '"' . (isset($v[$f]) && $v[$f] === $c ? ' selected' : '') . '>' . h(tq($col, $L)) . '</option>';
                }
                echo '</select></div>';
            }
            break;
        case 'scale':
            echo '<div class="escala">';
            for ($n = 1; $n <= 10; $n++) {
                echo '<label class="opcio"><input type="radio" name="' . $nom . '" value="' . $n . '"' . ($v === $n ? ' checked' : '') . '>' . $n . '</label>';
            }
            echo '</div><p class="escala-extrems"><span>1 = ' . h(tq($q['lo'], $L)) . '</span><span>10 = ' . h(tq($q['hi'], $L)) . '</span></p>';
            break;
        case 'mc':
        case 'cb':
            $multi = $q['type'] === 'cb';
            $tipus = $multi ? 'checkbox' : 'radio';
            echo '<div class="opcions">';
            foreach ($q['choices'] as $c => $text) {
                $marcat = $multi ? in_array($c, $v['c'] ?? [], true) : (isset($v['c']) && $v['c'] === $c);
                echo '<label class="opcio"><input type="' . $tipus . '" name="' . $nom . ($multi ? '[]' : '') . '" value="' . $c . '"'
                    . ($marcat ? ' checked' : '') . '>' . h(tq($text, $L)) . '</label>';
            }
            if (!empty($q['other'])) {
                $altres = $v['o'] ?? null;
                echo '<label class="opcio altres"><input type="' . $tipus . '" name="' . $nom . ($multi ? '[]' : '') . '" value="o"'
                    . ($altres !== null ? ' checked' : '') . '>' . h(tq($ui['other'], $L))
                    . ': <input type="text" name="o[' . $i . ']" value="' . h($altres) . '" maxlength="300" aria-label="' . h(tq($ui['other'], $L)) . '"'
                    . ' placeholder="' . h(mb_strtolower(tq($ui['other_ph'], $L))) . '"></label>';
            }
            echo '</div>';
            break;
    }
    echo '<p class="msg">' . h($error) . '</p></div>';
}

$titol = tq($d['form']['titulo'], $idioma);
require __DIR__ . '/../inc/capcalera.php';
$pagines = pagines_questionari();
$textos = ['required' => tq($ui['required'], $idioma), 'rows' => tq($ui['rows'], $idioma), 'check4' => tq($d['v_check'], $idioma),
           'fix' => tq($ui['fix'], $idioma), 'step' => tq($ui['step'], $idioma)];
?>
<p><a href="./">&larr; <?= h(tq($ui['area'], $idioma)) ?></a></p>

<form method="POST" class="questionari" lang="<?= $idioma ?>" novalidate data-textos='<?= h(json_encode($textos, JSON_UNESCAPED_UNICODE)) ?>'>
  <?= camp_csrf() ?>
  <input type="hidden" name="lang" value="<?= $idioma ?>">
  <input type="hidden" name="pagina" value="<?= $pagina ?>">

  <div class="idiomes" role="group" aria-label="<?= h(tq($ui['idioma'], $idioma)) ?>">
    <?php foreach (IDIOMES_QUESTIONARI as $l): ?>
      <button type="submit" name="idioma" value="<?= $l ?>" lang="<?= $l ?>"<?= $l === $idioma ? ' aria-pressed="true" class="actiu"' : ' aria-pressed="false"' ?>><?= h($d['names'][$l]) ?></button>
    <?php endforeach; ?>
  </div>

  <div class="intro-questionari">
    <?php foreach (explode("\n\n", tq($d['form']['desc'], $idioma)) as $paragraf): ?>
      <p><?= h($paragraf) ?></p>
    <?php endforeach; ?>
    <?php if ($anterior): ?>
      <p class="subtil"><?= h(str_replace('{data}', date('d/m/Y', strtotime($anterior['enviat'])), tq($ui['anterior'], $idioma))) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($errors): ?>
    <div class="error" role="alert"><?= h(tq($ui['fix'], $idioma)) ?></div>
  <?php endif; ?>

  <div class="progres" hidden><div></div></div>

  <?php foreach ($pagines as $n => $p): ?>
    <section class="full-questionari" id="pg<?= $n ?>"<?= $p['id'] ? ' data-id="' . h($p['id']) . '"' : '' ?>>
      <?php if ($p['titol']): ?>
        <p class="pas" hidden></p>
        <h2><?= h(tq($p['titol'], $idioma)) ?></h2>
        <?php if ($p['ajuda']): ?><p class="subtil"><?= h(tq($p['ajuda'], $idioma)) ?></p><?php endif; ?>
      <?php endif; ?>
      <?php foreach ($p['preguntes'] as $i):
          $q = $d['q'][$i];
          if ($q['type'] === 'header'): ?>
            <h2><?= h(tq($q['title'], $idioma)) ?></h2>
          <?php else:
              pinta_pregunta($i, $q, $estat[$i] ?? null, $errors[$i] ?? null, $idioma);
          endif;
      endforeach; ?>
    </section>
  <?php endforeach; ?>

  <p class="subtil"><a href="../legal/"><?= h(tq($ui['legal'], $idioma)) ?></a></p>

  <div class="botons-questionari">
    <button type="button" class="btn-secundari" data-enrere hidden>&lsaquo; <?= h(tq($ui['back'], $idioma)) ?></button>
    <button type="button" data-seguent hidden><?= h(tq($ui['next'], $idioma)) ?> &rsaquo;</button>
    <button type="submit" data-enviar><?= h(tq($ui['send'], $idioma)) ?> &check;</button>
  </div>
</form>
<script src="../js/questionari.js?v=<?= filemtime(__DIR__ . '/../js/questionari.js') ?>"></script>
<?php require __DIR__ . '/../inc/peu.php'; ?>
