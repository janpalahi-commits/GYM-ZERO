<?php
// Tancar la sessió
require_once __DIR__ . '/../inc/sessio.php';

$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Location: login.php');
exit;
