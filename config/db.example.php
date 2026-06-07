<?php
// ============================================================
// db.example.php — Archivo de ejemplo de configuración
// Copia este archivo como db.php y completa tus credenciales
// ============================================================

// Zona horaria de México (Centro: UTC-6 / UTC-5 en verano)
date_default_timezone_set('America/Mexico_City');

$host = "localhost";
$port = "3307";        // Puerto MySQL configurado en XAMPP
$user = "root";        // Usuario de MySQL (por defecto: root)
$pass = "";            // ← Ingresa tu contraseña de MySQL aquí
$db   = "agenda_vital";

$conexion = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Sincronizar zona horaria de MySQL con PHP
$offset = date('P');
mysqli_query($conexion, "SET time_zone = '$offset'");

// Auto-marcar como "No asistió"(5) las citas Pendientes o Confirmadas cuya hora ya pasó
mysqli_query($conexion,
    "UPDATE cita c
     JOIN horario h ON c.id_horario = h.id_horario
     SET c.id_estado_cita = 5
     WHERE c.id_estado_cita IN (1, 4)
       AND CONCAT(h.fecha, ' ', h.hora_inicio) < NOW()");

// Enviar recordatorios por correo cuya hora de envío ya llegó
require_once __DIR__ . '/../src/notifications/enviar_recordatorios.php';
procesarRecordatorios($conexion);
?>
