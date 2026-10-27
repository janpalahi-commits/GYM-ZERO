<?php
// Ja no es poden crear comptes des de la web: els crea recepció quan el client s'apunta (admin/alta.php).
// La pàgina es queda perquè els enllaços antics portin al login en lloc de donar error.
require_once __DIR__ . '/../inc/sessio.php';

avis('error', 'El compte te\'l fem a recepció quan t\'apuntes al gimnàs.');
header('Location: login.php');
exit;
