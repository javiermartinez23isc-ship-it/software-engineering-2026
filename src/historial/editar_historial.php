<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Seguridad: sesión activa y solo el doctor puede editar
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}
if ($_SESSION['id_tipo_usuario'] != 1) {
    header("Location: ../../views/roles/doctor.php");
    exit();
}

// 2. Leer y sanitizar campos
$id_historial   = mysqli_real_escape_string($conexion, $_POST['id_historial']   ?? '');
$id_paciente    = mysqli_real_escape_string($conexion, $_POST['id_paciente']    ?? '');
$fecha_consulta = mysqli_real_escape_string($conexion, $_POST['fecha_consulta'] ?? '');
$motivo         = mysqli_real_escape_string($conexion, trim($_POST['motivo']         ?? ''));
$diagnostico    = mysqli_real_escape_string($conexion, trim($_POST['diagnostico']    ?? ''));
$tratamiento    = mysqli_real_escape_string($conexion, trim($_POST['tratamiento']    ?? ''));

$ruta_historial = "../../views/roles/historial.php?id=$id_paciente";

// 3. Validar campos obligatorios
if (empty($id_historial) || empty($id_paciente) || empty($fecha_consulta) || empty($motivo) || empty($diagnostico) || empty($tratamiento)) {
    header("Location: $ruta_historial&error=" . urlencode('Todos los campos son obligatorios.'));
    exit();
}

// 4. Validar que la fecha no sea una fecha futura (no se puede registrar una consulta que aún no ocurre)
if ($fecha_consulta > date('Y-m-d')) {
    header("Location: $ruta_historial&error=" . urlencode('La fecha de consulta no puede ser una fecha futura.'));
    exit();
}

// 5. Verificar que el registro pertenece al paciente indicado (evita manipulación de IDs)
$check = mysqli_query($conexion, "SELECT id_historial FROM historial_medico 
                                   WHERE id_historial = '$id_historial' AND id_usuario = '$id_paciente'");
if (mysqli_num_rows($check) === 0) {
    header("Location: $ruta_historial&error=" . urlencode('Registro no encontrado o acceso denegado.'));
    exit();
}

// 6. Ejecutar UPDATE
$sql = "UPDATE historial_medico 
        SET fecha_consulta = '$fecha_consulta',
            motivo         = '$motivo',
            diagnostico    = '$diagnostico',
            tratamiento    = '$tratamiento'
        WHERE id_historial = '$id_historial' AND id_usuario = '$id_paciente'";

if (mysqli_query($conexion, $sql)) {
    header("Location: $ruta_historial&ok=editado");
} else {
    header("Location: $ruta_historial&error=" . urlencode(mysqli_error($conexion)));
}
exit();
?>
