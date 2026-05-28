<?php
/**
 * reprogramar_cita.php
 * Cancela la cita actual del paciente y registra la nueva en un solo paso.
 * Solo accesible por pacientes (tipo 3).
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Seguridad: solo pacientes autenticados
if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['id_tipo_usuario'] !== 3) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/roles/paciente.php');
    exit();
}

$id_usuario      = (int)$_SESSION['usuario_id'];
$id_cita_vieja   = isset($_POST['id_cita_vieja'])   ? (int)$_POST['id_cita_vieja']   : 0;
$id_horario_viejo = isset($_POST['id_horario_viejo']) ? (int)$_POST['id_horario_viejo'] : 0;
$id_horario_nuevo = isset($_POST['id_horario'])       ? (int)$_POST['id_horario']       : 0;
$fecha_slot      = mysqli_real_escape_string($conexion, trim($_POST['fecha_slot'] ?? ''));
$hora_slot       = mysqli_real_escape_string($conexion, trim($_POST['hora_slot']  ?? ''));

$destino = '../../views/roles/paciente.php';

// Validaciones básicas
if ($id_cita_vieja <= 0 || $id_horario_viejo <= 0) {
    echo "<script>alert('Error: datos de la cita original inválidos.'); window.location.href='$destino';</script>";
    exit();
}
if (empty($fecha_slot) || empty($hora_slot)) {
    echo "<script>alert('Error: selecciona un nuevo horario.'); window.location.href='$destino';</script>";
    exit();
}
if ($fecha_slot < date('Y-m-d')) {
    echo "<script>alert('Error: no puedes agendar en una fecha pasada.'); window.location.href='$destino';</script>";
    exit();
}

// Verificar que la cita pertenece al paciente en sesión
$res_own = mysqli_query($conexion,
    "SELECT id_cita FROM cita WHERE id_cita = '$id_cita_vieja' AND id_usuario = '$id_usuario'");
if (mysqli_num_rows($res_own) === 0) {
    echo "<script>alert('Error: no tienes permiso para modificar esta cita.'); window.location.href='$destino';</script>";
    exit();
}

// ── PASO 1: Liberar el horario anterior ──────────────────────────────────────
mysqli_query($conexion,
    "UPDATE horario SET estado = 'disponible'
     WHERE id_horario = '$id_horario_viejo' AND disponible = 1");

// ── PASO 2: Eliminar la cita anterior ────────────────────────────────────────
$del = mysqli_query($conexion, "DELETE FROM cita WHERE id_cita = '$id_cita_vieja'");
if (!$del) {
    echo "<script>alert('Error al cancelar la cita anterior: " . addslashes(mysqli_error($conexion)) . "'); window.location.href='$destino';</script>";
    exit();
}

// ── PASO 3: Resolver el nuevo horario ────────────────────────────────────────
if ($id_horario_nuevo > 0) {
    // Slot existente en BD — verificar disponibilidad
    $res_h = mysqli_query($conexion,
        "SELECT id_horario, estado, disponible FROM horario WHERE id_horario = '$id_horario_nuevo'");
    $row_h = mysqli_fetch_assoc($res_h);
    if (!$row_h || $row_h['estado'] === 'ocupado' || (int)$row_h['disponible'] === 0) {
        echo "<script>alert('El horario seleccionado ya no está disponible. Elige otro.'); window.location.href='$destino';</script>";
        exit();
    }
} else {
    // Slot virtual — verificar que no exista ya ocupado/bloqueado
    $hora_fin = date('H:i:s', strtotime($hora_slot) + 3600);
    $res_exist = mysqli_query($conexion,
        "SELECT id_horario, estado, disponible FROM horario
         WHERE fecha = '$fecha_slot' AND hora_inicio = '$hora_slot'");
    if ($row_exist = mysqli_fetch_assoc($res_exist)) {
        if ($row_exist['estado'] === 'ocupado' || (int)$row_exist['disponible'] === 0) {
            echo "<script>alert('Este horario ya fue reservado o está bloqueado. Elige otro.'); window.location.href='$destino';</script>";
            exit();
        }
        $id_horario_nuevo = (int)$row_exist['id_horario'];
    } else {
        // Crear el horario nuevo
        mysqli_query($conexion,
            "INSERT INTO horario (fecha, hora_inicio, hora_fin, disponible, estado)
             VALUES ('$fecha_slot', '$hora_slot', '$hora_fin', 1, 'disponible')");
        $id_horario_nuevo = (int)mysqli_insert_id($conexion);
    }
}

// ── PASO 4: Insertar la nueva cita ───────────────────────────────────────────
$ins = mysqli_query($conexion,
    "INSERT INTO cita (id_usuario, id_horario, id_estado_cita, fecha_registro)
     VALUES ('$id_usuario', '$id_horario_nuevo', 1, NOW())");

if ($ins) {
    mysqli_query($conexion,
        "UPDATE horario SET estado = 'ocupado' WHERE id_horario = '$id_horario_nuevo'");
    echo "<script>alert('✅ Cita reprogramada con éxito.'); window.location.href='$destino';</script>";
} else {
    echo "<script>alert('Error al registrar la nueva cita: " . addslashes(mysqli_error($conexion)) . "'); window.location.href='$destino';</script>";
}
?>
