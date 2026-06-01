<?php
/**
 * agenda_updates.php
 * API JSON — devuelve citas activas y canceladas recientes
 * para polling en tiempo real del doctor y asistente.
 * Solo accesible por doctor (tipo 1) o asistente (tipo 2).
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

$tipo = isset($_SESSION['id_tipo_usuario']) ? (int)$_SESSION['id_tipo_usuario'] : 0;
if (!isset($_SESSION['usuario_id']) || !in_array($tipo, [1, 2])) {
    echo json_encode(['ok' => false]);
    exit();
}

$res = mysqli_query($conexion,
    "SELECT
         c.id_cita,
         c.id_horario,
         u.id_usuario,
         CONCAT(u.nombre, ' ', COALESCE(u.apellido_paterno,'')) AS paciente,
         h.fecha,
         h.hora_inicio,
         e.estado        AS nombre_estado,
         c.id_estado_cita
     FROM cita c
     JOIN usuario     u ON c.id_usuario      = u.id_usuario
     JOIN horario     h ON c.id_horario      = h.id_horario
     JOIN estado_cita e ON c.id_estado_cita  = e.id_estado_cita
     WHERE (
         c.id_estado_cita IN (1, 4)
         OR (
             c.id_estado_cita = 3
             AND c.fecha_cancelacion >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
         )
     )
     AND h.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     ORDER BY
         FIELD(c.id_estado_cita, 1, 4, 3),
         h.fecha ASC, h.hora_inicio ASC");

$citas = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $citas[] = [
            'id_cita'        => (int)$row['id_cita'],
            'id_horario'     => (int)$row['id_horario'],
            'id_usuario'     => (int)$row['id_usuario'],
            'paciente'       => $row['paciente'],
            'fecha'          => $row['fecha'],
            'hora'           => substr($row['hora_inicio'], 0, 5),
            'estado'         => $row['nombre_estado'],
            'id_estado_cita' => (int)$row['id_estado_cita'],
        ];
    }
}

echo json_encode(['ok' => true, 'citas' => $citas]);
?>
