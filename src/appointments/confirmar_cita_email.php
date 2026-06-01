<?php
/**
 * confirmar_cita_email.php
 * Confirma una cita desde el enlace del correo usando un token único.
 * NO requiere sesión activa — el token es la autenticación.
 */
include_once(__DIR__ . '/../../config/db.php');

$token = isset($_GET['token']) ? mysqli_real_escape_string($conexion, $_GET['token']) : '';

if (empty($token)) {
    die('<p style="font-family:sans-serif;color:red;text-align:center;margin-top:60px;">❌ Enlace inválido.</p>');
}

// Buscar la cita por token
$res = mysqli_query($conexion,
    "SELECT c.id_cita, c.id_estado_cita, u.nombre,
            h.fecha, h.hora_inicio
     FROM cita c
     JOIN usuario u ON c.id_usuario = u.id_usuario
     JOIN horario h ON c.id_horario = h.id_horario
     WHERE c.token_confirmacion = '$token'
       AND c.id_estado_cita = 1
     LIMIT 1");

if (!$res || mysqli_num_rows($res) === 0) {
    // Token no encontrado o cita ya confirmada/cancelada
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Agenda Vital</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:60px 20px;background:#f4f7f9;">
        <div style="background:#fff;border-radius:16px;padding:40px;max-width:420px;margin:auto;box-shadow:0 4px 20px rgba(0,0,0,.1);">
            <p style="font-size:3rem;margin:0;">ℹ️</p>
            <h2 style="color:#1a237e;">Cita ya procesada</h2>
            <p style="color:#64748b;">Esta cita ya fue confirmada anteriormente o ya no está disponible.</p>
            <a href="http://localhost/AgendaVital/views/auth/login.php"
               style="display:inline-block;margin-top:20px;background:#00bcd4;color:#fff;padding:12px 28px;border-radius:24px;text-decoration:none;font-weight:700;">
                Ir al sistema
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$row = mysqli_fetch_assoc($res);

// Confirmar la cita
mysqli_query($conexion,
    "UPDATE cita SET id_estado_cita = 4, token_confirmacion = NULL
     WHERE token_confirmacion = '$token'");

// Formatear fecha y hora
$ts       = strtotime($row['fecha'] . ' ' . $row['hora_inicio']);
$dias_es  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$dia_sem  = ucfirst($dias_es[(int)date('w', $ts)]);
$fecha_fmt = $dia_sem . ' ' . date('d/m/Y', $ts);
$hh       = (int)date('H', $ts);
$h12      = ($hh % 12) ?: 12;
$ampm     = $hh < 12 ? 'a.m.' : 'p.m.';
$hora_fmt = $h12 . ':00 ' . $ampm;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cita Confirmada — Agenda Vital</title>
</head>
<body style="font-family:sans-serif;text-align:center;padding:60px 20px;background:#f4f7f9;">
    <div style="background:#fff;border-radius:16px;padding:40px;max-width:420px;margin:auto;box-shadow:0 4px 20px rgba(0,0,0,.1);">
        <p style="font-size:3rem;margin:0;">✅</p>
        <h2 style="color:#166534;margin:16px 0 8px;">¡Cita Confirmada!</h2>
        <p style="color:#475569;margin-bottom:24px;">
            Hola <strong><?php echo htmlspecialchars($row['nombre']); ?></strong>,<br>
            tu cita ha sido confirmada exitosamente.
        </p>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 20px;margin-bottom:24px;text-align:left;">
            <p style="margin:6px 0;color:#475569;">📅 <strong>Fecha:</strong> <?php echo $fecha_fmt; ?></p>
            <p style="margin:6px 0;color:#475569;">🕐 <strong>Hora:</strong> <?php echo $hora_fmt; ?></p>
            <p style="margin:6px 0;color:#166534;">📋 <strong>Estado:</strong> Confirmada</p>
        </div>
        <a href="http://localhost/AgendaVital/views/auth/login.php"
           style="display:inline-block;background:#00bcd4;color:#fff;padding:12px 28px;border-radius:24px;text-decoration:none;font-weight:700;">
            Ir al sistema
        </a>
    </div>
</body>
</html>
<?php
?>
