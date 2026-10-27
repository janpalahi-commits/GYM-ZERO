<?php
// Capçalera comuna. Abans d'incloure-la cal definir $titol.
// Les pàgines que són dins d'una carpeta (reserves/, client/...) no cal que facin res més.
// La portada (index.php) és a l'arrel i posa $arrel = '' perquè els enllaços no portin ../
$u = usuari_actual();
$arrel = $arrel ?? '../';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($titol) ?> — GYM ZERO</title>
  <link rel="stylesheet" href="<?= $arrel ?>css/area.css">
</head>
<body>
  <header class="barra">
    <a class="logo" href="<?= $arrel ?: './' ?>">GYM ZERO</a>
    <nav>
      <a href="<?= $arrel ?>activitats/">Activitats</a>
      <a href="<?= $arrel ?>calculs/">Càlculs</a>
      <a href="<?= $arrel ?>reserves/">Classes</a>
      <?php if ($u): ?>
        <a href="<?= $arrel ?>client/">La meva àrea</a>
        <?php if ($u['rol'] === 'admin'): ?>
          <a href="<?= $arrel ?>admin/">Administració</a>
        <?php endif; ?>
        <span class="qui"><?= h($u['nom']) ?></span>
        <a href="<?= $arrel ?>compte/logout.php">Sortir</a>
      <?php else: ?>
        <a href="<?= $arrel ?>compte/login.php">Entrar</a>
      <?php endif; ?>
    </nav>
  </header>
  <main>
    <div class="card">
      <h1><?= h($titol) ?></h1>
      <?php mostra_avis(); ?>
