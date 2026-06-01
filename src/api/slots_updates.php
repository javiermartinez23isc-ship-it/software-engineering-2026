<?php
/**
 * slots_updates.php
 * API JSON — devuelve el estado actual de los slots del calendario
 * para polling en tiempo real del paciente.
 * Solo accesible por pacientes (tipo 3).
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['id_tipo_usuario'] !== 3) {
    echo json_encode(['ok' => false]);
    exit();
}

$hoy        = date('Y-m-d');
$hoy_ts     = strtotime($hoy);
$dia_semana = (int)date('N');
$lunes_ts   = $hoy_ts - (($dia_semana - 1) * 86400);

$fecha_ini = date('Y-m-d', $lunes_ts);
$fecha_fin = date('Y-m-d', $lunes_ts + 13 * 86400);

$res = mysqli_query($conexion,
    "SELECT fecha, hora_inicio, id_horario, estado, disponible
     FROM horario
     WHERE fecha BETWEEN '$fecha_ini' AND '$fecha_fin'");

$slots = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $key = $row['fecha'] . '|' . $row['hora_inicio'];
        $slots[$key] = [
            'id'         => (int)$row['id_horario'],
            'estado'     => $row['estado'],
            'disponible' => (int)$row['disponible'],
        ];
    }
}

echo json_encode(['ok' => true, 'slots' => $slots, 'hoy' => $hoy]);
?>
