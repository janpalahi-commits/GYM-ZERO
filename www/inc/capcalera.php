<?php
// Capçalera comuna. Abans d'incloure-la cal definir $titol.
$u = usuari_actual();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($titol) ?> — GYM ZERO</title>
  <link rel="stylesheet" href="../css/area.css">
</head>
<body>
  <header class="barra">
    <a class="logo" href="../index.html">GYM ZERO</a>
    <nav>
      <a href="../reserves/">Classes</a>
      <?php if ($u): ?>
        <a href="../client/">La meva àrea</a>
        <?php if ($u['rol'] === 'admin'): ?>
          <a href="../admin/">Administració</a>
        <?php endif; ?>
        <span class="qui"><?= h($u['nom']) ?></span>
        <a href="../compte/logout.php">Sortir</a>
      <?php else: ?>
        <a href="../compte/login.php">Entrar</a>
        <a href="../compte/registre.php">Registrar-se</a>
      <?php endif; ?>
    </nav>
  </header>
  <main>
    <div class="card">
      <h1><?= h($titol) ?></h1>
      <?php mostra_avis(); ?>
