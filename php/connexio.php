<?php
$host = "localhost";
$dbname = "gymzero_db";
$user = "root";
$password = "";

try {
    $connexio = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );

    $connexio->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de connexió amb la base de dades: " . $e->getMessage());
}
?>

