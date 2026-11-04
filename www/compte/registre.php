<?php
require_once __DIR__ . '/../inc/sessio.php';

avis('error', 'El compte te\'l fem a recepció quan t\'apuntes al gimnàs.');
header('Location: login.php');
exit;
