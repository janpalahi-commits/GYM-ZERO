<?php
// Avís legal i política de privacitat (LSSI i RGPD)
require_once __DIR__ . '/../inc/sessio.php';

$titol = 'Avís legal i privacitat';
require __DIR__ . '/../inc/capcalera.php';
?>
<p>Gym Zero és un gimnàs inventat per al Projecte Intermodular del cicle de Sistemes Microinformàtics i Xarxes de l'INS Palamós. La web funciona de veritat, però darrere no hi ha cap empresa ni cap negoci real.</p>

<h2>Quines dades guardem</h2>
<table>
  <tr><th>Dada</th><th>Per a què</th></tr>
  <tr><td>Nom, cognoms i correu</td><td>Identificar-te i entrar a la web. El correu també serveix per recuperar la contrasenya.</td></tr>
  <tr><td>Contrasenya</td><td>Entrar a la web. Es guarda xifrada (amb un hash): ni el personal del gimnàs la pot llegir.</td></tr>
  <tr><td>Quota</td><td>Saber fins quan la tens pagada. El pagament es fa a recepció i la web no guarda cap dada bancària.</td></tr>
  <tr><td>Codi de la polsera</td><td>Obrir el torn d'entrada i apuntar les sèries a les màquines.</td></tr>
  <tr><td>Reserves</td><td>Guardar la teva plaça a les classes.</td></tr>
  <tr><td>Entrades</td><td>Saber qui ha passat pel torn i quan, també quan no et deixa passar.</td></tr>
  <tr><td>Sèries de les màquines</td><td>Ensenyar-te el teu entrenament: pes, repeticions i velocitat.</td></tr>
</table>

<h2>Per què les podem fer servir</h2>
<p>Perquè són necessàries per donar-te el servei del gimnàs que has contractat (article 6.1.b del RGPD). No les fem servir per a publicitat ni les donem a ningú.</p>

<h2>Qui les veu</h2>
<p>Tu, des de la teva àrea, i el personal de recepció. La web i la base de dades són en un servidor de Dinahosting, a Espanya, que només les guarda per nosaltres.</p>

<h2>Quant de temps les guardem</h2>
<p>Mentre siguis client. Quan et dones de baixa, el compte es desactiva; si demanes que s'esborri, s'esborra, i les entrades pel torn es queden sense el teu nom. Cada dia es fa una còpia de seguretat de la base de dades.</p>

<h2>Els teus drets</h2>
<p>Pots demanar a recepció veure les teves dades, corregir-les, esborrar-les, que no les fem servir o que te les donem en un fitxer. Si creus que no les tractem bé, pots reclamar a l'<a href="https://www.aepd.es">Agència Espanyola de Protecció de Dades</a>.</p>

<h2>Galetes</h2>
<p>La web només fa servir una galeta, la de la sessió, perquè no hagis d'escriure la contrasenya a cada pàgina. S'esborra quan tanques el navegador. No hi ha galetes de publicitat ni d'estadístiques, i les lletres de la web són al nostre servidor, així que el teu navegador no es connecta a cap altra web.</p>
<?php require __DIR__ . '/../inc/peu.php'; ?>
