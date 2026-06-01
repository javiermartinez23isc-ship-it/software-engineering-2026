<?php
/**
 * reprogramar_cita.php
 * Cancela la cita actual del paciente y registra la nueva en un solo paso.
 * - Máximo 2 reprogramaciones por día por paciente.
 * - Registra en tabla reprogramacion para notificar al doctor.
 * Solo accesible por pacientes (tipo 3).
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['id_tipo_usuario'] !== 3) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/roles/paciente.php');
    exit();
}

$id_usuario       = (int)$_SESSION['usuario_id'];
$id_cita_vieja    = isset($_POST['id_cita_vieja'])    ? (int)$_POST['id_cita_vieja']    : 0;
$id_horario_viejo = isset($_POST['id_horario_viejo']) ? (int)$_POST['id_horario_viejo'] : 0;
$id_horario_nuevo = isset($_POST['id_horario'])       ? (int)$_POST['id_horario']       : 0;
$fecha_slot       = mysqli_real_escape_string($conexion, trim($_POST['fecha_slot'] ?? ''));
$hora_slot        = mysqli_real_escape_string($conexion, trim($_POST['hora_slot']  ?? ''));
$destino          = '../../views/roles/paciente.php';

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

// Verificar que la cita pertenece al paciente
$res_own = mysqli_query($conexion,
    "SELECT c.id_cita, h.fecha AS fecha_ant, h.hora_inicio AS hora_ant
     FROM cita c
     JOIN horario h ON c.id_horario = h.id_horario
     WHERE c.id_cita = '$id_cita_vieja' AND c.id_usuario = '$id_usuario'");
if (mysqli_num_rows($res_own) === 0) {
    echo "<script>alert('Error: no tienes permiso para modificar esta cita.'); window.location.href='$destino';</script>";
    exit();
}
$datos_cita_vieja = mysqli_fetch_assoc($res_own);
$fecha_anterior   = $datos_cita_vieja['fecha_ant'];
$hora_anterior    = $datos_cita_vieja['hora_ant'];

// ── Límite: máximo 2 reprogramaciones por día ─────────────────────────────────
$hoy = date('Y-m-d');
$check_limite = mysqli_query($conexion,
    "SELECT COUNT(*) AS total FROM reprogramacion
     WHERE id_usuario = '$id_usuario'
       AND DATE(fecha_registro) = '$hoy'");
$row_limite = mysqli_fetch_assoc($check_limite);
if ((int)$row_limite['total'] >= 2) {
    echo "<script>alert('Has alcanzado el límite de 2 reprogramaciones por día. Intenta mañana.'); window.location.href='$destino';</script>";
    exit();
}

// ── PASO 1: Liberar el horario anterior ──────────────────────────────────────
mysqli_query($conexion,
    "UPDATE horario SET estado = 'disponible'
     WHERE id_horario = '$id_horario_viejo' AND disponible = 1");

// ── PASO 2: Cancelar la cita anterior ────────────────────────────────────────
$del = mysqli_query($conexion,
    "UPDATE cita SET id_estado_cita = 3, fecha_cancelacion = NOW()
     WHERE id_cita = '$id_cita_vieja'");
if (!$del) {
    echo "<script>alert('Error al cancelar la cita anterior: " . addslashes(mysqli_error($conexion)) . "'); window.location.href='$destino';</script>";
    exit();
}

// ── PASO 3: Resolver el nuevo horario ────────────────────────────────────────
if ($id_horario_nuevo > 0) {
    $res_h = mysqli_query($conexion,
        "SELECT id_horario, estado, disponible FROM horario WHERE id_horario = '$id_horario_nuevo'");
    $row_h = mysqli_fetch_assoc($res_h);
    if (!$row_h || $row_h['estado'] === 'ocupado' || (int)$row_h['disponible'] === 0) {
        echo "<script>alert('El horario seleccionado ya no está disponible. Elige otro.'); window.location.href='$destino';</script>";
        exit();
    }
} else {
    $hora_fin  = date('H:i:s', strtotime($hora_slot) + 3600);
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

if (!$ins) {
    echo "<script>alert('Error al registrar la nueva cita: " . addslashes(mysqli_error($conexion)) . "'); window.location.href='$destino';</script>";
    exit();
}

$id_cita_nueva = (int)mysqli_insert_id($conexion);

// Marcar horario como ocupado
mysqli_query($conexion,
    "UPDATE horario SET estado = 'ocupado' WHERE id_horario = '$id_horario_nuevo'");

// Crear recordatorio para la nueva cita
$fecha_envio = date('Y-m-d H:i:s', strtotime($fecha_slot . ' ' . $hora_slot) - 3600);
mysqli_query($conexion,
    "INSERT INTO recordatorio (id_cita, fecha_envio, enviado)
     VALUES ('$id_cita_nueva', '$fecha_envio', 0)");

// ── PASO 5: Registrar la reprogramación para notificar al doctor ──────────────
$fecha_ant_esc = mysqli_real_escape_string($conexion, $fecha_anterior);
$hora_ant_esc  = mysqli_real_escape_string($conexion, $hora_anterior);
mysqli_query($conexion,
    "INSERT INTO reprogramacion (id_usuario, id_cita_nueva, fecha_anterior, hora_anterior, fecha_nueva, hora_nueva)
     VALUES ('$id_usuario', '$id_cita_nueva', '$fecha_ant_esc', '$hora_ant_esc', '$fecha_slot', '$hora_slot')");

echo "<script>alert('✅ Cita reprogramada con éxito.'); window.location.href='$destino';</script>";
?>
