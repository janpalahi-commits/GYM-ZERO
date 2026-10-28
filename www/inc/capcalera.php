<?php
// Capçalera comuna. Abans d'incloure-la cal definir $titol.
// Les pàgines que són dins d'una carpeta (reserves/, client/...) no cal que facin res més.
// La portada (index.php) és a l'arrel: posa $arrel = '' perquè els enllaços no portin ../
// i $portada = true perquè no surti el títol (ella mateixa dibuixa les seves seccions).
$u = usuari_actual();
$arrel = $arrel ?? '../';
$portada = $portada ?? false;

// Marca a la barra la secció on som
$seccio = basename(dirname($_SERVER['SCRIPT_NAME']));
function enllac($href, $text, $carpeta)
{
    global $seccio;
    $actual = $seccio === $carpeta ? ' aria-current="page"' : '';
    return '<a href="' . $href . '"' . $actual . '>' . $text . '</a>';
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $portada ? 'Gym Zero' : h($titol) . ' · Gym Zero' ?></title>
  <link rel="icon" href="<?= $arrel ?>img/icona.svg" type="image/svg+xml">
  <link rel="preload" href="<?= $arrel ?>fonts/saira-condensed-800.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= $arrel ?>css/estil.css">
</head>
<body<?= $portada ? ' class="portada"' : '' ?>>
  <header class="capcalera">
    <div class="wrap">
      <a class="logo" href="<?= $arrel ?: './' ?>">Gym<em>Zero</em></a>
      <nav>
        <a href="<?= $arrel ?: './' ?>"<?= $portada ? ' aria-current="page"' : '' ?>>Inici</a>
        <?= enllac($arrel . 'activitats/', 'Activitats', 'activitats') ?>
        <?= enllac($arrel . 'reserves/', 'Classes', 'reserves') ?>
        <?= enllac($arrel . 'calculs/', 'Càlculs', 'calculs') ?>
        <?php if ($u): ?>
          <?= enllac($arrel . 'client/', 'La meva àrea', 'client') ?>
          <?php if ($u['rol'] === 'admin'): ?>
            <?= enllac($arrel . 'admin/', 'Administració', 'admin') ?>
          <?php endif; ?>
          <span class="qui"><?= h($u['nom']) ?></span>
          <a href="<?= $arrel ?>compte/logout.php">Sortir</a>
        <?php else: ?>
          <a class="entrar" href="<?= $arrel ?>compte/login.php">Entrar</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>
<?php if (!$portada): ?>
  <main class="wrap pagina">
    <div class="card">
      <h1><?= h($titol) ?></h1>
      <?php mostra_avis(); ?>
<?php endif; ?>
