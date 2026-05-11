<?php
require_once "connexio.php";

try {
    $stmt = $connexio->query("SELECT * FROM reserves ORDER BY data_creacio DESC");
    $reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en consultar les reserves: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <title>Llistat de reserves — GYM ZERO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      margin: 0;
      font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
      background: #f5f7fb;
      color: #222;
    }

    main {
      max-width: 1100px;
      margin: 40px auto;
      padding: 20px;
    }

    .card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      overflow-x: auto;
    }

    h1 {
      color: #004aad;
      margin-top: 0;
    }

    p {
      color: #666;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 18px;
      font-size: 14px;
    }

    th, td {
      border: 1px solid #dce3f2;
      padding: 9px;
      text-align: left;
    }

    th {
      background: #e3efff;
      color: #004aad;
    }

    .btn {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 16px;
      border-radius: 999px;
      background: #004aad;
      color: white;
      text-decoration: none;
      font-weight: 700;
    }

    .empty {
      background: #fff6df;
      color: #9a6700;
      padding: 12px;
      border-radius: 8px;
      margin-top: 16px;
    }
  </style>
</head>

<body>
  <main>
    <div class="card">
      <h1>Llistat de reserves — GYM ZERO</h1>

      <p>
        Aquesta pantalla mostra les reserves guardades a la base de dades.
      </p>

      <a class="btn" href="reserva.php">Nova reserva</a>

      <?php if (count($reserves) === 0): ?>
        <div class="empty">Encara no hi ha reserves registrades.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Email</th>
              <th>Activitat</th>
              <th>Data</th>
              <th>Hora</th>
              <th>Observacions</th>
              <th>Creació</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reserves as $reserva): ?>
              <tr>
                <td><?php echo htmlspecialchars($reserva["id"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["nom"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["email"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["activitat"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["data_reserva"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["hora_reserva"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["observacions"]); ?></td>
                <td><?php echo htmlspecialchars($reserva["data_creacio"]); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
