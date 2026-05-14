<?php
// CORRECCIÓN 1: Ruta para llegar a config/db.php desde src/appointments/
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Verificamos que lleguen los datos necesarios por la URL
if (isset($_GET['id']) && isset($_GET['status'])) {
    // Limpiamos los datos para evitar errores de SQL (usando la conexión del puerto 3307)
    $id_cita = mysqli_real_escape_string($conexion, $_GET['id']);
    $nuevo_estado = mysqli_real_escape_string($conexion, $_GET['status']);

    // 1. Obtenemos el id_horario para poder liberarlo después
    $consulta = mysqli_query($conexion, "SELECT id_horario FROM cita WHERE id_cita = '$id_cita'");
    $fila = mysqli_fetch_assoc($consulta);

    if ($fila) {
        $id_horario = $fila['id_horario'];

        // 2. Actualizamos el estado de la cita (2 = Finalizada, 5 = No asistió)
        $sql_cita = "UPDATE cita SET id_estado_cita = '$nuevo_estado' WHERE id_cita = '$id_cita'";
        
        if (mysqli_query($conexion, $sql_cita)) {
            
            // 3. Liberamos el horario para que otros pacientes puedan verlo
            mysqli_query($conexion, "UPDATE horario SET estado = 'disponible' WHERE id_horario = '$id_horario'");

            // CORRECCIÓN 2: Redirección a la nueva ubicación de la vista del doctor
            // Subimos dos niveles (../../) y entramos a views/roles/
            echo "<script>
                    alert('Cita actualizada correctamente.');
                    window.location.href = '../../views/roles/doctor.php';
                  </script>";
        } else {
            echo "Error al actualizar la base de datos: " . mysqli_error($conexion);
        }
    } else {
        echo "Cita no encontrada.";
    }
} else {
    // CORRECCIÓN 3: Redirección de seguridad si no hay parámetros
    header("Location: ../../views/roles/doctor.php");
    exit();
}
?>