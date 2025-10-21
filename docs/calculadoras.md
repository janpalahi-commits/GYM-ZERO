---
title: Calculadores
---

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

<!-- Carga del JS externo (usa ruta absoluta para Pages) -->
<script src="/GYM-ZERO/docs/js/calculadoras.js"></script>
