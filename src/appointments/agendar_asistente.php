<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/auth/login.php");
    exit();
}

$redireccion = '../../views/roles/asistente.php';

$id_paciente = mysqli_real_escape_string($conexion, $_POST['id_usuario']  ?? '');
$id_horario  = isset($_POST['id_horario']) ? (int)$_POST['id_horario'] : 0;
$fecha_slot  = mysqli_real_escape_string($conexion, trim($_POST['fecha_slot'] ?? ''));
$hora_slot   = mysqli_real_escape_string($conexion, trim($_POST['hora_slot']  ?? ''));

if (empty($id_paciente) || empty($fecha_slot) || empty($hora_slot)) {
    echo "<script>alert('Error: Selecciona un paciente y un horario.'); window.history.back();</script>";
    exit();
}

// Validar fecha no pasada
if ($fecha_slot < date('Y-m-d')) {
    echo "<script>alert('Error: No puedes agendar en una fecha pasada.'); window.history.back();</script>";
    exit();
}

// Buscar o crear el registro en horario
if ($id_horario > 0) {
    $res_h = mysqli_query($conexion, "SELECT id_horario, estado, disponible FROM horario WHERE id_horario = '$id_horario'");
    $row_h = mysqli_fetch_assoc($res_h);
    if (!$row_h || $row_h['estado'] === 'ocupado' || (int)$row_h['disponible'] === 0) {
        echo "<script>alert('Este horario ya no está disponible. Elige otro.'); window.history.back();</script>";
        exit();
    }
} else {
    $hora_fin  = date('H:i:s', strtotime($hora_slot) + 3600);
    $res_exist = mysqli_query($conexion,
        "SELECT id_horario, estado, disponible FROM horario
         WHERE fecha = '$fecha_slot' AND hora_inicio = '$hora_slot'");
    if ($row_exist = mysqli_fetch_assoc($res_exist)) {
        if ($row_exist['estado'] === 'ocupado' || (int)$row_exist['disponible'] === 0) {
            echo "<script>alert('Este horario ya fue reservado o está bloqueado. Elige otro.'); window.history.back();</script>";
            exit();
        }
        $id_horario = (int)$row_exist['id_horario'];
    } else {
        mysqli_query($conexion,
            "INSERT INTO horario (fecha, hora_inicio, hora_fin, disponible, estado)
             VALUES ('$fecha_slot', '$hora_slot', '$hora_fin', 1, 'disponible')");
        $id_horario = (int)mysqli_insert_id($conexion);
    }
}

// Insertar la cita
$sql = "INSERT INTO cita (id_usuario, id_horario, id_estado_cita, fecha_registro)
        VALUES ('$id_paciente', '$id_horario', 1, NOW())";

if (mysqli_query($conexion, $sql)) {
    mysqli_query($conexion, "UPDATE horario SET estado = 'ocupado' WHERE id_horario = '$id_horario'");
    echo "<script>alert('¡Cita agendada correctamente para el paciente!'); window.location.href='$redireccion';</script>";
} else {
    echo "<script>alert('Error al agendar: " . addslashes(mysqli_error($conexion)) . "'); window.history.back();</script>";
}
?>
