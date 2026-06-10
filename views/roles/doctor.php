<?php
// CORRECCIÓN: Ruta actualizada para subir dos niveles y entrar a config/
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Verificación de Seguridad: sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

// 2. Guard de rol: solo el doctor (tipo 1) puede acceder
if ($_SESSION['id_tipo_usuario'] != 1) {
    header("location: ../auth/acceso_denegado.php");
    exit();
}

// Impedir que el navegador guarde esta página en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

$id_user = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Doctor";

// === LOGIC REFACTOR: NAVEGACIÓN DINÁMICA DE SEMANAS ===
// Detectar si se pasa una fecha de inicio por URL; si no, calcular el lunes de la semana en curso
if (isset($_GET['semana'])) {
    $fecha_base = strtotime($_GET['semana']);
} else {
    $fecha_base = time();
}

$dia_sem_base = (int)date('N', $fecha_base);
// Retroceder los días necesarios para fijar la marca de tiempo en el Lunes de esa semana
$lunes_ts_dinamico = $fecha_base - (($dia_sem_base - 1) * 86400);

// Generar una única matriz de la semana actual bajo consulta (Lun-Vie)
$semana_vista = [];
for ($d = 0; $d < 5; $d++) {
    $semana_vista[] = date('Y-m-d', $lunes_ts_dinamico + ($d * 86400));
}

// Configurar los controles de navegación
$url_anterior  = "?semana=" . date('Y-m-d', $lunes_ts_dinamico - (7 * 86400));
$url_siguiente = "?semana=" . date('Y-m-d', $lunes_ts_dinamico + (7 * 86400));
$url_hoy       = "?semana=" . date('Y-m-d', time() - (((int)date('N') - 1) * 86400));

// Variable requerida por tus reglas de descarte de fechas pasadas
$hoy_doc = date('Y-m-d');

// --- DATOS DEL DASHBOARD & AGENDA ---
// Consulta adaptada: Citas activas (Estados 1 o 4) agrupadas por cercanía cronológica
$query_citas = "SELECT 
                    c.id_cita, 
                    c.id_usuario, 
                    u.nombre AS paciente, 
                    h.fecha,
                    h.hora_inicio, 
                    e.estado AS nombre_estado,
                    (SELECT COUNT(*) FROM reprogramacion r WHERE r.id_cita_nueva = c.id_cita) AS fue_reprogramada
                FROM cita c 
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario 
                INNER JOIN horario h ON c.id_horario = h.id_horario 
                INNER JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita 
                WHERE c.id_estado_cita IN (1, 4)
                AND h.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY 
                    FIELD(c.id_estado_cita, 1, 4),
                    h.fecha ASC, h.hora_inicio ASC";

$res_citas   = mysqli_query($conexion, $query_citas);
$error_mysql = (!$res_citas) ? mysqli_error($conexion) : "";

// Métricas KPI dinámicas para el nuevo Dashboard superior (Datos basados en el día de hoy)
$total_hoy = 0;
$reprogramadas_hoy = 0;
$proxima_cita_texto = "Sin citas hoy";

if ($res_citas && mysqli_num_rows($res_citas) > 0) {
    while($row = mysqli_fetch_assoc($res_citas)) {
        if ($row['fecha'] === $hoy_doc) {
            $total_hoy++;
            if ((int)$row['fue_reprogramada'] > 0) {
                $reprogramadas_hoy++;
            }
            if ($proxima_cita_texto === "Sin citas hoy" && strtotime($row['hora_inicio']) >= time()) {
                $proxima_cita_texto = date("g:i a", strtotime($row['hora_inicio'])) . " - " . $row['paciente'];
            }
        }
    }
    mysqli_data_seek($res_citas, 0); // Reiniciar el puntero de datos para la tabla HTML inferior
}

// 3. Consulta de pacientes para la sección Historial
$query_pacientes_hist = "SELECT id_usuario, nombre, apellido_paterno, apellido_materno, correo 
                         FROM usuario 
                         WHERE id_tipo_usuario = 3 
                         ORDER BY nombre ASC";
$res_pacientes_hist = mysqli_query($conexion, $query_pacientes_hist);

// Rango de horas (9am-7pm)
$horas_doc = [];
for ($h = 9; $h < 19; $h++) {
    $horas_doc[] = sprintf('%02d:00:00', $h);
}

// Obtener los slots registrados en el rango de la semana seleccionada
$f_ini_doc = $semana_vista[0];
$f_fin_doc = $semana_vista[4];
$res_slots_doc = mysqli_query($conexion,
    "SELECT fecha, hora_inicio, id_horario, estado, disponible FROM horario
     WHERE fecha BETWEEN '$f_ini_doc' AND '$f_fin_doc'");
$slots_doc = [];
if ($res_slots_doc) {
    while ($r = mysqli_fetch_assoc($res_slots_doc)) {
        $slots_doc[$r['fecha'] . '|' . $r['hora_inicio']] = [
            'id'         => $r['id_horario'],
            'estado'     => $r['estado'],
            'disponible' => $r['disponible']
        ];
    }
}

// 5. Datos del perfil del doctor
$query_perfil = "SELECT nombre, apellido_paterno, apellido_materno, telefono, correo, foto_perfil 
                 FROM usuario WHERE id_usuario = '$id_user'";
$res_perfil = mysqli_query($conexion, $query_perfil);
$datos_perfil = $res_perfil ? mysqli_fetch_assoc($res_perfil) : [];
if (!isset($datos_perfil['foto_perfil'])) $datos_perfil['foto_perfil'] = null;

// 6. Datos del consultorio
$logo_consultorio   = '';
$nombre_consultorio = 'Consultorio Privado';
$res_conf = @mysqli_query($conexion, "SELECT clave, valor FROM configuracion_consultorio WHERE clave IN ('logo_consultorio','nombre_consultorio')");
if ($res_conf) {
    while ($row_conf = mysqli_fetch_assoc($res_conf)) {
        if ($row_conf['clave'] === 'logo_consultorio')   $logo_consultorio   = $row_conf['valor'];
        if ($row_conf['clave'] === 'nombre_consultorio') $nombre_consultorio = $row_conf['valor'];
    }
}

$msg_perfil      = isset($_GET['perfil'])      ? $_GET['perfil']      : '';
$msg_consultorio = isset($_GET['consultorio']) ? $_GET['consultorio'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Vital | Panel del Doctor</title>
    <link rel="stylesheet" href="../../public/assets/css/doctor.css">
    <style>
        /* CSS Adicional integrado para las nuevas mejoras del Dashboard y Controles */
        .dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid var(--doctor-main, #0ea5e9);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .kpi-card h4 { margin: 0; font-size: 0.85rem; color: #64748b; text-transform: uppercase; }
        .kpi-card p { margin: 8px 0 0; font-size: 1.6rem; font-weight: bold; color: #1e293b; }
        .kpi-card .kpi-subtext { font-size: 0.8rem; color: #94a3b8; font-weight: normal; }
        
        .nav-semana-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .btn-nav-vital {
            background: #475569;
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-nav-vital:hover { background: #334155; }
        .btn-nav-hoy { background: var(--doctor-main, #0ea5e9); }
        .btn-nav-hoy:hover { filter: brightness(0.9); }
        
        .btn-bloqueo-dia {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.72rem;
            cursor: pointer;
            width: 100%;
        }
        .btn-bloqueo-dia:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <div class="layout">
        <nav class="navbar-vital">
            <div class="nav-left">
                <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Menú">☰</button>
                <div class="logo-container">
                    <img src="../../public/assets/img/logo_agenda_vital.png" class="logo-img" alt="Logo Agenda Vital">
                </div>
                <span class="brand-name">Agenda Vital</span>
            </div>
            <div class="nav-right">
                <a href="../../src/auth/logout.php" class="logout-link">Cerrar Sesión</a>
                <span class="user-profile-nav">👤 Dr. <?php echo htmlspecialchars($nombre_usuario); ?></span>
            </div>
        </nav>

        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
        <div class="content-wrapper">
            <nav class="sidebar" id="sidebar">
                <div class="profile-circle consultorio-clickable" 
                     onclick="abrirModalConsultorio()" 
                     title="Configurar consultorio"
                     style="cursor:pointer; position:relative;">
                    <?php if (!empty($logo_consultorio)): ?>
                        <img src="../../public/<?php echo htmlspecialchars($logo_consultorio); ?>" 
                             alt="Logo del consultorio" id="sidebar-logo-consultorio">
                    <?php else: ?>
                        <span id="sidebar-logo-placeholder" style="font-size:1.6rem; display:flex; align-items:center; justify-content:center; width:100%; height:100%;">🏥</span>
                    <?php endif; ?>
                    <span style="position:absolute; bottom:0; right:0; background: #0ea5e9; color:white; border-radius:50%; width:20px; height:20px; font-size:0.65rem; display:flex; align-items:center; justify-content:center; border:2px solid white;">✏️</span>
                </div>
                <h3>Dr. <?php echo htmlspecialchars($datos_perfil['apellido_paterno'] ?? $nombre_usuario); ?></h3>
                <p id="sidebar-nombre-consultorio"><?php echo htmlspecialchars($nombre_consultorio); ?></p>
                <hr width="80%" style="border: 0.5px solid #eee; margin-bottom: 20px;">
                
                <a href="javascript:void(0)" onclick="mostrar('agenda')" id="menu-agenda" class="active">📅 Agenda</a>
                <a href="javascript:void(0)" onclick="mostrar('historial')" id="menu-historial">📁 Historial</a>
                <a href="javascript:void(0)" onclick="mostrar('bloquear')" id="menu-bloquear">🔒 Bloquear Horario</a>
                <a href="javascript:void(0)" onclick="mostrar('configuracion')" id="menu-configuracion">⚙️ Configuración</a>
            </nav>

            <main class="main">
                
                <div id="vista-agenda" class="seccion activa">
                    <h1>Agenda de Pacientes</h1>
                    
                    <div class="dashboard-kpis">
                        <div class="kpi-card" style="border-left-color: #0ea5e9;">
                            <h4>Citas para Hoy</h4>
                            <p><?php echo $total_hoy; ?> <span class="kpi-subtext">consultas</span></p>
                        </div>
                        <div class="kpi-card" style="border-left-color: #a855f7;">
                            <h4>Reprogramadas</h4>
                            <p><?php echo $reprogramadas_hoy; ?> <span class="kpi-subtext">pacientes</span></p>
                        </div>
                        <div class="kpi-card" style="border-left-color: #eab308;">
                            <h4>Próximo Paciente</h4>
                            <p style="font-size: 1rem; margin-top: 15px; color: #475569;"><?php echo htmlspecialchars($proxima_cita_texto); ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Próximas Citas Programadas</h3>
                        
                        <?php if ($error_mysql): ?>
                            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 10px;">
                                <strong>Error de Conexión:</strong> <?php echo $error_mysql; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-wrap">
                            <table>
                                <colgroup>
                                    <col style="width: 25%;">
                                    <col style="width: 18%;">
                                    <col style="width: 15%;">
                                    <col style="width: 17%;">
                                    <col style="width: 25%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-agenda">
                                    <?php
                                    function pillEstiloDoctor($estado) {
                                        switch (strtolower(trim($estado))) {
                                            case 'confirmada':     return ['bg'=>'#dcfce7','color'=>'#166534','border'=>'#bbf7d0','icono'=>'✅'];
                                            case 'cancelada':      return ['bg'=>'#fee2e2','color'=>'#991b1b','border'=>'#fecaca','icono'=>'❌'];
                                            case 'reprogramada':   return ['bg'=>'#ede9fe','color'=>'#5b21b6','border'=>'#c4b5fd','icono'=>'🔄'];
                                            default:               return ['bg'=>'#fef9c3','color'=>'#92400e','border'=>'#fde68a','icono'=>'⏳'];
                                        }
                                    }
                                    if (mysqli_num_rows($res_citas) > 0): ?>
                                        <?php while($cita = mysqli_fetch_assoc($res_citas)):
                                            $estado_mostrar = ((int)$cita['fue_reprogramada'] > 0 && strtolower($cita['nombre_estado']) === 'pendiente')
                                                ? 'Reprogramada'
                                                : $cita['nombre_estado'];
                                            $p = pillEstiloDoctor($estado_mostrar); ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cita['paciente']); ?></strong></td>
                                            <td><?php echo $cita['fecha']; ?></td>
                                            <td><?php echo date("g:i a", strtotime($cita['hora_inicio'])); ?></td>
                                            <td>
                                                <span style="display:inline-block;padding:3px 10px;border-radius:18px;font-size:0.75rem;font-weight:700;white-space:nowrap;background:<?php echo $p['bg']; ?>;color:<?php echo $p['color']; ?>;border:1px solid <?php echo $p['border']; ?>;">
                                                    <?php echo $p['icono'] . ' ' . htmlspecialchars($estado_mostrar); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="td-actions">
                                                    <a href="historial.php?id=<?php echo $cita['id_usuario']; ?>" style="background:#0ea5e9;color:white;padding:6px 12px;border-radius:20px;text-decoration:none;font-size:0.78rem;font-weight:bold;">📋 Historial</a>
                                                    <?php if (strtolower(trim($cita['nombre_estado'])) !== 'cancelada'): ?>
                                                        <a href="../../src/appointments/finalizar_cita.php?id=<?php echo $cita['id_cita']; ?>&status=2" class="btn-atender">✔ Finalizar</a>
                                                        <a href="../../src/appointments/finalizar_cita.php?id=<?php echo $cita['id_cita']; ?>&status=5" class="btn-cancelar" onclick="return confirm('¿Marcar como inasistencia?')">✖ Faltó</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#64748b;">No hay citas en el sistema.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="vista-historial" class="seccion">
                    <h1>📁 Historial de Pacientes</h1>
                    <div class="card">
                        <h3>Selecciona un Paciente</h3>
                        <p style="color:#64748b; font-size:0.9rem; margin-bottom:20px;">
                            Haz clic en "Ver Historial" para consultar o agregar entradas al historial médico del paciente.
                        </p>
                        <?php if ($res_pacientes_hist && mysqli_num_rows($res_pacientes_hist) > 0): ?>
                            <div class="table-wrap">
                            <table>
                                <colgroup>
                                    <col style="width:40%;">
                                    <col style="width:35%;">
                                    <col style="width:25%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Correo</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($pac = mysqli_fetch_assoc($res_pacientes_hist)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellido_paterno'] . ' ' . $pac['apellido_materno']); ?></strong>
                                        </td>
                                        <td style="color:#64748b; font-size:0.9rem;">
                                            <?php echo htmlspecialchars($pac['correo']); ?>
                                        </td>
                                        <td>
                                            <a href="historial.php?id=<?php echo $pac['id_usuario']; ?>"
                                               style="background:#0ea5e9; color:white; padding:8px 15px; border-radius:20px; text-decoration:none; font-size:0.85rem; font-weight:bold; display:inline-block;">
                                                📋 Ver Historial
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else: ?>
                            <div style="text-align:center; padding:40px; color:#64748b;">
                                <p style="font-size:2.5rem; margin:0;">👥</p>
                                <p>No hay pacientes registrados en el sistema.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="vista-bloquear" class="seccion">
                    <h1>🔒 Bloquear Horario</h1>
                    <div class="card">
                        <h3>Gestión de Disponibilidad</h3>
                        <p style="color:#64748b; font-size:0.9rem; margin-bottom:20px;">
                            Navega entre semanas usando los controles. Haz clic en un horario libre (✅) para bloquearlo o en un candado (🔒) para liberarlo.
                        </p>

                        <div class="nav-semana-container">
                            <a href="<?php echo $url_anterior; ?>" class="btn-nav-vital">← Semana Anterior</a>
                            <span style="font-weight: bold; color: #334155;">
                                Semana del <?php echo date('d/m/Y', strtotime($semana_vista[0])); ?> al <?php echo date('d/m/Y', strtotime($semana_vista[4])); ?>
                            </span>
                            <div>
                                <a href="<?php echo $url_hoy; ?>" class="btn-nav-vital btn-nav-hoy" style="margin-right:5px;">Esta Semana</a>
                                <a href="<?php echo $url_siguiente; ?>" class="btn-nav-vital">Semana Siguiente →</a>
                            </div>
                        </div>

                        <div style="overflow-x:auto; border:1px solid #ddd; border-radius:10px; margin-bottom:10px;">
                            <table style="width:100%; border-collapse:collapse; text-align:center; min-width:460px;">
                                <thead>
                                    <tr style="background:#475569; color:white;">
                                        <th style="padding:10px 8px; border:1px solid #333; font-size:0.82rem;">Hora</th>
                                        <?php 
                                        $dias_label_doc = ['Lun','Mar','Mié','Jue','Vie'];
                                        foreach ($semana_vista as $i => $fecha_d): 
                                        ?>
                                        <th style="padding:10px 8px; border:1px solid #333; font-size:0.82rem; text-align:center;">
                                            <?php echo $dias_label_doc[$i]; ?><br>
                                            <span style="font-weight:400; font-size:0.72rem CONTAINER;"><?php echo date('d/m', strtotime($fecha_d)); ?></span>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                    
                                    <tr style="background: #f8fafc;">
                                        <td style="padding:6px; border:1px solid #ddd; font-weight:bold; font-size:0.75rem; color:#475569;">Todo el día</td>
                                        <?php foreach ($semana_vista as $fecha_d): ?>
                                        <td style="padding:6px; border:1px solid #ddd;">
                                            <?php if ($fecha_d >= $hoy_doc): ?>
                                                <button type="button" class="btn-bloqueo-dia" onclick="bloquearDiaCompleto('<?php echo $fecha_d; ?>')">🚫 Bloquear</button>
                                            <?php else: ?>
                                                <span style="color:#cbd5e1; font-size:0.7rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horas_doc as $hora_d): ?>
                                    <tr>
                                        <td style="padding:8px; border:1px solid #ddd; font-weight:600; background:#f8fafc; font-size:0.8rem; white-space:nowrap;">
                                            <?php echo date('g:i a', strtotime($hora_d)); ?>
                                        </td>
                                        <?php foreach ($semana_vista as $fecha_d):
                                            $key_d = $fecha_d . '|' . $hora_d;
                                            $pasado = ($fecha_d < $hoy_doc);
                                            if ($pasado):
                                        ?>
                                            <td style="border:1px solid #ddd; background:#e2e8f0; color:#94a3b8; height:46px;">—</td>
                                        <?php else:
                                            if (isset($slots_doc[$key_d])):
                                                $sd = $slots_doc[$key_d];
                                                if ($sd['estado'] === 'ocupado' && $sd['disponible'] == 0):
                                        ?>
                                            <td style="border:1px solid #ddd; height:46px; padding:4px;">
                                                <form method="POST" action="../../src/schedule/bloquear_horario.php" style="margin:0;">
                                                    <input type="hidden" name="id_horario" value="<?php echo $sd['id']; ?>">
                                                    <input type="hidden" name="accion" value="desbloquear">
                                                    <input type="hidden" name="fecha_slot" value="<?php echo $fecha_d; ?>">
                                                    <input type="hidden" name="hora_slot" value="<?php echo $hora_d; ?>">
                                                    <button type="submit" title="Clic para desbloquear"
                                                        onclick="return confirm('¿Desbloquear este horario?')"
                                                        style="width:100%; height:38px; border:none; border-radius:6px; cursor:pointer; font-size:1rem; background:#fee2e2; color:#dc2626;">🔒</button>
                                                </form>
                                            </td>
                                        <?php elseif ($sd['estado'] === 'ocupado' && $sd['disponible'] == 1): ?>
                                            <td style="border:1px solid #ddd; height:46px; background:#fef9c3; color:#92400e; font-size:0.75rem; vertical-align:middle;">📅</td>
                                        <?php else: ?>
                                            <td style="border:1px solid #ddd; height:46px; padding:4px;">
                                                <form method="POST" action="../../src/schedule/bloquear_horario.php" style="margin:0;">
                                                    <input type="hidden" name="id_horario" value="<?php echo $sd['id']; ?>">
                                                    <input type="hidden" name="accion" value="bloquear">
                                                    <input type="hidden" name="fecha_slot" value="<?php echo $fecha_d; ?>">
                                                    <input type="hidden" name="hora_slot" value="<?php echo $hora_d; ?>">
                                                    <button type="submit" title="Clic para bloquear"
                                                        onclick="return confirm('¿Bloquear este horario? Los pacientes no podrán agendarlo.')"
                                                        style="width:100%; height:38px; border:none; border-radius:6px; cursor:pointer; font-size:1rem; background:#dcfce7; color:#16a34a;">✅</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                        <?php else: ?>
                                            <td style="border:1px solid #ddd; height:46px; padding:4px;">
                                                <form method="POST" action="../../src/schedule/bloquear_horario.php" style="margin:0;">
                                                    <input type="hidden" name="id_horario" value="0">
                                                    <input type="hidden" name="accion" value="bloquear">
                                                    <input type="hidden" name="fecha_slot" value="<?php echo $fecha_d; ?>">
                                                    <input type="hidden" name="hora_slot" value="<?php echo $hora_d; ?>">
                                                    <button type="submit" title="Clic para bloquear"
                                                        onclick="return confirm('¿Bloquear este horario? Los pacientes no podrán agendarlo.')"
                                                        style="width:100%; height:38px; border:none; border-radius:6px; cursor:pointer; font-size:1rem; background:#dcfce7; color:#16a34a;">✅</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top:10px; display:flex; gap:20px; font-size:0.82rem; color:#64748b; flex-wrap:wrap;">
                            <span>✅ Disponible — clic para bloquear</span>
                            <span>🔒 Bloqueado — clic para desbloquear</span>
                            <span>📅 Con cita activa — no modificable</span>
                        </div>
                    </div>
                </div>
                
                <div id="vista-configuracion" class="seccion">
                    <h1>⚙️ Configuración de Perfil</h1>
                    <div class="card" style="max-width:600px;">

                        <?php if ($msg_perfil === 'ok'): ?>
                            <div style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:12px 15px; border-radius:8px; margin-bottom:20px;">
                                ✅ Perfil actualizado correctamente.
                            </div>
                        <?php elseif ($msg_perfil === 'sin_cambios'): ?>
                            <div style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:12px 15px; border-radius:8px; margin-bottom:20px;">
                                ℹ️ No se realizaron cambios en el perfil.
                            </div>
                        <?php endif; ?>

                        <div style="text-align:center; margin-bottom:25px;">
                            <?php
                            $foto_actual = !empty($datos_perfil['foto_perfil'])
                                ? '../../public/' . htmlspecialchars($datos_perfil['foto_perfil'])
                                : '../../public/assets/img/imagen_persona_sin.png';
                            ?>
                            <img src="<?php echo $foto_actual; ?>" alt="Foto Perfil" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #cbd5e1;">
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    function bloquearDiaCompleto(fechaSeleccionada) {
        if (!confirm("¿Estás seguro de que deseas bloquear TODO el día " + fechaSeleccionada + "?\nEsta acción inhabilitará todas las horas laborables.")) {
            return;
        }

        // Listado de las horas a bloquear (coinciden exactamente con tu bucle PHP de horas)
        const horasJornada = [
            '09:00:00', '10:00:00', '11:00:00', '12:00:00', 
            '13:00:00', '14:00:00', '15:00:00', '16:00:00', 
            '17:00:00', '18:00:00'
        ];

        console.log("Iniciando procesamiento de envío de lote para el día: " + fechaSeleccionada);

        // Mapeamos cada hora a una promesa fetch reutilizando tu backend actual individual
        const promesasDeBloqueo = horasJornada.map(hora => {
            const formData = new FormData();
            formData.append('id_horario', '0'); // Al enviar 0 tu backend sabe que debe crear el registro si no existe
            formData.append('accion', 'bloquear');
            formData.append('fecha_slot', fechaSeleccionada);
            formData.append('hora_slot', hora);

            return fetch('../../src/schedule/bloquear_horario.php', {
                method: 'POST',
                body: formData
            });
        });

        // Esperamos a que todas las horas se procesen en lote en la base de datos
        Promise.all(promesasDeBloqueo)
        .then(() => {
            alert("¡Función de bloqueo masivo completada con éxito para el día " + fechaSeleccionada + "!");
            location.reload(); // Recarga la página para mostrar los checks como candados rojos
        })
        .catch(error => {
            console.error("Error en el lote:", error);
            alert("Ocurrió un error al intentar bloquear el día de forma masiva.");
        });
    }

    // Funciones básicas de interfaz que ya venían con tu dashboard
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    function mostrar(seccion) {
        document.querySelectorAll('.seccion').forEach(s => s.classList.remove('activa'));
        document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
        
        const target = document.getElementById('vista-' + seccion);
        const menu = document.getElementById('menu-' + seccion);
        if(target) target.classList.add('activa');
        if(menu) menu.classList.add('active');
    }
    </script>
</body>
</html>
