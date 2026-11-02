// Qüestionari per passos: ensenya una pàgina cada vegada i salta les que no toquen, com el formulari d'APP GYM.
// Sense aquest fitxer el formulari també funciona (surten totes les pàgines i el servidor ho comprova tot).
(function () {
  var form = document.querySelector('form.questionari');
  if (!form) return;
  var textos = JSON.parse(form.dataset.textos);
  var pagines = [].slice.call(form.querySelectorAll('section.full-questionari'));
  var perId = {};
  pagines.forEach(function (p, n) { if (p.dataset.id) perId[p.dataset.id] = n; });
  var campPagina = form.querySelector('input[name=pagina]');
  var barra = form.querySelector('.progres');
  var intro = form.querySelector('.intro-questionari');
  var enrere = form.querySelector('[data-enrere]');
  var seguent = form.querySelector('[data-seguent]');
  var enviar = form.querySelector('[data-enviar]');
  var historial = [];
  var actual = 0;

  // On va després d'aquesta pàgina: el salt de l'última pregunta amb salt que tingui resposta
  function desti(n) {
    var d = null;
    pagines[n].querySelectorAll('.pregunta[data-nav]').forEach(function (q) {
      var nav = JSON.parse(q.dataset.nav);
      var r = q.querySelector('input[type=radio]:checked');
      if (r && nav[r.value] != null) d = perId[nav[r.value]];
    });
    return d != null ? d : n + 1;
  }

  function cami() {
    var c = [], n = 0;
    while (n < pagines.length && c.indexOf(n) < 0) { c.push(n); n = desti(n); }
    return c;
  }

  function error(q) {
    var tipus = q.dataset.tipus, req = q.hasAttribute('data-req');
    var marcades = [].slice.call(q.querySelectorAll('input[type=radio]:checked, input[type=checkbox]:checked'));
    var altres = q.querySelector('input[name^="o["]');
    var buida;
    if (tipus === 'mc' || tipus === 'cb') {
      buida = !marcades.some(function (m) { return m.value !== 'o' || (altres && altres.value.trim()); });
    } else if (tipus === 'scale') {
      buida = !marcades.length;
    } else if (tipus === 'grid') {
      var selects = [].slice.call(q.querySelectorAll('select'));
      if (req && selects.some(function (s) { return !s.value; })) return selects.every(function (s) { return !s.value; }) ? textos.required : textos.rows;
      return null;
    } else {
      var camp = q.querySelector('input, textarea, select');
      buida = !camp.value.trim();
      if (!buida && q.dataset.min) {
        var n = parseFloat(camp.value.replace(',', '.'));
        if (isNaN(n) || n < +q.dataset.min || n > +q.dataset.max) return q.dataset.errorNum;
      }
    }
    if (req && buida) return textos.required;
    if (q.hasAttribute('data-check4') && q.querySelectorAll('input[type=checkbox]:checked').length < 4) return textos.check4;
    return null;
  }

  function valida(n) {
    var primera = null;
    pagines[n].querySelectorAll('.pregunta').forEach(function (q) {
      var e = error(q);
      q.classList.toggle('err', !!e);
      q.querySelector('.msg').textContent = e || '';
      if (e && !primera) primera = q;
    });
    if (primera) primera.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return !primera;
  }

  function mostra(n) {
    actual = n;
    campPagina.value = n;
    pagines.forEach(function (p, k) { p.hidden = k !== n; });
    intro.hidden = n > 0;
    var pas = pagines[n].querySelector('.pas');
    if (pas) { pas.hidden = false; pas.textContent = textos.step + ' ' + (historial.length + 1); }
    var ultima = n === pagines.length - 1;
    enrere.hidden = !historial.length;
    seguent.hidden = ultima;
    enviar.hidden = !ultima;
    barra.firstElementChild.style.width = Math.round(100 * n / (pagines.length - 1)) + '%';
  }

  form.classList.add('per-passos');
  barra.hidden = false;

  // Comença on era (si ha canviat d'idioma) o a la primera pàgina amb errors del servidor
  var inici = parseInt(campPagina.value, 10) || 0;
  var ambError = form.querySelector('.pregunta.err');
  if (ambError) inici = pagines.indexOf(ambError.closest('section.full-questionari'));
  var c = cami();
  var pos = c.indexOf(inici);
  if (pos < 0) { pos = 0; inici = 0; }
  historial = c.slice(0, pos);
  mostra(inici);

  seguent.addEventListener('click', function () {
    if (!valida(actual)) return;
    historial.push(actual);
    mostra(desti(actual));
    form.scrollIntoView();
  });
  enrere.addEventListener('click', function () {
    if (historial.length) mostra(historial.pop());
    form.scrollIntoView();
  });
  form.addEventListener('submit', function (e) {
    if (e.submitter === enviar && !valida(actual)) e.preventDefault();
  });

  // Escriure a "Altres" ja marca l'opció
  form.querySelectorAll('input[name^="o["]').forEach(function (t) {
    t.addEventListener('input', function () { if (t.value.trim()) t.parentNode.querySelector('input[value=o]').checked = true; });
  });
  // La tecla Enter en un camp no envia el formulari (aniria al botó d'idioma)
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault();
  });
  // En corregir una pregunta, treu el vermell
  // (també mentre escriu: si no, el missatge desapareix en sortir del camp i la pàgina es mou sota el dit)
  function treuError(e) {
    var q = e.target.closest('.pregunta');
    if (q && q.classList.contains('err') && !error(q)) { q.classList.remove('err'); q.querySelector('.msg').textContent = ''; }
  }
  form.addEventListener('change', treuError);
  form.addEventListener('input', treuError);
})();
