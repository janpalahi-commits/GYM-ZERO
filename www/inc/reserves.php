<?php
// Lògica de les reserves: reservar i cancel·lar.
// Les dues funcions retornen [true|false, missatge per a l'usuari].

function reservar(PDO $bd, int $usuari_id, int $classe_id)
{
    $bd->beginTransaction();
    try {
        // 1. Bloquejar la classe. Fins al COMMIT, qualsevol altra reserva
        //    d'aquesta mateixa classe s'espera aquí (control de concurrència).
        $st = $bd->prepare(
            'SELECT c.places, c.inici, c.cancelada, a.nom, c.inici <= NOW() AS comencada
               FROM classes c JOIN activitats a ON a.id = c.activitat_id
              WHERE c.id = ? FOR UPDATE'
        );
        $st->execute([$classe_id]);
        $classe = $st->fetch();

        if (!$classe || $classe['cancelada']) {
            $bd->rollBack();
            return [false, 'Aquesta classe no existeix o està cancel·lada.'];
        }
        if ($classe['comencada']) {
            $bd->rollBack();
            return [false, 'Aquesta classe ja ha començat.'];
        }

        // 2. Té la quota pagada fins al dia de la classe? (el personal no en paga)
        $st = $bd->prepare(
            'SELECT rol, quota_fins, quota_fins >= DATE(?) AS cobreix FROM usuaris WHERE id = ?'
        );
        $st->execute([$classe['inici'], $usuari_id]);
        $quota = $st->fetch();
        if ($quota['rol'] !== 'admin' && !$quota['cobreix']) {
            $bd->rollBack();
            if ($quota['quota_fins'] !== null && $quota['quota_fins'] >= date('Y-m-d')) {
                return [false, 'La teva quota acaba el ' . date('d/m', strtotime($quota['quota_fins']))
                    . ', abans d\'aquesta classe. Renova-la a recepció per poder-la reservar.'];
            }
            return [false, 'Per reservar has de tenir la quota pagada. Passa per recepció.'];
        }

        // 3. L'usuari ja hi té plaça?
        $st = $bd->prepare("SELECT estat FROM reserves WHERE usuari_id = ? AND classe_id = ?");
        $st->execute([$usuari_id, $classe_id]);
        if ($st->fetchColumn() === 'confirmada') {
            $bd->rollBack();
            return [false, 'Ja tens una reserva per a aquesta classe.'];
        }

        // 4. Queden places?
        $st = $bd->prepare("SELECT COUNT(*) FROM reserves WHERE classe_id = ? AND estat = 'confirmada'");
        $st->execute([$classe_id]);
        if ((int) $st->fetchColumn() >= (int) $classe['places']) {
            $bd->rollBack();
            return [false, 'No queden places per a aquesta classe.'];
        }

        // 5. Guardar la reserva (si l'havia cancel·lat abans, es torna a confirmar)
        $st = $bd->prepare(
            "INSERT INTO reserves (usuari_id, classe_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE estat = 'confirmada', data_cancelacio = NULL,
                                     data_reserva = CURRENT_TIMESTAMP"
        );
        $st->execute([$usuari_id, $classe_id]);

        $text = 'Reserva confirmada: ' . $classe['nom'] . ', ' . date('d/m/Y H:i', strtotime($classe['inici'])) . '.';
        $st = $bd->prepare("INSERT INTO notificacions (usuari_id, tipus, missatge) VALUES (?, 'reserva', ?)");
        $st->execute([$usuari_id, $text]);

        $bd->commit();
        return [true, $text];
    } catch (Throwable $e) {
        $bd->rollBack();
        throw $e;
    }
}

function cancelar(PDO $bd, int $usuari_id, int $classe_id)
{
    $st = $bd->prepare(
        "SELECT a.nom, c.inici, c.inici <= NOW() AS comencada
           FROM reserves r
           JOIN classes c    ON c.id = r.classe_id
           JOIN activitats a ON a.id = c.activitat_id
          WHERE r.usuari_id = ? AND r.classe_id = ? AND r.estat = 'confirmada'"
    );
    $st->execute([$usuari_id, $classe_id]);
    $reserva = $st->fetch();

    if (!$reserva) {
        return [false, 'No tens cap reserva activa per a aquesta classe.'];
    }
    if ($reserva['comencada']) {
        return [false, 'No es pot cancel·lar una classe que ja ha començat.'];
    }

    $st = $bd->prepare(
        "UPDATE reserves SET estat = 'cancelada', data_cancelacio = NOW()
          WHERE usuari_id = ? AND classe_id = ?"
    );
    $st->execute([$usuari_id, $classe_id]);

    $text = 'Reserva cancel·lada: ' . $reserva['nom'] . ', ' . date('d/m/Y H:i', strtotime($reserva['inici'])) . '.';
    $st = $bd->prepare("INSERT INTO notificacions (usuari_id, tipus, missatge) VALUES (?, 'cancelacio', ?)");
    $st->execute([$usuari_id, $text]);

    return [true, $text];
}
