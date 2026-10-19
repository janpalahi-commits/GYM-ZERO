<?php
// GYM-ZERO · Prova de concurrència de reserves (Fase 2)
//
// Ús (des de la terminal, amb la BD i les dades de prova importades):
//   php prova_concurrencia.php <usuari_id> <classe_id> <amb_bloqueig: 1|0>
//
// El script obre una transacció, mira quantes places queden, espera 2 segons
// (per simular que molta gent reserva alhora) i després insereix la reserva.
// Si es llancen diversos alhora:
//   - amb_bloqueig = 0 -> tots veuen places lliures i la classe s'omple de més
//   - amb_bloqueig = 1 -> SELECT ... FOR UPDATE els posa en cua i no se'n passa cap

$usuari  = (int) $argv[1];
$classe  = (int) $argv[2];
$bloqueig = ($argv[3] ?? '1') === '1';

$bd = new PDO('mysql:host=localhost;dbname=gymzero_db;charset=utf8mb4', 'root', '');
$bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$bd->beginTransaction();

// 1. Llegir la classe. Amb FOR UPDATE, la fila queda bloquejada fins al COMMIT:
//    qualsevol altra reserva de la mateixa classe s'espera aquí.
$sql = 'SELECT places FROM classes WHERE id = ? AND cancelada = 0' . ($bloqueig ? ' FOR UPDATE' : '');
$st = $bd->prepare($sql);
$st->execute([$classe]);
$places = $st->fetchColumn();

// 2. Comptar les reserves confirmades
$st = $bd->prepare("SELECT COUNT(*) FROM reserves WHERE classe_id = ? AND estat = 'confirmada'");
$st->execute([$classe]);
$ocupades = (int) $st->fetchColumn();

sleep(2); // simula molts usuaris reservant al mateix moment

if ($places === false || $ocupades >= $places) {
    $bd->rollBack();
    echo "usuari $usuari: SENSE PLAÇA (ocupades $ocupades de $places)\n";
    exit;
}

// 3. Inserir la reserva (si existia cancel·lada, es torna a confirmar)
$st = $bd->prepare("INSERT INTO reserves (usuari_id, classe_id) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE estat = 'confirmada', data_cancelacio = NULL,
                                            data_reserva = CURRENT_TIMESTAMP");
$st->execute([$usuari, $classe]);
$bd->commit();
echo "usuari $usuari: RESERVA FETA (n'hi havia $ocupades de $places)\n";
