<?php
// Procesador: Bloquear / Desbloquear un horario
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}

// 2. Solo el doctor puede bloquear horarios
if ($_SESSION['id_tipo_usuario'] != 1) {
    header("Location: ../../views/roles/doctor.php");
    exit();
}

// 3. Verificar que lleguen los datos necesarios
if (!isset($_POST['accion'])) {
    header("Location: ../../views/roles/doctor.php");
    exit();
}

$accion      = $_POST['accion']; // 'bloquear' o 'desbloquear'
$id_horario  = isset($_POST['id_horario']) ? (int)$_POST['id_horario'] : 0;
$fecha_slot  = mysqli_real_escape_string($conexion, trim($_POST['fecha_slot'] ?? ''));
$hora_slot   = mysqli_real_escape_string($conexion, trim($_POST['hora_slot']  ?? ''));

// 4. Si no tenemos id_horario, necesitamos fecha y hora para buscar/crear el registro
if ($id_horario <= 0) {
    if (empty($fecha_slot) || empty($hora_slot)) {
        echo "<script>alert('Datos insuficientes para procesar el horario.'); window.location.href='../../views/roles/doctor.php';</script>";
        exit();
    }

    // Buscar si ya existe un registro para esa fecha/hora
    $res_exist = mysqli_query($conexion,
        "SELECT id_horario, estado, disponible FROM horario
         WHERE fecha = '$fecha_slot' AND hora_inicio = '$hora_slot'");
    if ($row_exist = mysqli_fetch_assoc($res_exist)) {
        $id_horario  = (int)$row_exist['id_horario'];
    } else {
        // Crear el registro (solo si vamos a bloquear)
        if ($accion === 'bloquear') {
            $hora_fin = date('H:i:s', strtotime($hora_slot) + 3600);
            mysqli_query($conexion,
                "INSERT INTO horario (fecha, hora_inicio, hora_fin, disponible, estado)
                 VALUES ('$fecha_slot', '$hora_slot', '$hora_fin', 0, 'ocupado')");
            $id_horario = (int)mysqli_insert_id($conexion);
            if ($id_horario > 0) {
                echo "<script>alert('Horario bloqueado correctamente.'); window.location.href='../../views/roles/doctor.php';</script>";
            } else {
                echo "<script>alert('Error al crear el horario: " . addslashes(mysqli_error($conexion)) . "'); window.location.href='../../views/roles/doctor.php';</script>";
            }
            exit();
        } else {
            // Desbloquear un slot que no existe — no hay nada que hacer
            header("Location: ../../views/roles/doctor.php");
            exit();
        }
    }
}

if ($accion === 'bloquear') {
    // Verificar que el horario no tenga una cita activa antes de bloquearlo
    $check = mysqli_query($conexion, "SELECT c.id_cita FROM cita c 
                                      WHERE c.id_horario = '$id_horario' 
                                      AND c.id_estado_cita IN (1, 4)");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>
                alert('No se puede bloquear: este horario tiene una cita activa.');
                window.location.href = '../../views/roles/doctor.php';
              </script>";
        exit();
    }
    $sql = "UPDATE horario SET estado = 'ocupado', disponible = 0 WHERE id_horario = '$id_horario'";
    $msg = 'Horario bloqueado correctamente.';
} elseif ($accion === 'desbloquear') {
    $sql = "UPDATE horario SET estado = 'disponible', disponible = 1 WHERE id_horario = '$id_horario'";
    $msg = 'Horario desbloqueado correctamente.';
} else {
    header("Location: ../../views/roles/doctor.php");
    exit();
}

if (mysqli_query($conexion, $sql)) {
    echo "<script>
            alert('" . addslashes($msg) . "');
            window.location.href = '../../views/roles/doctor.php';
          </script>";
} else {
    $error = addslashes(mysqli_error($conexion));
    echo "<script>
            alert('Error al actualizar el horario: $error');
            window.location.href = '../../views/roles/doctor.php';
          </script>";
}
?>
