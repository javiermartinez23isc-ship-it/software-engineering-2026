<?php
/**
 * cancelar_cita.php
 * Cambia el estado de la cita a Cancelada(3) y libera el horario.
 * NO borra el registro para mantener trazabilidad histórica.
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (isset($_GET['id']) && isset($_GET['horario'])) {
    $id_cita    = (int)$_GET['id'];
    $id_horario = (int)$_GET['horario'];

    $tipo_usuario = (int)$_SESSION['id_tipo_usuario'];
    $destino = ($tipo_usuario == 2)
        ? '../../views/roles/asistente.php'
        : '../../views/roles/paciente.php';

    // 1. Liberar el horario
    mysqli_query($conexion,
        "UPDATE horario SET estado = 'disponible'
         WHERE id_horario = '$id_horario' AND disponible = 1");

    // 2. Cambiar estado a Cancelada(3) y guardar fecha de cancelación — NO borrar
    if (mysqli_query($conexion,
        "UPDATE cita SET id_estado_cita = 3, fecha_cancelacion = NOW()
         WHERE id_cita = '$id_cita'")) {

        echo "<script>
                alert('Cita cancelada y horario liberado.');
                window.location.href = '$destino';
              </script>";
    } else {
        echo "Error al cancelar: " . mysqli_error($conexion);
    }

} else {
    $regreso = ((int)$_SESSION['id_tipo_usuario'] == 2)
        ? '../../views/roles/asistente.php'
        : '../../views/roles/paciente.php';
    header("Location: $regreso");
    exit();
}
?>
