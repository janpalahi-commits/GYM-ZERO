<?php
$missatge = $_GET["msg"] ?? "";
$error = $_GET["error"] ?? "";
?>

<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <title>Reserva de classe — GYM ZERO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      margin: 0;
      font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
      background: #f5f7fb;
      color: #222;
    }

    main {
      max-width: 760px;
      margin: 40px auto;
      padding: 20px;
    }

    .card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }

    h1 {
      color: #004aad;
      margin-top: 0;
    }

    p {
      color: #666;
      line-height: 1.6;
    }

    label {
      display: block;
      margin-top: 14px;
      font-weight: 600;
      color: #444;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border-radius: 8px;
      border: 1px solid #ccd6eb;
      font-size: 15px;
      box-sizing: border-box;
    }

    textarea {
      min-height: 90px;
      resize: vertical;
    }

    button, .btn {
      display: inline-block;
      margin-top: 18px;
      padding: 11px 18px;
      border-radius: 999px;
      border: none;
      background: #004aad;
      color: white;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      font-size: 15px;
    }

    .btn-secondary {
      background: white;
      color: #004aad;
      border: 1px solid #ccd6eb;
      margin-left: 8px;
    }

    .ok {
      background: #e0f7e9;
      color: #117a2f;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
    }

    .error {
      background: #ffecec;
      color: #b42318;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
    }

    .footer {
      margin-top: 20px;
      font-size: 13px;
      color: #777;
    }
  </style>
</head>

<body>
  <main>
    <div class="card">
      <h1>Reserva de classe — GYM ZERO</h1>

      <p>
        Aquesta funcionalitat forma part del projecte GYM ZERO. Permet registrar una reserva
        de classe mitjançant un formulari PHP connectat a una base de dades MySQL/MariaDB.
      </p>

      <?php if ($missatge): ?>
        <div class="ok"><?php echo htmlspecialchars($missatge); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="processar_reserva.php" method="POST">
        <label for="nom">Nom complet</label>
        <input type="text" id="nom" name="nom" required>

        <label for="email">Correu electrònic</label>
        <input type="email" id="email" name="email" required>

        <label for="activitat">Activitat</label>
        <select id="activitat" name="activitat" required>
          <option value="">Selecciona una activitat</option>
          <option value="Musculació">Musculació</option>
          <option value="Cross Training">Cross Training</option>
          <option value="Powerlifting">Powerlifting</option>
          <option value="Hipertrofia">Hipertrofia</option>
          <option value="Mobilitat">Mobilitat</option>
        </select>

        <label for="data_reserva">Data de la reserva</label>
        <input type="date" id="data_reserva" name="data_reserva" required>

        <label for="hora_reserva">Hora</label>
        <input type="time" id="hora_reserva" name="hora_reserva" required>

        <label for="observacions">Observacions</label>
        <textarea id="observacions" name="observacions" placeholder="Ex: primera vegada, preferència d'entrenador, objectiu..."></textarea>

        <button type="submit">Enviar reserva</button>
        <a class="btn btn-secondary" href="llistar_reserves.php">Veure reserves</a>
      </form>

      <div class="footer">
        Projecte Intermodular SMX2 — Funcionalitat PHP + Base de dades
      </div>
    </div>
  </main>
</body>
</html>

