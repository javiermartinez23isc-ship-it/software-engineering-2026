<?php
// CORRECCIÓN: Ruta actualizada para subir dos niveles y entrar a config/
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Verificación de Seguridad
if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Doctor";

// 2. Consulta de citas AJUSTADA PARA PRUEBAS
// Eliminamos el filtro estricto de h.fecha = '$hoy' para que muestre 
// las citas que agendaste en tus fechas estáticas (como ese lunes).
$query_citas = "SELECT 
                    c.id_cita, 
                    u.nombre AS paciente, 
                    h.fecha,
                    h.hora_inicio, 
                    e.estado AS nombre_estado 
                FROM cita c 
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario 
                INNER JOIN horario h ON c.id_horario = h.id_horario 
                INNER JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita 
                WHERE c.id_estado_cita IN (1, 4) 
                ORDER BY h.fecha ASC, h.hora_inicio ASC";

$res_citas = mysqli_query($conexion, $query_citas);
$error_mysql = (!$res_citas) ? mysqli_error($conexion) : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Vital | Panel del Doctor</title>
    <link rel="stylesheet" href="../../public/assets/css/doctor.css">
</head>
<body>

    <div class="layout">
        <nav class="navbar-vital">
            <div style="display:flex; align-items:center;">
                <div style="background:white; border-radius:50%; width:35px; height:35px; margin-right:10px; display:flex; justify-content:center; align-items:center; overflow:hidden;">
                    <img src="../../public/img/logo_agenda_vital.png" style="width:80%;">
                </div>
                <strong style="color:#333;">Agenda Vital</strong>
            </div>
            <div style="color:#333; font-size: 0.9rem;">
                <a href="../../src/auth/logout.php" style="text-decoration:none; color:#333; margin-right:15px; font-weight:500;">Cerrar Sesión</a>
                <strong>👤 Dr. <?php echo $nombre_usuario; ?></strong>
            </div>
        </nav>

        <div class="content-wrapper">
            <nav class="sidebar">
                <div class="profile-circle"></div>
                <h3>Dr. Nava</h3>
                <p>Consultorio Privado</p>
                <hr width="80%" style="border: 0.5px solid #eee; margin-bottom: 20px;">
                
                <a href="javascript:void(0)" onclick="mostrar('agenda')" id="menu-agenda" class="active">📅 Agenda</a>
                <a href="javascript:void(0)" onclick="mostrar('bloquear')" id="menu-bloquear">🔒 Bloquear Horario</a>
                <a href="javascript:void(0)" onclick="mostrar('configuracion')" id="menu-configuracion">⚙️ Configuración</a>
            </nav>

            <main class="main">
                <div id="vista-agenda" class="seccion activa">
                    <h1>Agenda de Pacientes</h1>
                    <div class="card">
                        <h3>Próximas Citas Programadas</h3>
                        
                        <?php if ($error_mysql): ?>
                            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 10px;">
                                <strong>Error de Conexión:</strong> <?php echo $error_mysql; ?>
                            </div>
                        <?php else: ?>
                            <table>
                                <colgroup>
                                    <col style="width: 30%;">
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                    <col style="width: 30%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($res_citas) > 0): ?>
                                        <?php while($cita = mysqli_fetch_assoc($res_citas)): ?>
                                        <tr>
                                            <td><strong><?php echo $cita['paciente']; ?></strong></td>
                                            <td><?php echo $cita['fecha']; ?></td>
                                            <td><?php echo date("g:i a", strtotime($cita['hora_inicio'])); ?></td>
                                            <td>
                                                <a href="../../src/appointments/finalizar_cita.php?id=<?php echo $cita['id_cita']; ?>&status=2" class="btn-atender">Finalizar</a>
                                                <a href="../../src/appointments/finalizar_cita.php?id=<?php echo $cita['id_cita']; ?>&status=5" class="btn-cancelar" onclick="return confirm('¿Marcar como inasistencia?')">Faltó</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">No hay citas pendientes en el sistema.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="vista-bloquear" class="seccion">
                    <h1>🔒 Bloquear Horario</h1>
                    <div class="card">
                        <h3>Gestión de Disponibilidad</h3>
                        <p>Inhabilita horarios aquí.</p>
                    </div>
                </div>
                
                <div id="vista-configuracion" class="seccion">
                    <h1>⚙️ Configuración</h1>
                    <div class="card">
                        <h3>Ajustes de Perfil</h3>
                        <p>Administra tu cuenta aquí.</p>
                    </div>
                </div>
            </main>
        </div>

        <footer class="footer-vital"></footer>
    </div>

    <script src="../../public/assets/js/doctor.js"></script>
</body>
</html>