<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_cita      = mysqli_real_escape_string($conexion, $_GET['id']);
    $nuevo_estado = mysqli_real_escape_string($conexion, $_GET['status']);

    // Obtener id_horario e id_usuario (paciente) de la cita
    $consulta = mysqli_query($conexion, "SELECT id_horario, id_usuario FROM cita WHERE id_cita = '$id_cita'");
    $fila = mysqli_fetch_assoc($consulta);

    if ($fila) {
        $id_horario  = $fila['id_horario'];
        $id_paciente = $fila['id_usuario'];

        // Actualizar estado de la cita
        $sql_cita = "UPDATE cita SET id_estado_cita = '$nuevo_estado' WHERE id_cita = '$id_cita'";

        if (mysqli_query($conexion, $sql_cita)) {
            // Liberar el horario
            mysqli_query($conexion, "UPDATE horario SET estado = 'disponible' WHERE id_horario = '$id_horario'");

            if ($nuevo_estado == 2) {
                // Cita FINALIZADA → redirigir al formulario de historial médico
                header("Location: ../../views/roles/registrar_historial_cita.php?id_cita=$id_cita&id_paciente=$id_paciente");
            } else {
                // Inasistencia (status=5) → volver al panel sin historial
                echo "<script>
                        alert('Cita marcada como inasistencia.');
                        window.location.href = '../../views/roles/doctor.php';
                      </script>";
            }
        } else {
            echo "Error al actualizar la base de datos: " . mysqli_error($conexion);
        }
    } else {
        echo "Cita no encontrada.";
    }
} else {
    header("Location: ../../views/roles/doctor.php");
    exit();
}
?>
