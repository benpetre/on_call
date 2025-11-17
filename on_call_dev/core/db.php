<?php
$host = "localhost";
$user = "thronet6";
$pass = "Fried669!";
$db   = "thronet6_ortho";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
