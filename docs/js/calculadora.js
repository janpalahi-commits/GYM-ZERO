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
  document.getElementById("res1rm").innerHTML =
    "<b>Resultats:</b><br>" +
    "Epley: " + epley.toFixed(1) + " kg<br>" +
    "Brzycki: " + brzycki.toFixed(1) + " kg";
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

  document.getElementById("resIMC").innerHTML = "IMC: <b>" + imc.toFixed(1) + "</b> (" + cat + ")";
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
  document.getElementById("resTDEE").innerHTML =
    "<b>BMR:</b> " + bmr.toFixed(0) + " kcal/dia<br>" +
    "<b>TDEE:</b> " + tdee.toFixed(0) + " kcal/dia";
}
