<?php
function preu_tarifa(array $tarifa, $mesos)
{
    return $mesos === 3 ? $tarifa['preu_3mesos'] : $tarifa['preu_mes'];
}

function euros($import)
{
    return number_format($import, 2, ',', '.') . ' €';
}

function cobra(PDO $bd, $usuari_id, $tarifa_id, $mesos, $cobrat_per)
{
    if (!in_array($mesos, [1, 3], true)) {
        throw new InvalidArgumentException('Només es cobra 1 o 3 mesos.');
    }
    $bd->beginTransaction();
    try {
        $st = $bd->prepare('SELECT * FROM tarifes WHERE id = ?');
        $st->execute([$tarifa_id]);
        $tarifa = $st->fetch();

        $st = $bd->prepare("SELECT tarifa_id, quota_fins, quota_fins >= CURDATE() AS al_dia
                              FROM usuaris WHERE id = ? AND rol = 'client' FOR UPDATE");
        $st->execute([$usuari_id]);
        $u = $st->fetch();
        if (!$tarifa || !$u) {
            $bd->rollBack();
            return null;
        }

        $continua = $u['al_dia'] && (int) $u['tarifa_id'] === (int) $tarifa['id'];
        $st = $bd->prepare('SELECT ? + INTERVAL ? MONTH');
        $st->execute([$continua ? $u['quota_fins'] : date('Y-m-d', strtotime('yesterday')), $mesos]);
        $fins = $st->fetchColumn();
        $import = preu_tarifa($tarifa, $mesos);

        $bd->prepare('UPDATE usuaris SET quota_fins = ?, tarifa_id = ? WHERE id = ?')
           ->execute([$fins, $tarifa['id'], $usuari_id]);
        $bd->prepare('INSERT INTO pagaments (usuari_id, tarifa_id, mesos, import, fins, cobrat_per) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$usuari_id, $tarifa['id'], $mesos, $import, $fins, $cobrat_per]);
        $bd->commit();
        return ['fins' => $fins, 'import' => $import, 'tarifa' => $tarifa['nom']];
    } catch (Throwable $e) {
        $bd->rollBack();
        throw $e;
    }
}
