<?php
$usuari  = (int) $argv[1];
$classe  = (int) $argv[2];
$bloqueig = ($argv[3] ?? '1') === '1';

$bd = new PDO('mysql:host=localhost;dbname=gymzero_db;charset=utf8mb4', 'root', '');
$bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$bd->beginTransaction();

$sql = 'SELECT places FROM classes WHERE id = ? AND cancelada = 0' . ($bloqueig ? ' FOR UPDATE' : '');
$st = $bd->prepare($sql);
$st->execute([$classe]);
$places = $st->fetchColumn();

$st = $bd->prepare("SELECT COUNT(*) FROM reserves WHERE classe_id = ? AND estat = 'confirmada'");
$st->execute([$classe]);
$ocupades = (int) $st->fetchColumn();

sleep(2);

if ($places === false || $ocupades >= $places) {
    $bd->rollBack();
    echo "usuari $usuari: SENSE PLAÇA (ocupades $ocupades de $places)\n";
    exit;
}

$st = $bd->prepare("INSERT INTO reserves (usuari_id, classe_id) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE estat = 'confirmada', data_cancelacio = NULL,
                                            data_reserva = CURRENT_TIMESTAMP");
$st->execute([$usuari, $classe]);
$bd->commit();
echo "usuari $usuari: RESERVA FETA (n'hi havia $ocupades de $places)\n";
