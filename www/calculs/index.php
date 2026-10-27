<?php
// Calculadores: 1RM, IMC i calories diàries.
// Els càlculs es fan aquí, en PHP. Els formularis van per GET perquè no
// guarden res: només calculen i ensenyen el resultat.
require_once __DIR__ . '/../inc/sessio.php';
require_once __DIR__ . '/../inc/bd.php';

$u = usuari_actual();

// Llegeix un número del formulari (accepta "62,5" i "62.5").
// Torna null si no hi és o si no està entre $min i $max.
function numero($camp, $min, $max)
{
    $valor = str_replace(',', '.', trim($_GET[$camp] ?? ''));
    if (!is_numeric($valor) || $valor < $min || $valor > $max) {
        return null;
    }
    return (float) $valor;
}

// 62.5 -> "62,5"
function xifra($valor, $decimals = 1)
{
    return number_format($valor, $decimals, ',', '.');
}

// Pes màxim estimat per a 1 repetició (1RM) amb la fórmula d'Epley:
// 1RM = pes x (1 + reps / 30). Es fa servir la mateixa per a totes les màquines.
// És una estimació: com més repeticions, menys fiable (a partir d'unes 12 l'error ja és gran).
function u_rm($kg, $reps)
{
    if ($reps == 1) {
        return $kg;
    }
    return $kg * (1 + $reps / 30);
}

// Al revés: quantes repeticions es poden fer amb un % del 1RM
function reps_amb($pct)
{
    return max(1, round(30 * (100 / $pct - 1)));
}

// Arrodoneix al pes que es pot posar amb discos (de 2,5 en 2,5 kg)
function discos($kg)
{
    return round($kg / 2.5) * 2.5;
}

$calcul = $_GET['calcul'] ?? '';
$errors = [];

// --- 1RM ---
$rm = null;
if ($calcul === '1rm') {
    $kg   = numero('kg', 1, 500);
    $reps = numero('reps', 1, PHP_INT_MAX);
    if ($kg === null || $reps === null || $reps != (int) $reps) {
        $errors['1rm'] = 'Posa un pes entre 1 i 500 kg i un nombre de repeticions sencer (1 o més).';
    } else {
        $rm = u_rm($kg, (int) $reps);
    }
}

// --- IMC ---
$imc = null;
if ($calcul === 'imc') {
    $pes    = numero('pes', 30, 300);
    $alcada = numero('alcada', 120, 230);
    if ($pes === null || $alcada === null) {
        $errors['imc'] = 'Posa un pes entre 30 i 300 kg i una alçada entre 120 i 230 cm.';
    } else {
        $imc = $pes / (($alcada / 100) ** 2);
        if ($imc < 18.5) {
            $imc_text = 'pes baix';
        } elseif ($imc < 25) {
            $imc_text = 'pes normal';
        } elseif ($imc < 30) {
            $imc_text = 'sobrepès';
        } else {
            $imc_text = 'obesitat';
        }
    }
}

// --- Calories diàries (fórmula de Mifflin-St Jeor) ---
$activitats = [
    '1.2'   => 'Sedentari',
    '1.375' => '1-3 dies/setmana',
    '1.55'  => '3-5 dies/setmana',
    '1.725' => '6-7 dies/setmana',
    '1.9'   => 'Molt actiu',
];
$kcal = null;
if ($calcul === 'kcal') {
    $pes    = numero('pes', 30, 300);
    $alcada = numero('alcada', 120, 230);
    $edat   = numero('edat', 14, 100);
    $sexe   = $_GET['sexe'] ?? '';
    $factor = $_GET['activitat'] ?? '';
    if ($pes === null || $alcada === null || $edat === null
        || !in_array($sexe, ['home', 'dona'], true) || !isset($activitats[$factor])) {
        $errors['kcal'] = 'Omple tots els camps (pes 30-300 kg, alçada 120-230 cm, edat 14-100).';
    } else {
        $basal = 10 * $pes + 6.25 * $alcada - 5 * $edat + ($sexe === 'home' ? 5 : -161);
        $kcal  = $basal * (float) $factor;
    }
}

// Si l'usuari ha entrat, el seu 1RM estimat a cada màquina amb les sèries
// que han enviat les màquines els últims 90 dies (la millor de cada màquina)
$meus_rm = [];
if ($u) {
    $st = $connexio->prepare(
        'SELECT m.nom, v.data, v.carrega_kg, v.repeticions
           FROM v_resum_series v JOIN maquines m ON m.codi = v.maquina
          WHERE v.usuari_id = ? AND v.data >= CURDATE() - INTERVAL 90 DAY'
    );
    $st->execute([$u['id']]);
    foreach ($st->fetchAll() as $s) {
        $estimat = u_rm((float) $s['carrega_kg'], (int) $s['repeticions']);
        if (!isset($meus_rm[$s['nom']]) || $estimat > $meus_rm[$s['nom']]['rm']) {
            $meus_rm[$s['nom']] = $s + ['rm' => $estimat];
        }
    }
    ksort($meus_rm);
}

// Torna a posar al formulari el que s'havia escrit
function valor($camp)
{
    return h($_GET[$camp] ?? '');
}

$titol = 'Càlculs';
require __DIR__ . '/../inc/capcalera.php';
?>
<p>Són estimacions orientatives. Si tens alguna lesió o dubte, pregunta a un entrenador abans de fer servir aquests números.</p>

<h2>Pes màxim (1RM)</h2>
<p>Amb el pes que has aixecat i quantes repeticions has fet, calcula el pes que podries aixecar una sola vegada. Fa servir la fórmula d'Epley.</p>
<form method="GET" action="#rm" id="rm">
  <input type="hidden" name="calcul" value="1rm">
  <div class="fila-camps">
    <div><label for="kg">Pes aixecat (kg)</label><input id="kg" name="kg" inputmode="decimal" value="<?= $calcul === '1rm' ? valor('kg') : '' ?>" required></div>
    <div><label for="reps">Repeticions</label><input id="reps" name="reps" type="number" min="1" value="<?= $calcul === '1rm' ? valor('reps') : '' ?>" required></div>
  </div>
  <button>Calcular</button>
</form>
<?php if (isset($errors['1rm'])): ?>
  <div class="error"><?= h($errors['1rm']) ?></div>
<?php elseif ($rm !== null): ?>
  <div class="resultat">1RM estimat: <b><?= xifra($rm) ?> kg</b>
    <?php if ($reps > 12): ?><br><span class="subtil">Amb més de 12 repeticions l'estimació és poc fiable.</span><?php endif; ?>
  </div>
  <table>
    <tr><th>% del 1RM</th><th>Pes (arrodonit a 2,5 kg)</th><th>Repeticions aproximades</th></tr>
    <?php foreach ([95, 90, 85, 80, 75, 70] as $pct): ?>
      <tr><td><?= $pct ?>%</td><td><?= xifra(discos($rm * $pct / 100)) ?> kg</td><td><?= reps_amb($pct) ?></td></tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if ($meus_rm): ?>
  <h3>El teu 1RM a les màquines (últims 90 dies)</h3>
  <p class="subtil">Calculat amb la teva millor sèrie a cada màquina.</p>
  <table>
    <tr><th>Màquina</th><th>Millor sèrie</th><th class="amaga-mobil">Dia</th><th>1RM estimat</th></tr>
    <?php foreach ($meus_rm as $maquina => $s): ?>
      <tr>
        <td><?= h($maquina) ?></td>
        <td><?= xifra((float) $s['carrega_kg']) ?> kg × <?= (int) $s['repeticions'] ?></td>
        <td class="amaga-mobil"><?= date('d/m', strtotime($s['data'])) ?></td>
        <td><b><?= xifra($s['rm']) ?> kg</b></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<h2>Índex de massa corporal (IMC)</h2>
<p>Relaciona el pes i l'alçada. En persones que fan força pot sortir alt per culpa del múscul, no del greix.</p>
<form method="GET" action="#imc" id="imc">
  <input type="hidden" name="calcul" value="imc">
  <div class="fila-camps">
    <div><label for="imc-pes">Pes (kg)</label><input id="imc-pes" name="pes" inputmode="decimal" value="<?= $calcul === 'imc' ? valor('pes') : '' ?>" required></div>
    <div><label for="imc-alcada">Alçada (cm)</label><input id="imc-alcada" name="alcada" type="number" min="120" max="230" value="<?= $calcul === 'imc' ? valor('alcada') : '' ?>" required></div>
  </div>
  <button>Calcular</button>
</form>
<?php if (isset($errors['imc'])): ?>
  <div class="error"><?= h($errors['imc']) ?></div>
<?php elseif ($imc !== null): ?>
  <div class="resultat">IMC: <b><?= xifra($imc) ?></b> (<?= $imc_text ?>)</div>
<?php endif; ?>

<h2>Calories que gastes cada dia</h2>
<p>Primer calcula el que gasta el cos en repòs (metabolisme basal) i després hi suma l'activitat.</p>
<form method="GET" action="#kcal" id="kcal">
  <input type="hidden" name="calcul" value="kcal">
  <div class="fila-camps">
    <div><label for="kcal-pes">Pes (kg)</label><input id="kcal-pes" name="pes" inputmode="decimal" value="<?= $calcul === 'kcal' ? valor('pes') : '' ?>" required></div>
    <div><label for="kcal-alcada">Alçada (cm)</label><input id="kcal-alcada" name="alcada" type="number" min="120" max="230" value="<?= $calcul === 'kcal' ? valor('alcada') : '' ?>" required></div>
    <div><label for="kcal-edat">Edat</label><input id="kcal-edat" name="edat" type="number" min="14" max="100" value="<?= $calcul === 'kcal' ? valor('edat') : '' ?>" required></div>
  </div>
  <div class="fila-camps">
    <div>
      <label for="kcal-sexe">Sexe</label>
      <select id="kcal-sexe" name="sexe">
        <option value="home" <?= ($_GET['sexe'] ?? '') === 'home' ? 'selected' : '' ?>>Home</option>
        <option value="dona" <?= ($_GET['sexe'] ?? '') === 'dona' ? 'selected' : '' ?>>Dona</option>
      </select>
    </div>
    <div>
      <label for="kcal-activitat">Activitat</label>
      <select id="kcal-activitat" name="activitat">
        <?php foreach ($activitats as $factor => $text): ?>
          <option value="<?= $factor ?>" <?= ($_GET['activitat'] ?? '1.55') === (string) $factor ? 'selected' : '' ?>><?= h($text) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button>Calcular</button>
</form>
<?php if (isset($errors['kcal'])): ?>
  <div class="error"><?= h($errors['kcal']) ?></div>
<?php elseif ($kcal !== null): ?>
  <div class="resultat">
    En repòs: <b><?= xifra($basal, 0) ?> kcal</b> al dia<br>
    Amb la teva activitat: <b><?= xifra($kcal, 0) ?> kcal</b> al dia
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../inc/peu.php'; ?>
