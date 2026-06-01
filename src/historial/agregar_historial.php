<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}

// 2. Solo el doctor puede insertar historial
if ($_SESSION['id_tipo_usuario'] != 1) {
    if ($_SESSION['id_tipo_usuario'] == 2) {
        header("Location: ../../views/roles/asistente.php");
    } else {
        header("Location: ../../views/roles/paciente.php");
    }
    exit();
}

// 3. Leer y sanitizar campos del POST
$id_paciente    = mysqli_real_escape_string($conexion, $_POST['id_paciente'] ?? '');
$fecha_consulta = mysqli_real_escape_string($conexion, $_POST['fecha_consulta'] ?? '');
$motivo         = mysqli_real_escape_string($conexion, trim($_POST['motivo'] ?? ''));
$diagnostico    = mysqli_real_escape_string($conexion, trim($_POST['diagnostico'] ?? ''));
$tratamiento    = mysqli_real_escape_string($conexion, trim($_POST['tratamiento'] ?? ''));
$id_cita        = isset($_POST['id_cita']) ? mysqli_real_escape_string($conexion, $_POST['id_cita']) : null;

// Determinar ruta de regreso
// Si viene de una cita finalizada, regresar al panel del doctor
// Si viene del panel de historial, regresar al historial del paciente
$desde_cita = !empty($id_cita);
$ruta_exito = $desde_cita
    ? '../../views/roles/doctor.php'
    : '../../views/roles/historial.php?id=' . $id_paciente;
$ruta_error = $desde_cita
    ? '../../views/roles/registrar_historial_cita.php?id_cita=' . $id_cita . '&id_paciente=' . $id_paciente . '&error='
    : '../../views/roles/historial.php?id=' . $id_paciente . '&error=';

// 4. Validar campos obligatorios
if (empty($id_paciente) || empty($fecha_consulta) || empty($motivo) || empty($diagnostico) || empty($tratamiento)) {
    header("Location: " . $ruta_error . urlencode('Todos los campos son obligatorios.'));
    exit();
}

// 4b. Validar que la fecha no sea futura (no se puede registrar una consulta que aún no ocurre)
if ($fecha_consulta > date('Y-m-d')) {
    header("Location: " . $ruta_error . urlencode('La fecha de consulta no puede ser una fecha futura.'));
    exit();
}

// 5. Ejecutar INSERT
$sql = "INSERT INTO historial_medico (id_usuario, fecha_consulta, motivo, diagnostico, tratamiento)
        VALUES ('$id_paciente', '$fecha_consulta', '$motivo', '$diagnostico', '$tratamiento')";

if (mysqli_query($conexion, $sql)) {
    if ($desde_cita) {
        echo "<script>
                alert('Historial médico registrado correctamente.');
                window.location.href = '../../views/roles/doctor.php';
              </script>";
    } else {
        header("Location: $ruta_exito");
    }
} else {
    $error_msg = urlencode(mysqli_error($conexion));
    header("Location: " . $ruta_error . $error_msg);
}
exit();
?>
