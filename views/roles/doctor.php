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

// 2. Consulta de citas — solo activas (1, 2, 4), con flag si fue reprogramada
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
                WHERE c.id_estado_cita IN (1, 2,4)
                AND h.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY 
                    FIELD(c.id_estado_cita, 1, 2, 4),
                    h.fecha ASC, h.hora_inicio ASC";

$res_citas   = mysqli_query($conexion, $query_citas);
$error_mysql = (!$res_citas) ? mysqli_error($conexion) : "";

// 3. Consulta de pacientes para la sección Historial
$query_pacientes_hist = "SELECT id_usuario, nombre, apellido_paterno, apellido_materno, correo 
                         FROM usuario 
                         WHERE id_tipo_usuario = 3 
                         ORDER BY nombre ASC";
$res_pacientes_hist = mysqli_query($conexion, $query_pacientes_hist);

// 4. Calendario dinámico para bloquear/desbloquear (semana actual + siguiente, Lun-Vie, 9am-7pm)
$hoy_doc     = date('Y-m-d');
$hoy_ts_doc  = strtotime($hoy_doc);
$dia_sem_doc = (int)date('N');
$lunes_ts_doc = $hoy_ts_doc - (($dia_sem_doc - 1) * 86400);

$semanas_doc = [];
for ($s = 0; $s < 2; $s++) {
    $sem = [];
    for ($d = 0; $d < 5; $d++) {
        $sem[] = date('Y-m-d', $lunes_ts_doc + ($s * 7 + $d) * 86400);
    }
    $semanas_doc[] = $sem;
}

$horas_doc = [];
for ($h = 9; $h < 19; $h++) {
    $horas_doc[] = sprintf('%02d:00:00', $h);
}

// Obtener todos los slots registrados en BD para el rango
$f_ini_doc = $semanas_doc[0][0];
$f_fin_doc = $semanas_doc[1][4];
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

// 6. Datos del consultorio (tabla puede no existir aún)
$logo_consultorio   = '';
$nombre_consultorio = 'Consultorio Privado';
$res_conf = @mysqli_query($conexion, "SELECT clave, valor FROM configuracion_consultorio WHERE clave IN ('logo_consultorio','nombre_consultorio')");
if ($res_conf) {
    while ($row_conf = mysqli_fetch_assoc($res_conf)) {
        if ($row_conf['clave'] === 'logo_consultorio')   $logo_consultorio   = $row_conf['valor'];
        if ($row_conf['clave'] === 'nombre_consultorio') $nombre_consultorio = $row_conf['valor'];
    }
}

// Mensaje de éxito/error desde redirección
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
                <!-- Círculo del consultorio — clickeable solo para el doctor -->
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
                    <!-- Ícono de edición -->
                    <span style="position:absolute; bottom:0; right:0; background:var(--doctor-main); color:white; border-radius:50%; width:20px; height:20px; font-size:0.65rem; display:flex; align-items:center; justify-content:center; border:2px solid white;">✏️</span>
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
                                            // Si fue reprogramada pero ya está Confirmada, mostrar Confirmada
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
                            Haz clic en un horario disponible para bloquearlo. Los horarios bloqueados no aparecerán para los pacientes al agendar citas.
                        </p>

                        <?php
                        $dias_label_doc = ['Lun','Mar','Mié','Jue','Vie'];
                        foreach ($semanas_doc as $idx_sd => $semana_d):
                        ?>
                        <p style="font-weight:700; color:#475569; font-size:.82rem; margin:12px 0 5px;">
                            Semana <?php echo $idx_sd === 0 ? 'actual' : 'siguiente'; ?>
                            (<?php echo date('d/m', strtotime($semana_d[0])); ?> – <?php echo date('d/m', strtotime($semana_d[4])); ?>)
                        </p>
                        <div style="overflow-x:auto; border:1px solid #ddd; border-radius:10px; margin-bottom:10px;">
                            <table style="width:100%; border-collapse:collapse; text-align:center; min-width:460px;">
                                <thead>
                                    <tr style="background:#475569; color:white;">
                                        <th style="padding:10px 8px; border:1px solid #333; font-size:0.82rem;">Hora</th>
                                        <?php foreach ($semana_d as $i => $fecha_d): ?>
                                        <th style="padding:10px 8px; border:1px solid #333; font-size:0.82rem; text-align:center;">
                                            <?php echo $dias_label_doc[$i]; ?><br>
                                            <span style="font-weight:400; font-size:0.72rem;"><?php echo date('d/m', strtotime($fecha_d)); ?></span>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horas_doc as $hora_d): ?>
                                    <tr>
                                        <td style="padding:8px; border:1px solid #ddd; font-weight:600; background:#f8fafc; font-size:0.8rem; white-space:nowrap;">
                                            <?php echo date('g:i a', strtotime($hora_d)); ?>
                                        </td>
                                        <?php foreach ($semana_d as $fecha_d):
                                            $key_d = $fecha_d . '|' . $hora_d;
                                            $pasado = ($fecha_d < $hoy_doc);
                                            if ($pasado):
                                        ?>
                                            <td style="border:1px solid #ddd; background:#e2e8f0; color:#94a3b8; height:46px;">—</td>
                                        <?php else:
                                            if (isset($slots_doc[$key_d])):
                                                $sd = $slots_doc[$key_d];
                                                // Slot con cita activa: no se puede bloquear/desbloquear
                                                if ($sd['estado'] === 'ocupado' && $sd['disponible'] == 0):
                                                    // Bloqueado por el doctor
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
                                            <!-- Ocupado por cita — no se puede bloquear -->
                                            <td style="border:1px solid #ddd; height:46px; background:#fef9c3; color:#92400e; font-size:0.75rem; vertical-align:middle;">📅</td>
                                        <?php else: ?>
                                            <!-- Disponible -->
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
                                            <!-- Slot virtual — disponible, se crea al bloquear -->
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
                        <?php endforeach; ?>

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

                        <!-- Foto de perfil actual -->
                        <div style="text-align:center; margin-bottom:25px;">
                            <?php
                            $foto_actual = !empty($datos_perfil['foto_perfil'])
                                ? '../../public/' . htmlspecialchars($datos_perfil['foto_perfil'])
                                : '../../public/assets/img/imagen_persona_sin.png';
                            ?>
                            <img src="<?php echo $foto_actual; ?>" alt="Foto de perfil"
                                 style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #059669;">
                            <p style="font-size:0.8rem; color:#64748b; margin-top:8px;">Foto de perfil actual</p>
                        </div>

                        <form method="POST" action="../../src/profile/actualizar_perfil.php" enctype="multipart/form-data">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Nombre(s) *</label>
                                    <input type="text" name="nombre" required
                                           pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+"
                                           title="Solo letras y espacios, sin números ni símbolos"
                                           oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                                           value="<?php echo htmlspecialchars($datos_perfil['nombre'] ?? ''); ?>"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Apellido Paterno</label>
                                    <input type="text" name="apellido_paterno"
                                           pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]*"
                                           title="Solo letras y espacios, sin números ni símbolos"
                                           oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                                           value="<?php echo htmlspecialchars($datos_perfil['apellido_paterno'] ?? ''); ?>"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Apellido Materno</label>
                                    <input type="text" name="apellido_materno"
                                           pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]*"
                                           title="Solo letras y espacios, sin números ni símbolos"
                                           oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                                           value="<?php echo htmlspecialchars($datos_perfil['apellido_materno'] ?? ''); ?>"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Teléfono</label>
                                    <input type="tel" name="telefono"
                                           pattern="[0-9]{7,15}"
                                           title="Solo números, entre 7 y 15 dígitos"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                           maxlength="15"
                                           value="<?php echo htmlspecialchars($datos_perfil['telefono'] ?? ''); ?>"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                            </div>

                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Correo Electrónico *</label>
                                <input type="email" name="correo" required
                                       pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                                       title="Ingresa un correo válido con formato nombre@dominio.ext"
                                       value="<?php echo htmlspecialchars($datos_perfil['correo'] ?? ''); ?>"
                                       style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                            </div>

                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Foto de Perfil</label>
                                <input type="file" name="foto_perfil" accept="image/*"
                                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; background:white;">
                                <small style="color:#64748b;">JPG, PNG, GIF o WEBP. Máximo 2 MB.</small>
                                <?php if (!empty($datos_perfil['foto_perfil'])): ?>
                                <button type="submit" name="quitar_foto" value="1"
                                        onclick="return confirm('¿Estás seguro de que deseas quitar tu foto de perfil?')"
                                        style="margin-top:10px; background:none; border:1px solid #ef4444; color:#ef4444; padding:7px 14px; border-radius:8px; font-size:0.83rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .18s;"
                                        onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                                    🗑️ Quitar foto de perfil
                                </button>
                                <?php endif; ?>
                            </div>

                            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                            <p style="font-weight:bold; color:#475569; margin-bottom:10px;">Cambiar Contraseña <span style="font-weight:normal; font-size:0.85rem;">(dejar en blanco para no cambiar)</span></p>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Nueva Contraseña</label>
                                    <input type="password" name="nueva_password" placeholder="Mínimo 6 caracteres"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:bold; margin-bottom:5px; color:#475569;">Confirmar Contraseña</label>
                                    <input type="password" name="confirmar_password" placeholder="Repite la contraseña"
                                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                                </div>
                            </div>

                            <button type="submit"
                                    style="background:#059669; color:white; border:none; padding:12px 30px; border-radius:25px; font-weight:bold; cursor:pointer; font-size:1rem;">
                                💾 Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>

        <footer class="footer-vital"></footer>
    </div>

    <script src="../../public/assets/js/doctor.js"></script>

    <!-- ── Modal: Configuración del Consultorio ─────────────────── -->
    <div id="modal-consultorio-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.55); z-index:9999; overflow-y:auto; padding:20px; box-sizing:border-box;">
        <div style="background:#fff; border-radius:16px; padding:28px; width:min(420px,92vw); margin:auto; box-shadow:0 10px 30px rgba(0,0,0,.25); position:relative; margin-top:5vh;">
            
            <!-- Botón cerrar -->
            <button onclick="cerrarModalConsultorio()" 
                    style="position:absolute; top:12px; right:14px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:#64748b; line-height:1; padding:0 4px;">×</button>

            <h2 style="margin-bottom:6px; color:#1a237e; font-size:1.1rem;">🏥 Configuración del Consultorio</h2>
            <p style="font-size:0.82rem; color:#64748b; margin-bottom:20px;">Solo tú puedes editar estos datos. Se mostrarán a todos los usuarios.</p>

            <?php if ($msg_consultorio === 'ok'): ?>
                <div style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:0.88rem;">
                    ✅ Consultorio actualizado correctamente.
                </div>
            <?php endif; ?>

            <!-- Vista previa del logo actual -->
            <div style="text-align:center; margin-bottom:18px;">
                <?php if (!empty($logo_consultorio)): ?>
                    <img src="../../public/<?php echo htmlspecialchars($logo_consultorio); ?>" 
                         alt="Logo actual" id="modal-logo-preview"
                         style="max-height:80px; max-width:180px; object-fit:contain; border:2px solid #e2e8f0; border-radius:10px; padding:6px;">
                <?php else: ?>
                    <div id="modal-logo-preview" style="width:80px; height:80px; background:#f1f5f9; border-radius:50%; border:3px solid #059669; display:inline-flex; align-items:center; justify-content:center; font-size:2rem;">🏥</div>
                <?php endif; ?>
                <p style="font-size:0.75rem; color:#94a3b8; margin-top:6px;">Logo actual del consultorio</p>
            </div>

            <form method="POST" action="../../src/consultorio/actualizar_consultorio.php" enctype="multipart/form-data">

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-weight:700; margin-bottom:5px; color:#475569; font-size:0.88rem;">Nombre del Consultorio</label>
                    <input type="text" name="nombre_consultorio" 
                           value="<?php echo htmlspecialchars($nombre_consultorio); ?>"
                           placeholder="Ej. Consultorio Privado Dr. Nava"
                           maxlength="100"
                           style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:8px; font-size:0.92rem; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:700; margin-bottom:5px; color:#475569; font-size:0.88rem;">Logo del Consultorio</label>
                    <input type="file" name="logo_consultorio" accept="image/*" 
                           onchange="previewLogo(this)"
                           style="width:100%; padding:8px; border:1px solid #ddd; border-radius:8px; background:white; box-sizing:border-box; font-size:0.88rem;">
                    <small style="color:#64748b; font-size:0.78rem;">JPG, PNG, GIF o WEBP. Máximo 2 MB.</small>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" 
                            style="flex:1; background:#059669; color:white; border:none; padding:11px; border-radius:22px; font-weight:700; cursor:pointer; font-size:0.92rem;">
                        💾 Guardar
                    </button>
                    <button type="button" onclick="cerrarModalConsultorio()"
                            style="flex:1; background:#f1f5f9; color:#475569; border:1px solid #ddd; padding:11px; border-radius:22px; font-weight:700; cursor:pointer; font-size:0.92rem;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Funciones del modal inline — garantizan disponibilidad inmediata
    function abrirModalConsultorio() {
        var overlay = document.getElementById('modal-consultorio-overlay');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalConsultorio() {
        var overlay = document.getElementById('modal-consultorio-overlay');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('modal-logo-preview');
                if (preview) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        var img = document.createElement('img');
                        img.id = 'modal-logo-preview';
                        img.src = e.target.result;
                        img.style.cssText = 'max-height:80px; max-width:180px; object-fit:contain; border:2px solid #e2e8f0; border-radius:10px; padding:6px;';
                        preview.parentNode.replaceChild(img, preview);
                    }
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    // Cerrar al hacer clic fuera del panel
    document.getElementById('modal-consultorio-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalConsultorio();
    });
    // Abrir automáticamente si viene ?consultorio=ok
    if (new URLSearchParams(window.location.search).get('consultorio') === 'ok') {
        abrirModalConsultorio();
    }
    </script>
</body>
</html>