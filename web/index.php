<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = "192.168.10.20";
$user = "bchecker";
$pass = "pirineus";
$db   = "mvp_db";

$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "<h1 style=\"color:red;\">Error de connexió amb B-NCC: " . $conn->connect_error . "</h1>\n";
} else {
    $result = $conn->query("SELECT estado FROM test WHERE id=1");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<h1 style=\"color:green;\">MVP FUNCIONANT CORRECTAMENT</h1>\n";
        echo "<h3>Missatge des de B-NCC: " . $row["estado"] . "</h3>\n";
    } else {
        echo "<h1 style=\"color:orange;\">Connectat a B-NCC, però la taula no té dades.</h1>\n";
    }
}
?>
