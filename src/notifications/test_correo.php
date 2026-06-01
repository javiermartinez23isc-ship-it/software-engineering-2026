<?php
/**
 * test_correo.php — Script de prueba para verificar el envío de correo.
 * Accede desde: http://localhost/AgendaVital/src/notifications/test_correo.php
 * ELIMINAR después de confirmar que funciona.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Activar debug para ver qué pasa
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
    $mail->addAddress('cuellarpamela291@gmail.com', 'Jorge');

    $mail->isHTML(true);
    $mail->Subject = 'Prueba de correo — Agenda Vital';
    $mail->Body    = '<h2>✅ El sistema de correo funciona correctamente.</h2><p>Este es un correo de prueba de Agenda Vital.</p>';
    $mail->AltBody = 'El sistema de correo funciona correctamente.';

    $mail->send();
    echo '<h2 style="color:green">✅ Correo enviado correctamente</h2>';

} catch (Exception $e) {
    echo '<h2 style="color:red">❌ Error al enviar</h2>';
    echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
}
?>
