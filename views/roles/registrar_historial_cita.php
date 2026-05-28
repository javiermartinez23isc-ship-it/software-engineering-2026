<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Solo el doctor puede acceder
if (!isset($_SESSION['usuario_id']) || $_SESSION['id_tipo_usuario'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

// Recibir parámetros de la URL
if (!isset($_GET['id_cita']) || !isset($_GET['id_paciente'])) {
    header("Location: doctor.php");
    exit();
}

$id_cita    = mysqli_real_escape_string($conexion, $_GET['id_cita']);
$id_paciente = mysqli_real_escape_string($conexion, $_GET['id_paciente']);

// Verificar que la cita exista y pertenezca al paciente
$check = mysqli_query($conexion, "SELECT c.id_cita, u.nombre, u.apellido_paterno, u.apellido_materno, h.fecha, h.hora_inicio
                                   FROM cita c
                                   JOIN usuario u ON c.id_usuario = u.id_usuario
                                   JOIN horario h ON c.id_horario = h.id_horario
                                   WHERE c.id_cita = '$id_cita' AND c.id_usuario = '$id_paciente'");
$datos_cita = mysqli_fetch_assoc($check);

if (!$datos_cita) {
    echo "<script>alert('Error: Cita no encontrada.'); window.location.href='doctor.php';</script>";
    exit();
}

// Mensaje de error si viene de un intento fallido
$error = isset($_GET['error']) ? htmlspecialchars(urldecode($_GET['error'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Historial - Agenda Vital</title>
    <link rel="stylesheet" href="../../public/assets/css/doctor.css">
    <style>
        .form-historial { max-width: 650px; margin: 0 auto; }
        .info-cita { background:#e9f2f9; border-left:4px solid #0056b3; padding:15px; border-radius:8px; margin-bottom:25px; }
        .info-cita h3 { margin:0 0 8px 0; color:#0056b3; }
        .info-cita p  { margin:3px 0; font-size:0.9rem; color:#475569; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-weight:bold; margin-bottom:6px; color:#475569; }
        .form-group input,
        .form-group textarea { width:100%; padding:11px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; font-family:inherit; font-size:0.95rem; }
        .form-group textarea { min-height:90px; resize:vertical; }
        .btn-guardar { background:#059669; color:white; border:none; padding:12px 30px; border-radius:25px; font-weight:bold; cursor:pointer; font-size:1rem; }
        .btn-omitir  { background:#e2e8f0; color:#475569; border:none; padding:12px 20px; border-radius:25px; font-weight:bold; cursor:pointer; font-size:0.9rem; margin-left:10px; text-decoration:none; display:inline-block; }
        .alerta-error { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; padding:12px 15px; border-radius:8px; margin-bottom:20px; }
    </style>
</head>
<body>
<div class="layout">
    <nav class="navbar-vital">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="background:white; border-radius:50%; width:35px; height:35px; display:flex; justify-content:center; align-items:center; overflow:hidden;">
                <img src="../../public/assets/img/logo_agenda_vital.png" style="width:90%; object-fit:contain;">
            </div>
            <strong style="color:#333;">Agenda Vital</strong>
        </div>
        <div style="color:#333; font-size:0.9rem;">
            <a href="doctor.php" style="text-decoration:none; color:#333; font-weight:500;">← Volver al Panel</a>
        </div>
    </nav>

    <div class="content-wrapper" style="justify-content:center;">
        <main class="main">
            <div class="form-historial">
                <h1>📋 Registrar Historial Médico</h1>
                <p style="color:#64748b; margin-bottom:20px;">La cita ha sido finalizada. Completa el historial médico del paciente.</p>

                <?php if ($error): ?>
                    <div class="alerta-error">⚠️ <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="info-cita">
                    <h3><?php echo htmlspecialchars($datos_cita['nombre'] . ' ' . $datos_cita['apellido_paterno'] . ' ' . $datos_cita['apellido_materno']); ?></h3>
                    <p><strong>Fecha de consulta:</strong> <?php echo $datos_cita['fecha']; ?></p>
                    <p><strong>Hora:</strong> <?php echo date("g:i a", strtotime($datos_cita['hora_inicio'])); ?></p>
                </div>

                <div class="card">
                    <form method="POST" action="../../src/historial/agregar_historial.php">
                        <input type="hidden" name="id_paciente" value="<?php echo $id_paciente; ?>">
                        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                        <input type="hidden" name="fecha_consulta" value="<?php echo $datos_cita['fecha']; ?>">

                        <div class="form-group">
                            <label for="motivo">Motivo de Consulta *</label>
                            <input type="text" id="motivo" name="motivo" maxlength="255"
                                   placeholder="Ej: Dolor de cabeza, fiebre, revisión general..." required>
                        </div>
                        <div class="form-group">
                            <label for="diagnostico">Diagnóstico *</label>
                            <textarea id="diagnostico" name="diagnostico"
                                      placeholder="Diagnóstico del médico..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tratamiento">Tratamiento *</label>
                            <textarea id="tratamiento" name="tratamiento"
                                      placeholder="Tratamiento indicado, medicamentos, indicaciones..." required></textarea>
                        </div>

                        <div style="margin-top:25px;">
                            <button type="submit" class="btn-guardar">💾 Guardar Historial</button>
                            <a href="doctor.php" class="btn-omitir"
                               onclick="return confirm('¿Omitir el registro del historial? La cita ya fue finalizada.')">
                                Omitir
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
