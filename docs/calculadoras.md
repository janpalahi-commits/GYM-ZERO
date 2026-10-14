<h1>🧮 Calculadores de Gimnàs 4.0</h1>

<p>Calculadores per ajudar els clients del gimnàs a estimar valors com el 1RM, l'IMC i el TDEE.</p>

<hr>

<h2>🏋️‍♂️ Càlcul de 1RM (Epley i Brzycki)</h2>
<label>Pès aixecat (kg):</label>
<input id="peso" type="number" step="0.1"><br>
<label>Repeticions:</label>
<input id="reps" type="number"><br>
<button onclick="calcular1RM()">Calcular 1RM</button>

<p id="res1rm"></p>

<hr>

<h2>📊 Taula RPE → %1RM aproximat</h2>
<table border="1" cellpadding="4">
  <tr><th>RPE</th><th>%1RM</th></tr>
  <tr><td>10</td><td>100%</td></tr>
  <tr><td>9.5</td><td>97%</td></tr>
  <tr><td>9</td><td>94%</td></tr>
  <tr><td>8.5</td><td>91%</td></tr>
  <tr><td>8</td><td>88%</td></tr>
  <tr><td>7.5</td><td>84%</td></tr>
  <tr><td>7</td><td>81%</td></tr>
  <tr><td>6.5</td><td>77%</td></tr>
  <tr><td>6</td><td>74%</td></tr>
</table>

<hr>

<h2>⚖️ Índex de Massa Corporal (IMC)</h2>
<label>Pès (kg):</label>
<input id="imc_peso" type="number" step="0.1"><br>
<label>Alçada (cm):</label>
<input id="imc_altura" type="number"><br>
<button onclick="calcularIMC()">Calcular IMC</button>
<p id="resIMC"></p>

<hr>

<h2>🔥 TDEE (Despesa Energètica Diària Total)</h2>
<label>Pes (kg):</label><input id="tdee_peso" type="number"><br>
<label>Alçada (cm):</label><input id="tdee_altura" type="number"><br>
<label>Edat:</label><input id="tdee_edat" type="number"><br>
<label>Gènere:</label>
<select id="tdee_genere">
  <option value="home">Home</option>
  <option value="dona">Dona</option>
</select><br>
<label>Nivell d'activitat:</label>
<select id="tdee_activitat">
  <option value="1.2">Sedentari</option>
  <option value="1.375">Lleugerament actiu</option>
  <option value="1.55">Moderat</option>
  <option value="1.725">Molt actiu</option>
  <option value="1.9">Extremadament actiu</option>
</select><br>
<button onclick="calcularTDEE()">Calcular TDEE</button>
<p id="resTDEE"></p>

<script>
// --- 1RM ---
function calcular1RM() {
  const peso = parseFloat(document.getElementById("peso").value);
  const reps = parseInt(document.getElementById("reps").value);

  if (isNaN(peso) || isNaN(reps) || reps <= 0) {
    document.getElementById("res1rm").innerText = "Introdueix valors vàlids.";
    return;
  }

  const epley = peso * (1 + reps / 30);
  const brzycki = peso * (36 / (37 - reps));
  document.getElementById("res1rm").innerHTML = `
    <b>Resultats:</b><br>
    Epley: ${epley.toFixed(1)} kg<br>
    Brzycki: ${brzycki.toFixed(1)} kg
  `;
}

// --- IMC ---
function calcularIMC() {
  const peso = parseFloat(document.getElementById("imc_peso").value);
  const alturaCm = parseFloat(document.getElementById("imc_altura").value);
  const altura = alturaCm / 100;
  if (!peso || !altura) {
    document.getElementById("resIMC").innerText = "Introdueix pes i alçada vàlids.";
    return;
  }
  const imc = peso / (altura * altura);
  let cat = "";
  if (imc < 18.5) cat = "Baix pes";
  else if (imc < 25) cat = "Pes normal";
  else if (imc < 30) cat = "Sobrepès";
  else cat = "Obesitat";

  document.getElementById("resIMC").innerHTML = `IMC: <b>${imc.toFixed(1)}</b> (${cat})`;
}

// --- TDEE ---
function calcularTDEE() {
  const peso = parseFloat(document.getElementById("tdee_peso").value);
  const altura = parseFloat(document.getElementById("tdee_altura").value);
  const edat = parseFloat(document.getElementById("tdee_edat").value);
  const genere = document.getElementById("tdee_genere").value;
  const activitat = parseFloat(document.getElementById("tdee_activitat").value);

  if (!peso || !altura || !edat) {
    document.getElementById("resTDEE").innerText = "Completa tots els camps.";
    return;
  }

  // Fórmula Mifflin-St Jeor
  let bmr;
  if (genere === "home") {
    bmr = 10 * peso + 6.25 * altura - 5 * edat + 5;
  } else {
    bmr = 10 * peso + 6.25 * altura - 5 * edat - 161;
  }
  const tdee = bmr * activitat;
  document.getElementById("resTDEE").innerHTML = `
    <b>BMR:</b> ${bmr.toFixed(0)} kcal/dia<br>
    <b>TDEE:</b> ${tdee.toFixed(0)} kcal/dia
  `;
}
</script>
