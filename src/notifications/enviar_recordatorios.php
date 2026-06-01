<?php
/**
 * enviar_recordatorios.php
 * Revisa la tabla recordatorio y envía correos a los pacientes
 * cuya fecha_envio ya llegó y aún no fueron enviados.
 *
 * Se ejecuta automáticamente desde db.php en cada carga del sistema.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

// ── Configuración del remitente ───────────────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     'cuellarpamela291@gmail.com');
define('MAIL_PASS',     'hzgekhowyluwzant');
define('MAIL_FROM',     'cuellarpamela291@gmail.com');
define('MAIL_FROM_NAME','Agenda Vital — Consultorio');

/**
 * Busca recordatorios pendientes y envía un correo por cada uno.
 * @param mysqli $conexion
 */
function procesarRecordatorios($conexion) {
    $sql = "SELECT
                r.id_recordatorio,
                c.id_cita,
                c.id_estado_cita,
                u.nombre,
                u.apellido_paterno,
                u.correo,
                h.fecha,
                h.hora_inicio,
                e.estado AS nombre_estado
            FROM recordatorio r
            JOIN cita        c ON r.id_cita        = c.id_cita
            JOIN usuario     u ON c.id_usuario     = u.id_usuario
            JOIN horario     h ON c.id_horario     = h.id_horario
            JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita
            WHERE r.enviado        = 0
              AND r.fecha_envio   <= NOW()
              AND c.id_estado_cita IN (1, 4)
              AND CONCAT(h.fecha, ' ', h.hora_inicio) > NOW()
            ORDER BY r.fecha_envio ASC
            LIMIT 10";

    $res = mysqli_query($conexion, $sql);
    if (!$res) return;

    while ($row = mysqli_fetch_assoc($res)) {
        $enviado = enviarCorreoRecordatorio($row, $conexion);

        $id = (int)$row['id_recordatorio'];
        mysqli_query($conexion,
            "UPDATE recordatorio SET enviado = 1 WHERE id_recordatorio = '$id'");
    }
}

/**
 * Envía el correo de recordatorio al paciente.
 * @param array $datos  Fila con datos de la cita y el paciente
 * @return bool
 */
function enviarCorreoRecordatorio($datos, $conexion) {
    // Formatear fecha y hora
    $ts      = strtotime($datos['fecha'] . ' ' . $datos['hora_inicio']);
    $dias_es = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $dia_sem = ucfirst($dias_es[(int)date('w', $ts)]);
    $fecha_fmt = $dia_sem . ' ' . date('d/m/Y', $ts);

    $hh   = (int)date('H', $ts);
    $h12  = ($hh % 12) ?: 12;
    $ampm = $hh < 12 ? 'a.m.' : 'p.m.';
    $hora_fmt = $h12 . ':00 ' . $ampm;

    $nombre_paciente = htmlspecialchars($datos['nombre'] . ' ' . $datos['apellido_paterno']);
    $estado          = htmlspecialchars($datos['nombre_estado']);
    $id_cita         = (int)$datos['id_cita'];

    // Calcular cuándo es la cita para el asunto del correo
    $diff_dias = (int)floor((strtotime($datos['fecha']) - strtotime(date('Y-m-d'))) / 86400);
    if ($diff_dias <= 0) {
        $cuando = 'hoy';
    } elseif ($diff_dias === 1) {
        $cuando = 'mañana';
    } else {
        $cuando = 'en ' . $diff_dias . ' días';
    }

    // Generar token único para confirmación sin sesión
    $token = bin2hex(random_bytes(32));
    mysqli_query($conexion,
        "UPDATE cita SET token_confirmacion = '$token' WHERE id_cita = '$id_cita'");

    // URL de confirmación por correo (no requiere sesión)
    $url_confirmar = 'http://localhost/AgendaVital/src/appointments/confirmar_cita_email.php?token=' . $token;

    // ── Cuerpo del correo en HTML ─────────────────────────────────────────────
    $cuerpo_html = '
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f7f9;font-family:Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f9;padding:30px 0;">
        <tr><td align="center">
          <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1);">

            <!-- Encabezado -->
            <tr>
              <td style="background:#1a237e;padding:28px 32px;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:1.4rem;letter-spacing:.5px;">🔔 Recordatorio de Cita</h1>
                <p style="color:#90caf9;margin:6px 0 0;font-size:.9rem;">Agenda Vital — Consultorio</p>
              </td>
            </tr>

            <!-- Cuerpo -->
            <tr>
              <td style="padding:32px;">
                <p style="color:#333;font-size:1rem;margin:0 0 20px;">
                  Hola, <strong>' . $nombre_paciente . '</strong>.<br>
                  Te recordamos que tienes una cita médica próximamente.
                </p>

                <!-- Tarjeta de detalles -->
                <table width="100%" cellpadding="0" cellspacing="0"
                       style="background:#f0f7ff;border:1px solid #c8dff0;border-radius:12px;margin-bottom:24px;">
                  <tr>
                    <td style="padding:20px 24px;">
                      <p style="margin:0 0 10px;font-size:.85rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Detalles de tu cita</p>
                      <table cellpadding="0" cellspacing="0">
                        <tr>
                          <td style="padding:5px 12px 5px 0;color:#475569;font-size:.9rem;">📅 <strong>Fecha:</strong></td>
                          <td style="padding:5px 0;color:#1a237e;font-size:.9rem;font-weight:700;">' . $fecha_fmt . '</td>
                        </tr>
                        <tr>
                          <td style="padding:5px 12px 5px 0;color:#475569;font-size:.9rem;">🕐 <strong>Hora:</strong></td>
                          <td style="padding:5px 0;color:#1a237e;font-size:.9rem;font-weight:700;">' . $hora_fmt . '</td>
                        </tr>
                        <tr>
                          <td style="padding:5px 12px 5px 0;color:#475569;font-size:.9rem;">📋 <strong>Estado:</strong></td>
                          <td style="padding:5px 0;color:#1a237e;font-size:.9rem;font-weight:700;">' . $estado . '</td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>

                <!-- Botón confirmar (solo si está Pendiente) -->
                ' . ($datos['id_estado_cita'] == 1 ? '
                <p style="color:#475569;font-size:.88rem;margin:0 0 16px;">
                  ¿Confirmas tu asistencia? Haz clic en el botón:
                </p>
                <table cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td align="center">
                      <a href="' . $url_confirmar . '"
                         style="display:inline-block;background:#00bcd4;color:#fff;text-decoration:none;
                                padding:14px 36px;border-radius:32px;font-weight:700;font-size:1rem;">
                        ✅ Confirmar mi cita
                      </a>
                    </td>
                  </tr>
                </table>
                ' : '
                <p style="color:#166534;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;
                           padding:12px 16px;font-size:.88rem;margin:0;">
                  ✅ Tu cita ya está <strong>confirmada</strong>. ¡Te esperamos!
                </p>
                ') . '

              </td>
            </tr>

            <!-- Pie -->
            <tr>
              <td style="background:#f8fafc;padding:18px 32px;text-align:center;border-top:1px solid #e2e8f0;">
                <p style="color:#94a3b8;font-size:.78rem;margin:0;">
                  Este correo fue enviado automáticamente por Agenda Vital.<br>
                  Si tienes dudas, comunícate directamente con el consultorio.
                </p>
              </td>
            </tr>

          </table>
        </td></tr>
      </table>
    </body>
    </html>';

    // ── Envío con PHPMailer ───────────────────────────────────────────────────
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($datos['correo'], $datos['nombre']);

        $mail->isHTML(true);
        $mail->Subject = '🔔 Recordatorio: Tu cita es ' . $cuando . ' — ' . $hora_fmt;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = 'Hola ' . $datos['nombre'] . ', tienes una cita el ' . $fecha_fmt . ' a las ' . $hora_fmt . '. Estado: ' . $estado;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Silencioso — no interrumpir al usuario si el correo falla
        return false;
    }
}
?>
