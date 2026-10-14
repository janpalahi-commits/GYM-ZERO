<?php
require_once "connexio.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reserva.php?error=Accés no permès");
    exit;
}

$nom = trim($_POST["nom"] ?? "");
$email = trim($_POST["email"] ?? "");
$activitat = trim($_POST["activitat"] ?? "");
$data_reserva = trim($_POST["data_reserva"] ?? "");
$hora_reserva = trim($_POST["hora_reserva"] ?? "");
$observacions = trim($_POST["observacions"] ?? "");

if ($nom === "" || $email === "" || $activitat === "" || $data_reserva === "" || $hora_reserva === "") {
    header("Location: reserva.php?error=Cal completar tots els camps obligatoris");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: reserva.php?error=El correu electrònic no és vàlid");
    exit;
}

try {
    $sql = "INSERT INTO reserves 
            (nom, email, activitat, data_reserva, hora_reserva, observacions)
            VALUES 
            (:nom, :email, :activitat, :data_reserva, :hora_reserva, :observacions)";

    $stmt = $connexio->prepare($sql);

    $stmt->execute([
        ":nom" => $nom,
        ":email" => $email,
        ":activitat" => $activitat,
        ":data_reserva" => $data_reserva,
        ":hora_reserva" => $hora_reserva,
        ":observacions" => $observacions
    ]);

    header("Location: reserva.php?msg=Reserva registrada correctament");
    exit;

} catch (PDOException $e) {
    header("Location: reserva.php?error=Error en guardar la reserva");
    exit;
}
?>
