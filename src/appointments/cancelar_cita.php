<?php
// CORRECCIÓN 1: Ruta para llegar a config/db.php desde src/appointments/
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Verificamos que existan los datos de la cita y el horario
if (isset($_GET['id']) && isset($_GET['horario'])) {
    $id_cita = mysqli_real_escape_string($conexion, $_GET['id']);
    $id_horario = mysqli_real_escape_string($conexion, $_GET['horario']);

    // --- LÓGICA DE REDIRECCIÓN DINÁMICA ACTUALIZADA ---
    $tipo_usuario = $_SESSION['id_tipo_usuario'];

    // CORRECCIÓN 2: Ajuste de rutas hacia views/roles/
    // Subimos dos niveles (../../) para salir de src/appointments/
    if ($tipo_usuario == 2) {
        $destino = '../../views/roles/asistente.php';
    } else {
        $destino = '../../views/roles/paciente.php';
    }

    // 1. Liberar el horario en la tabla 'horario'
    // Solo restaurar si el horario tenía disponible=1 (no fue bloqueado por el doctor)
    $sql_h = "UPDATE horario SET estado = 'disponible' WHERE id_horario = '$id_horario' AND disponible = 1";
    mysqli_query($conexion, $sql_h);

    // 2. Borrar la cita de la tabla 'cita'
    $sql_c = "DELETE FROM cita WHERE id_cita = '$id_cita'";
    
    if (mysqli_query($conexion, $sql_c)) {
        echo "<script>
                alert('Cita cancelada y horario liberado.');
                window.location.href = '$destino';
              </script>";
    } else {
        echo "Error al eliminar: " . mysqli_error($conexion);
    }
} else {
    // CORRECCIÓN 3: Redirección de seguridad con rutas nuevas
    $regreso = ($_SESSION['id_tipo_usuario'] == 2) ? '../../views/roles/asistente.php' : '../../views/roles/paciente.php';
    header("Location: $regreso");
    exit();
}
?>