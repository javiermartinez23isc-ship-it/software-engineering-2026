<?php
// CORRECCIÓN: Ruta absoluta para llegar a config/db.php (usando tu puerto 3307)
include_once(__DIR__ . '/../../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos el ID del paciente del SELECT y el ID del horario de la tabla
    // Se usa la variable $conexion definida en config/db.php
    $id_paciente = mysqli_real_escape_string($conexion, $_POST['id_usuario']); 
    $id_horario = mysqli_real_escape_string($conexion, $_POST['id_horario']);

    if (empty($id_paciente) || empty($id_horario)) {
        echo "<script>alert('Error: Datos incompletos.'); window.history.back();</script>";
        exit();
    }

    // 1. Insertar la cita a nombre del PACIENTE (id_estado_cita = 1: Pendiente)
    $sql_cita = "INSERT INTO cita (id_usuario, id_horario, id_estado_cita) 
                 VALUES ('$id_paciente', '$id_horario', 1)";

    if (mysqli_query($conexion, $sql_cita)) {
        
        // 2. Marcar el horario como ocupado en la base de datos
        mysqli_query($conexion, "UPDATE horario SET estado = 'ocupado' WHERE id_horario = '$id_horario'");

        // CORRECCIÓN: Redirección a la nueva ubicación de la vista del asistente
        // Subimos dos niveles (../../) para salir de src/appointments/ y entramos a views/roles/
        echo "<script>
                alert('¡Cita agendada correctamente para el paciente!');
                window.location.href = '../../views/roles/asistente.php';
              </script>";
    } else {
        echo "Error al agendar: " . mysqli_error($conexion);
    }
}
?>