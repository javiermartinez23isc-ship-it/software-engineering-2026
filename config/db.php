<?php
// Zona horaria de México (Centro: UTC-6 / UTC-5 en verano)
date_default_timezone_set('America/Mexico_City');

$host = "127.0.0.1";
$port = "3307"; // El puerto que salvó el proyecto
$user = "root";
$pass = "";
$db   = "agenda_vital";

// Conexión específica para el puerto 3307
$conexion = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>