<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Usuario";

// --- 1. LÓGICA DE VALIDACIÓN (Sincronizada exactamente con la vista) ---
// Quitamos el CURDATE de aquí para que si hay CUALQUIER cita pendiente (aunque sea vieja), 
// se muestre en el bloque de abajo y el usuario pueda cancelarla o verla.
$check_citas = mysqli_query($conexion, "SELECT c.id_cita FROM cita c 
                                        WHERE c.id_usuario = '$id_user' 
                                        AND c.id_estado_cita IN (1, 4)");
$tiene_cita = (mysqli_num_rows($check_citas) > 0);

// --- 2. LÓGICA DE LA AGENDA DINÁMICA ---
$query_h = "SELECT id_horario, hora_inicio, hora_fin, fecha, estado FROM horario ORDER BY fecha ASC, hora_inicio ASC";
$res_h = mysqli_query($conexion, $query_h);

$agenda = [];
if ($res_h) {
    while ($row = mysqli_fetch_assoc($res_h)) {
        $dias_esp = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
        $nombre_dia = $dias_esp[date('l', strtotime($row['fecha']))];
        $agenda[$row['hora_inicio']][$nombre_dia] = ['id' => $row['id_horario'], 'estado' => $row['estado']];
    }
}

function renderizarCelda($hora, $dia, $agenda) {
    if (isset($agenda[$hora][$dia])) {
        $datos = $agenda[$hora][$dia];
        return ($datos['estado'] == 'ocupado') 
            ? '<td class="cell-locked" title="No disponible">🔒</td>' 
            : '<td class="cell-free" data-id="'.$datos['id'].'" onclick="seleccionarCelda(this)"></td>';
    }
    return '<td class="cell-locked">--</td>'; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Vital | Consultorio Nava</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/paciente.css">
</head>
<body>

<div class="layout">
    <nav class="navbar-vital">
        <div class="nav-left">
            <div class="logo-container"><img src="../../public/img/logo_agenda_vital.png" class="logo-img"></div>
            <span class="brand-name">Agenda Vital</span>
        </div>
        <div class="nav-right">
            <a href="../../src/auth/logout.php" class="logout-link">Cerrar Sesión</a>
            <div class="user-profile-nav">👤 <?php echo $nombre_usuario; ?></div>
        </div>
    </nav>

    <div class="content-wrapper">
        <nav class="sidebar">
            <div class="profile-circle"></div>
            <p style="font-weight:700; text-align:center;">Paciente</p>
            <div class="sidebar-menu">
                <a onclick="ver('perfil')" id="m-perfil" class="active">👤 Mi Perfil</a>
                <a onclick="ver('agendar')" id="m-agendar">📅 Agendar Cita</a>
            </div>
        </nav>

        <main class="main">
            <div id="v-perfil" class="seccion activa">
                <div class="card">
                    <h1>Bienvenido, <?php echo $nombre_usuario; ?></h1>
                    
                    <?php if($tiene_cita): ?>
                        <div style="background: #fff9c4; padding: 15px; border-radius: 10px; color: #856404; margin-bottom: 20px; font-weight: bold; border: 1px solid #ffeeba;">
                            ⚠️ Tienes una cita activa en el sistema.
                        </div>
                    <?php endif; ?>

                    <p>Gestiona tus citas médicas de forma sencilla.</p>
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                    
                    <h3>Tu Cita Actual</h3>
                    <?php
                    // IMPORTANTE: Quitamos el filtro de fecha para que SIEMPRE muestre la cita que está bloqueando al usuario
                    $mi_cita = mysqli_query($conexion, "SELECT c.id_cita, h.fecha, h.hora_inicio, c.id_horario, c.id_estado_cita 
                                                        FROM cita c 
                                                        JOIN horario h ON c.id_horario = h.id_horario 
                                                        WHERE c.id_usuario = '$id_user' 
                                                        AND c.id_estado_cita IN (1, 4)
                                                        ORDER BY c.id_cita DESC 
                                                        LIMIT 1");

                    if ($cita_data = mysqli_fetch_assoc($mi_cita)) { ?>
                        <div style="background: #f0fdfa; padding: 20px; border-radius: 15px; border: 1px solid var(--nava-cian);">
                            <p><strong>Fecha:</strong> <?php echo $cita_data['fecha']; ?></p>
                            <p><strong>Hora:</strong> <?php echo date("g:i a", strtotime($cita_data['hora_inicio'])); ?></p>
                            <p><strong>Estado:</strong> <?php echo ($cita_data['id_estado_cita'] == 4) ? '✅ Confirmada' : '⏳ Pendiente'; ?></p>
                            <div style="margin-top: 15px;">
                                <a href="../../src/appointments/cancelar_cita.php?id=<?php echo $cita_data['id_cita']; ?>&horario=<?php echo $cita_data['id_horario']; ?>" 
                                   style="background: #ef4444; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: 600;" 
                                   onclick="return confirm('¿Estás seguro de que deseas cancelar tu cita?')">❌ Cancelar Cita</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <p style="color: #64748b;">No tienes citas próximas programadas.</p>
                        <button class="btn-submit" style="width: auto; padding: 10px 30px;" onclick="ver('agendar')">Agendar ahora</button>
                    <?php } ?>
                </div>
            </div>

            <div id="v-agendar" class="seccion">
                <h1>Agendar Nueva Cita</h1>
                <div class="card">
                    <?php if($tiene_cita): ?>
                        <div style="text-align: center; padding: 40px;">
                            <div style="font-size: 3rem; margin-bottom: 20px;">🚫</div>
                            <h3>Cita en Curso</h3>
                            <p>Ya tienes una cita registrada. Para agendar una nueva, primero debes cancelar la actual.</p>
                            <button class="btn-submit" style="width: auto; padding: 10px 30px;" onclick="ver('perfil')">Ir a mi cita</button>
                        </div>
                    <?php else: ?>
                        <div class="schedule-table-wrapper" style="overflow-x: auto;">
                            <table class="schedule-table">
                                <thead><tr><th>Hora</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr></thead>
                                <tbody>
                                    <?php foreach ($agenda as $hora => $dias): ?>
                                        <tr>
                                            <td class="cell-time"><?php echo date("g:i a", strtotime($hora)); ?></td>
                                            <?php echo renderizarCelda($hora, 'Lunes', $agenda);
                                                  echo renderizarCelda($hora, 'Martes', $agenda);
                                                  echo renderizarCelda($hora, 'Miércoles', $agenda);
                                                  echo renderizarCelda($hora, 'Jueves', $agenda);
                                                  echo renderizarCelda($hora, 'Viernes', $agenda); ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <form action="../../src/appointments/agendar_cita.php" method="POST">
                            <input type="hidden" name="id_horario" id="input-id-horario">
                            <button type="submit" class="btn-submit" id="btn-submit-cita" disabled>Confirmar cita</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="../../public/assets/js/paciente.js"></script>
</body>
</html>