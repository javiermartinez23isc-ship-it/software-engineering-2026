<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Seguridad: sesión activa y solo el doctor puede eliminar
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}
if ($_SESSION['id_tipo_usuario'] != 1) {
    header("Location: ../../views/roles/doctor.php");
    exit();
}

// 2. Leer y sanitizar
$id_historial = mysqli_real_escape_string($conexion, $_POST['id_historial'] ?? '');
$id_paciente  = mysqli_real_escape_string($conexion, $_POST['id_paciente']  ?? '');

$ruta_historial = "../../views/roles/historial.php?id=$id_paciente";

if (empty($id_historial) || empty($id_paciente)) {
    header("Location: $ruta_historial&error=" . urlencode('Datos incompletos para eliminar.'));
    exit();
}

// 3. Verificar que el registro pertenece al paciente indicado
$check = mysqli_query($conexion, "SELECT id_historial FROM historial_medico 
                                   WHERE id_historial = '$id_historial' AND id_usuario = '$id_paciente'");
if (mysqli_num_rows($check) === 0) {
    header("Location: $ruta_historial&error=" . urlencode('Registro no encontrado o acceso denegado.'));
    exit();
}

// 4. Ejecutar DELETE
$sql = "DELETE FROM historial_medico WHERE id_historial = '$id_historial' AND id_usuario = '$id_paciente'";

if (mysqli_query($conexion, $sql)) {
    header("Location: $ruta_historial&ok=eliminado");
} else {
    header("Location: $ruta_historial&error=" . urlencode(mysqli_error($conexion)));
}
exit();
?>
