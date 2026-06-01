<?php
/**
 * test_recordatorio.php — Prueba el envío del recordatorio real al paciente.
 * Accede desde: http://localhost/AgendaVital/src/notifications/test_recordatorio.php
 * ELIMINAR después de confirmar que funciona.
 */
include_once(__DIR__ . '/../../config/db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

echo '<h2>🔍 Diagnóstico de recordatorios</h2>';

// 1. Ver recordatorios pendientes
$res = mysqli_query($conexion,
    "SELECT r.id_recordatorio, r.fecha_envio, r.enviado,
            c.id_cita, c.id_estado_cita,
            u.nombre, u.correo,
            h.fecha, h.hora_inicio
     FROM recordatorio r
     JOIN cita c ON r.id_cita = c.id_cita
     JOIN usuario u ON c.id_usuario = u.id_usuario
     JOIN horario h ON c.id_horario = h.id_horario
     ORDER BY r.id_recordatorio DESC LIMIT 5");

echo '<h3>Recordatorios en BD:</h3><table border="1" cellpadding="6">';
echo '<tr><th>id</th><th>fecha_envio</th><th>enviado</th><th>estado_cita</th><th>correo</th><th>fecha_cita</th><th>hora_cita</th></tr>';
while ($row = mysqli_fetch_assoc($res)) {
    echo '<tr>';
    echo '<td>' . $row['id_recordatorio'] . '</td>';
    echo '<td>' . $row['fecha_envio'] . '</td>';
    echo '<td>' . $row['enviado'] . '</td>';
    echo '<td>' . $row['id_estado_cita'] . '</td>';
    echo '<td>' . $row['correo'] . '</td>';
    echo '<td>' . $row['fecha'] . '</td>';
    echo '<td>' . $row['hora_inicio'] . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<p>NOW() en MySQL: ';
$r = mysqli_query($conexion, "SELECT NOW() as n");
$rr = mysqli_fetch_assoc($r);
echo $rr['n'] . '</p>';

// 2. Intentar enviar manualmente al paciente con cita activa
$res2 = mysqli_query($conexion,
    "SELECT r.id_recordatorio, u.nombre, u.correo,
            h.fecha, h.hora_inicio, e.estado AS nombre_estado,
            c.id_estado_cita, c.id_cita
     FROM recordatorio r
     JOIN cita c ON r.id_cita = c.id_cita
     JOIN usuario u ON c.id_usuario = u.id_usuario
     JOIN horario h ON c.id_horario = h.id_horario
     JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita
     WHERE c.id_estado_cita IN (1,4)
     LIMIT 1");

$datos = mysqli_fetch_assoc($res2);
if (!$datos) {
    echo '<p style="color:red">❌ No hay citas activas con recordatorio.</p>';
    exit();
}

echo '<h3>Intentando enviar a: ' . htmlspecialchars($datos['correo']) . '</h3>';

$ts      = strtotime($datos['fecha'] . ' ' . $datos['hora_inicio']);
$hora_fmt = date('g:i a', $ts);
$fecha_fmt = date('d/m/Y', $ts);

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'cuellarpamela291@gmail.com';
    $mail->Password   = 'hzgekhowyluwzant';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('cuellarpamela291@gmail.com', 'Agenda Vital');
    $mail->addAddress($datos['correo'], $datos['nombre']);

    $mail->isHTML(true);
    $mail->Subject = '🔔 Recordatorio: Tu cita es hoy — ' . $hora_fmt;
    $mail->Body    = '<h2>Recordatorio de cita</h2><p>Hola <strong>' . htmlspecialchars($datos['nombre']) . '</strong>, tu cita es el <strong>' . $fecha_fmt . '</strong> a las <strong>' . $hora_fmt . '</strong>.</p>';
    $mail->AltBody = 'Tu cita es el ' . $fecha_fmt . ' a las ' . $hora_fmt;

    $mail->send();
    echo '<h2 style="color:green">✅ Correo enviado a ' . htmlspecialchars($datos['correo']) . '</h2>';

} catch (Exception $e) {
    echo '<h2 style="color:red">❌ Error: ' . htmlspecialchars($mail->ErrorInfo) . '</h2>';
}
?>
