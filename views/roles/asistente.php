<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

$id_asis = $_SESSION['usuario_id'];
$nombre_asistente = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Usuario";
$hoy = date('Y-m-d');

// 1. Citas registradas (Sin filtro de hoy para pruebas)
$query_citas = "SELECT c.id_cita, c.id_horario, u.nombre as paciente, u.apellido_paterno, h.fecha, h.hora_inicio, e.estado as nombre_estado 
                FROM cita c 
                JOIN usuario u ON c.id_usuario = u.id_usuario 
                JOIN horario h ON c.id_horario = h.id_horario 
                JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita 
                WHERE c.id_estado_cita IN (1, 4)
                ORDER BY h.fecha ASC, h.hora_inicio ASC";
$res_citas = mysqli_query($conexion, $query_citas);

// 2. Pacientes para el select
$query_pacientes = "SELECT id_usuario, nombre, apellido_paterno FROM usuario WHERE id_tipo_usuario = 3 ORDER BY nombre ASC";
$res_pacientes = mysqli_query($conexion, $query_pacientes);

// 3. Cuadrícula de horarios
$query_h = "SELECT id_horario, hora_inicio, fecha, estado FROM horario ORDER BY hora_inicio ASC";
$res_h = mysqli_query($conexion, $query_h);
$agenda = [];
while ($row = mysqli_fetch_assoc($res_h)) {
    $dias_esp = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes'];
    $nombre_dia = $dias_esp[date('l', strtotime($row['fecha']))] ?? null;
    if($nombre_dia) {
        $agenda[$row['hora_inicio']][$nombre_dia] = ['id' => $row['id_horario'], 'estado' => $row['estado']];
    }
}

function renderizarCeldaAsistente($hora, $dia, $agenda) {
    if (isset($agenda[$hora][$dia])) {
        $datos = $agenda[$hora][$dia];
        if ($datos['estado'] == 'ocupado') {
            return '<td class="cell-locked" onclick="mostrarAlerta()">🔒</td>';
        } else {
            return '<td class="cell-free" data-id="'.$datos['id'].'" onclick="seleccionarCelda(this)"></td>';
        }
    }
    return '<td class="cell-locked">--</td>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultorio Nava - Panel de Asistencia</title>
    <link rel="stylesheet" href="../../public/assets/css/asistente.css">
</head>
<body>

<div class="layout">
    <nav class="navbar-vital">
        <div class="nav-left">
            <button class="hamburger-btn" onclick="toggleMenu()">☰</button>
            <div class="logo-container">
                <img src="../../public/img/logo_agenda_vital.png" class="logo-img">
            </div>
            <span class="brand-name">Agenda Vital</span>
        </div>
        <div class="nav-right">
            <a href="../../src/auth/logout.php" class="logout-link">Cerrar Sesión</a>
            <div class="user-profile-nav">👤 <span><?php echo $nombre_asistente; ?></span></div>
        </div>
    </nav>

    <div class="content-wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="profile-circle"></div>
            <h3>Asistente</h3>
            <p class="sidebar-subtitle">Agenda Vital</p>
            <hr class="sidebar-hr">
            <a onclick="ver('agenda')" id="m-agenda" class="menu-link active">📅 Agenda Dr. Nava</a>
            <a onclick="ver('nuevo')" id="m-nuevo" class="menu-link">🗓️ Registrar Cita</a>
            <a onclick="ver('registro')" id="m-registro" class="menu-link">👤 Registrar Paciente</a>
        </nav>

        <main class="main">
            <!-- SECCIÓN: AGENDA -->
            <div id="v-agenda" class="section active">
                <h1>Agenda del Dr. Nava</h1>
                <div class="card">
                    <h3>Citas Registradas</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($res_citas) > 0): ?>
                                <?php while($c = mysqli_fetch_assoc($res_citas)): ?>
                                <tr>
                                    <td><strong><?php echo $c['paciente']." ".$c['apellido_paterno']; ?></strong></td>
                                    <td><?php echo $c['fecha']; ?></td>
                                    <td><?php echo date("g:i a", strtotime($c['hora_inicio'])); ?></td>
                                    <td><span class="status-pill"><?php echo $c['nombre_estado']; ?></span></td>
                                    <td>
                                        <a href="../../src/appointments/cancelar_cita.php?id=<?php echo $c['id_cita']; ?>&horario=<?php echo $c['id_horario']; ?>" 
                                           onclick="return confirm('¿Borrar esta cita?')" class="btn-delete">❌ Borrar</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No hay citas registradas en el sistema.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn-primary-action" onclick="ver('nuevo')">+ Nueva Cita</button>
            </div>

            <!-- SECCIÓN: REGISTRAR CITA -->
            <div id="v-nuevo" class="section">
                <h1>🗓️ Nueva Cita para Dr. Nava</h1>
                <div class="card card-form-wide">
                    <form action="../../src/appointments/agendar_asistente.php" method="POST">
                        <label>Paciente Registrado:</label>
                        <select name="id_usuario" required class="select-full">
                            <option value="">-- Buscar por nombre --</option>
                            <?php mysqli_data_seek($res_pacientes, 0); while($p = mysqli_fetch_assoc($res_pacientes)): ?>
                                <option value="<?php echo $p['id_usuario']; ?>"><?php echo $p['nombre']." ".$p['apellido_paterno']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <div class="form-row-flex">
                            <div class="form-box-info">
                                <div class="box-title">Disponibilidad de Agenda</div>
                                <!-- Aquí eliminamos el calendario solicitado -->
                                <button type="button" class="btn-secondary" onclick="mostrarTabla()">Consultar Disponibilidad</button>
                                <p class="box-note">Visualiza los horarios en la tabla inferior.</p>
                            </div>
                            <div class="form-box-info text-center">
                                <div class="box-title">Horario Seleccionado</div>
                                <span id="status-text" class="status-selection">Ningún horario seleccionado</span>
                                <button type="button" id="btn-cancelar-hora" onclick="cancelarSeleccion()" class="btn-link-cancel">Cancelar</button>
                            </div>
                        </div>

                        <div class="schedule-table-wrapper" id="schedule-container">
                            <table class="schedule-table">
                                <thead><tr><th>Hora</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr></thead>
                                <tbody>
                                    <?php foreach ($agenda as $hora => $dias): ?>
                                    <tr>
                                        <td class="cell-time"><?php echo date("g:i a", strtotime($hora)); ?></td>
                                        <?php echo renderizarCeldaAsistente($hora,'Lunes',$agenda); 
                                              echo renderizarCeldaAsistente($hora,'Martes',$agenda); 
                                              echo renderizarCeldaAsistente($hora,'Miércoles',$agenda); 
                                              echo renderizarCeldaAsistente($hora,'Jueves',$agenda); 
                                              echo renderizarCeldaAsistente($hora,'Viernes',$agenda); ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" name="id_horario" id="id_horario_input">
                        <button type="submit" class="btn-confirmar" id="btn-submit-cita" disabled>Confirmar Cita en Agenda</button>
                    </form>
                </div>
            </div>

            <!-- SECCIÓN: REGISTRAR PACIENTE -->
            <div id="v-registro" class="section">
                <h1>👤 Crear Cuenta de Paciente</h1>
                <div class="card card-form-narrow">
                    <form action="../../src/patients/registrar_paciente_asis.php" method="POST">
                        <label>Nombre(s):</label>
                        <input type="text" name="nombre" required placeholder="Ej. María">
                        
                        <div class="form-grid-2">
                            <div><label>Apellido Paterno:</label><input type="text" name="ap_paterno" required placeholder="López"></div>
                            <div><label>Correo Electrónico:</label><input type="email" name="usuario" required placeholder="correo@ejemplo.com"></div>
                        </div>
                        
                        <hr class="form-hr">
                        <label>Contraseña Provisional:</label>
                        <input type="text" value="Nava2026*" readonly class="input-readonly">
                        
                        <button type="submit" class="btn-confirmar">Registrar Paciente</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="alert-overlay" id="alerta-overlay"></div>
<div class="alert-box" id="alerta-box">
    <div class="alert-icon">⚠️</div>
    <p class="alert-text">Este horario no está disponible</p>
    <button onclick="cerrarAlerta()" class="btn-alert-close">Cerrar</button>
</div>

<script src="../../public/assets/js/asistente.js"></script>
</body>
</html>