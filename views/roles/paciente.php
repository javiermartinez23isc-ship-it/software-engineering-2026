<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

// Guard de rol: solo el paciente (tipo 3) puede acceder
if ($_SESSION['id_tipo_usuario'] != 3) {
    header("location: ../auth/acceso_denegado.php");
    exit();
}

// Impedir que el navegador guarde esta página en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

$id_user = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Usuario";

// --- 1. LÓGICA DE VALIDACIÓN DE CITA ---
$check_citas = mysqli_query($conexion, "SELECT c.id_cita FROM cita c 
                                        WHERE c.id_usuario = '$id_user' 
                                        AND c.id_estado_cita IN (1, 4)");
$tiene_cita = (mysqli_num_rows($check_citas) > 0);

// --- 2. CALENDARIO DINÁMICO (semana actual + siguiente, Lun-Vie, 9am-7pm) ---
$hoy        = date('Y-m-d');
$hoy_ts     = strtotime($hoy);
$dia_semana = (int)date('N'); // 1=Lun … 7=Dom

// Inicio de la semana actual (lunes)
$lunes_ts   = $hoy_ts - (($dia_semana - 1) * 86400);

// Generar 2 semanas de fechas Lun-Vie
$semanas = [];
for ($s = 0; $s < 2; $s++) {
    $semana = [];
    for ($d = 0; $d < 5; $d++) {
        $ts   = $lunes_ts + ($s * 7 + $d) * 86400;
        $fecha = date('Y-m-d', $ts);
        $semana[] = $fecha;
    }
    $semanas[] = $semana;
}

// Horas disponibles: 09:00 a 19:00 cada hora
$horas_disponibles = [];
for ($h = 9; $h < 19; $h++) {
    $horas_disponibles[] = sprintf('%02d:00:00', $h);
}

// Obtener slots ya ocupados en la BD para el rango de fechas
$fecha_inicio_rango = $semanas[0][0];
$fecha_fin_rango    = $semanas[1][4];
$res_ocupados = mysqli_query($conexion,
    "SELECT h.fecha, h.hora_inicio, h.id_horario, h.estado, h.disponible
     FROM horario h
     WHERE h.fecha BETWEEN '$fecha_inicio_rango' AND '$fecha_fin_rango'");
$slots_bd = []; // clave: "fecha|hora" => ['id'=>..., 'estado'=>..., 'disponible'=>...]
if ($res_ocupados) {
    while ($row = mysqli_fetch_assoc($res_ocupados)) {
        $key = $row['fecha'] . '|' . $row['hora_inicio'];
        $slots_bd[$key] = ['id' => $row['id_horario'], 'estado' => $row['estado'], 'disponible' => $row['disponible']];
    }
}

/**
 * Renderiza una celda del calendario.
 * - Día pasado → bloqueada (gris)
 * - Fin de semana → bloqueada
 * - Slot ocupado en BD (cita o bloqueado por doctor) → bloqueada (🔒)
 * - Disponible → verde, clickeable (guarda fecha+hora como data)
 */
function renderCeldaPaciente($fecha, $hora, $hoy, $slots_bd) {
    if ($fecha < $hoy) {
        return '<td class="cell-locked" title="Día pasado">—</td>';
    }
    $key = $fecha . '|' . $hora;
    if (isset($slots_bd[$key])) {
        $slot = $slots_bd[$key];
        // Bloqueado por doctor (disponible=0) o con cita activa (estado=ocupado)
        if ($slot['estado'] === 'ocupado' || (int)$slot['disponible'] === 0) {
            return '<td class="cell-locked" title="No disponible">🔒</td>';
        }
        // Existe en BD y está disponible → usar su id_horario
        return '<td class="cell-free" data-id="' . $slot['id'] . '" data-fecha="' . $fecha . '" data-hora="' . $hora . '" onclick="seleccionarCelda(this)"></td>';
    }
    // No existe en BD → slot libre virtual (se creará al agendar)
    return '<td class="cell-free" data-id="" data-fecha="' . $fecha . '" data-hora="' . $hora . '" onclick="seleccionarCelda(this)"></td>';
}
$query_historial = "SELECT fecha_consulta, motivo, diagnostico, tratamiento 
                    FROM historial_medico 
                    WHERE id_usuario = '$id_user' 
                    ORDER BY fecha_consulta DESC";
$res_historial = mysqli_query($conexion, $query_historial);
$tiene_historial = (mysqli_num_rows($res_historial) > 0);

// --- 4. DATOS DEL PERFIL DEL PACIENTE ---
$query_perfil_pac = "SELECT nombre, apellido_paterno, apellido_materno, telefono, correo, foto_perfil 
                     FROM usuario WHERE id_usuario = '$id_user'";
$res_perfil_pac = mysqli_query($conexion, $query_perfil_pac);
$datos_perfil_pac = $res_perfil_pac ? mysqli_fetch_assoc($res_perfil_pac) : [];
if (!isset($datos_perfil_pac['foto_perfil'])) $datos_perfil_pac['foto_perfil'] = null;

// --- 5. DATOS DEL CONSULTORIO ---
$logo_consultorio_pac   = '';
$nombre_consultorio_pac = 'Consultorio';
$res_conf_pac = @mysqli_query($conexion, "SELECT clave, valor FROM configuracion_consultorio WHERE clave IN ('logo_consultorio','nombre_consultorio')");
if ($res_conf_pac) {
    while ($row_cp = mysqli_fetch_assoc($res_conf_pac)) {
        if ($row_cp['clave'] === 'logo_consultorio')   $logo_consultorio_pac   = $row_cp['valor'];
        if ($row_cp['clave'] === 'nombre_consultorio') $nombre_consultorio_pac = $row_cp['valor'];
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
    <link rel="stylesheet" href="../../public/assets/css/paciente.css?v=3">
</head>
<body>

<div class="layout">
    <nav class="navbar-vital">
        <div class="nav-left">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Menú">☰</button>
            <div class="logo-container"><img src="../../public/assets/img/logo_agenda_vital.png" class="logo-img"></div>
            <span class="brand-name">Agenda Vital</span>
        </div>
        <div class="nav-right">
            <a href="../../src/auth/logout.php" class="logout-link">Cerrar Sesión</a>
            <div class="user-profile-nav">👤 <?php echo $nombre_usuario; ?></div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
        <nav class="sidebar" id="sidebar">
            <div class="profile-circle">
                <?php if (!empty($logo_consultorio_pac)): ?>
                    <img src="../../public/<?php echo htmlspecialchars($logo_consultorio_pac); ?>" 
                         alt="Logo del consultorio">
                <?php else: ?>
                    <span style="font-size:1.6rem; display:flex; align-items:center; justify-content:center; width:100%; height:100%;">🏥</span>
                <?php endif; ?>
            </div>
            <p style="font-weight:700; text-align:center;"><?php echo htmlspecialchars($nombre_consultorio_pac); ?></p>
            <div class="sidebar-menu">
                <a onclick="ver('perfil')" id="m-perfil" class="active">👤 Mi Perfil</a>
                <a onclick="ver('agendar')" id="m-agendar">📅 Agendar Cita</a>
                <a onclick="ver('historial')" id="m-historial">📁 Mi Historial</a>
                <a onclick="ver('configuracion')" id="m-configuracion">⚙️ Configuración</a>
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
                            <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="../../src/appointments/cancelar_cita.php?id=<?php echo $cita_data['id_cita']; ?>&horario=<?php echo $cita_data['id_horario']; ?>" 
                                   style="background: #ef4444; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: 600;" 
                                   onclick="return confirm('¿Estás seguro de que deseas cancelar tu cita?')">❌ Cancelar Cita</a>
                                <button type="button"
                                        onclick="abrirReprogramar(<?php echo $cita_data['id_cita']; ?>, <?php echo $cita_data['id_horario']; ?>, '<?php echo $cita_data['fecha']; ?>', '<?php echo $cita_data['hora_inicio']; ?>')"
                                        style="background: #0ea5e9; color: white; padding: 10px 15px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;">
                                    🔄 Reprogramar Cita
                                </button>
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
                        <?php
                        $dias_label = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
                        ?>
                        <p style="font-size:.82rem; color:#64748b; margin-bottom:12px;">
                            Selecciona un horario disponible (🟢). Los días anteriores y horarios ocupados (🔒) no se pueden seleccionar.
                        </p>

                        <form action="../../src/appointments/agendar_cita.php" method="POST" id="form-agendar" onsubmit="enviarCita(event)">

                        <?php foreach ($semanas as $idx_s => $semana): ?>
                        <p style="font-weight:700; color:#475569; font-size:.85rem; margin: 14px 0 6px;">
                            Semana <?php echo $idx_s === 0 ? 'actual' : 'siguiente'; ?>
                            (<?php echo date('d/m', strtotime($semana[0])); ?> – <?php echo date('d/m', strtotime($semana[4])); ?>)
                        </p>
                        <div class="schedule-table-wrapper" style="overflow-x:auto; max-height:320px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:8px;">
                            <table class="schedule-table" style="min-width:480px;">
                                <thead>
                                    <tr>
                                        <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;">Hora</th>
                                        <?php foreach ($semana as $i => $fecha): ?>
                                        <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;text-align:center;">
                                            <?php echo $dias_label[$i]; ?><br>
                                            <span style="font-weight:400;font-size:.75rem;"><?php echo date('d/m', strtotime($fecha)); ?></span>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horas_disponibles as $hora): ?>
                                    <tr>
                                        <td class="cell-time"><?php echo date('g:i a', strtotime($hora)); ?></td>
                                        <?php foreach ($semana as $fecha): ?>
                                            <?php echo renderCeldaPaciente($fecha, $hora, $hoy, $slots_bd); ?>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endforeach; ?>

                            <p id="seleccion-info" style="margin:12px 0 8px; font-size:.88rem; color:#64748b;">Ningún horario seleccionado.</p>
                            <button type="submit" class="btn-submit" id="btn-submit-cita" disabled>Confirmar cita</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div id="v-historial" class="seccion">
                <h1>Mi Historial Médico</h1>
                <div class="card">
                    <p>Consulta el registro de tus consultas y tratamientos anteriores.</p>
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                    
                    <?php if ($tiene_historial): ?>
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #475569; color: white;">
                                    <th style="padding: 12px; border: 1px solid #cbd5e1;">Fecha</th>
                                    <th style="padding: 12px; border: 1px solid #cbd5e1;">Motivo</th>
                                    <th style="padding: 12px; border: 1px solid #cbd5e1;">Diagnóstico</th>
                                    <th style="padding: 12px; border: 1px solid #cbd5e1;">Tratamiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($h = mysqli_fetch_assoc($res_historial)): ?>
                                    <tr>
                                        <td style="padding: 12px; border: 1px solid #cbd5e1; font-weight: 600;"><?php echo date("d/M/Y", strtotime($h['fecha_consulta'])); ?></td>
                                        <td style="padding: 12px; border: 1px solid #cbd5e1;"><?php echo htmlspecialchars($h['motivo']); ?></td>
                                        <td style="padding: 12px; border: 1px solid #cbd5e1;"><?php echo htmlspecialchars($h['diagnostico']); ?></td>
                                        <td style="padding: 12px; border: 1px solid #cbd5e1;"><?php echo htmlspecialchars($h['treatment'] ?? $h['tratamiento']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #64748b;">
                            <p style="font-size: 3rem; margin: 0;">📁</p>
                            <p>No cuentas con consultas previas registradas en tu historial.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="v-configuracion" class="seccion">
                <h1>⚙️ Configuración de Perfil</h1>
                <div class="card">

                    <?php if (isset($_GET['perfil']) && $_GET['perfil'] === 'ok'): ?>
                        <div style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:12px 15px; border-radius:10px; margin-bottom:20px;">
                            ✅ Perfil actualizado correctamente.
                        </div>
                    <?php endif; ?>

                    <!-- Foto de perfil actual -->
                    <div style="text-align:center; margin-bottom:25px;">
                        <?php
                        $foto_pac = !empty($datos_perfil_pac['foto_perfil'])
                            ? '../../public/' . htmlspecialchars($datos_perfil_pac['foto_perfil'])
                            : '../../public/assets/img/imagen_persona_sin.png';
                        ?>
                        <img src="<?php echo $foto_pac; ?>" alt="Foto de perfil"
                             style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:4px solid var(--nava-cian);">
                        <p style="font-size:0.8rem; color:#64748b; margin-top:8px;">Tu foto de perfil</p>
                    </div>

                    <!-- Datos de solo lectura -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:15px; margin-bottom:20px;">
                        <p style="font-size:0.8rem; color:#64748b; margin:0 0 10px 0;">ℹ️ Los siguientes datos solo pueden ser modificados por el personal del consultorio.</p>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:3px;">Nombre completo</label>
                                <p style="margin:0; font-weight:600; color:#475569;"><?php echo htmlspecialchars(($datos_perfil_pac['nombre'] ?? '') . ' ' . ($datos_perfil_pac['apellido_paterno'] ?? '') . ' ' . ($datos_perfil_pac['apellido_materno'] ?? '')); ?></p>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:3px;">Correo electrónico</label>
                                <p style="margin:0; font-weight:600; color:#475569;"><?php echo htmlspecialchars($datos_perfil_pac['correo'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="../../src/profile/actualizar_perfil.php" enctype="multipart/form-data">

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:700; margin-bottom:6px; color:#475569;">Teléfono</label>
                            <input type="tel" name="telefono"
                                   value="<?php echo htmlspecialchars($datos_perfil_pac['telefono'] ?? ''); ?>"
                                   placeholder="Ej. 8711234567"
                                   pattern="[\d\s\+\-\(\)]{7,15}"
                                   title="Solo números, espacios, +, - y paréntesis"
                                   style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:10px; box-sizing:border-box; font-size:1rem;">
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-weight:700; margin-bottom:6px; color:#475569;">Foto de Perfil</label>
                            <input type="file" name="foto_perfil" accept="image/*"
                                   style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:10px; box-sizing:border-box; background:white; font-size:0.95rem;">
                            <small style="color:#64748b; display:block; margin-top:4px;">JPG, PNG, GIF o WEBP. Máximo 2 MB.</small>
                        </div>

                        <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                        <p style="font-weight:700; color:#475569; margin-bottom:10px;">
                            Cambiar Contraseña
                            <span style="font-weight:400; font-size:0.85rem;">(dejar en blanco para no cambiar)</span>
                        </p>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:6px; color:#475569;">Nueva Contraseña</label>
                                <input type="password" name="nueva_password" placeholder="Mínimo 6 caracteres"
                                       style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:10px; box-sizing:border-box; font-size:1rem;">
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:6px; color:#475569;">Confirmar Contraseña</label>
                                <input type="password" name="confirmar_password" placeholder="Repite la contraseña"
                                       style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:10px; box-sizing:border-box; font-size:1rem;">
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" style="width:auto; padding:14px 35px;">
                            💾 Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<!-- ══════════════════════════════════════════════════════════
     MODAL DE REPROGRAMACIÓN
     ══════════════════════════════════════════════════════════ -->
<div id="modal-reprogramar-overlay" class="repro-overlay" style="display:none;" onclick="cerrarReprogramar()"></div>
<div id="modal-reprogramar" class="repro-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="repro-titulo">
    <div class="repro-header">
        <h2 id="repro-titulo">🔄 Reprogramar Cita</h2>
        <button class="repro-close" onclick="cerrarReprogramar()" aria-label="Cerrar">✕</button>
    </div>

    <div class="repro-aviso">
        <strong>Cita actual:</strong>
        <span id="repro-cita-actual">—</span>
    </div>
    <p class="repro-nota">⚠️ Al confirmar, la cita actual se cancelará y se registrará la nueva.</p>
    <p class="repro-nota" style="color:#ef4444; font-weight:600;" id="repro-nota-bloqueado">
        🔒 El horario anterior quedará bloqueado y no podrá volver a seleccionarse.
    </p>

    <form action="../../src/appointments/reprogramar_cita.php" method="POST"
          id="form-reprogramar" onsubmit="enviarReprogramar(event)">
        <input type="hidden" name="id_cita_vieja"    id="inp-cita-vieja">
        <input type="hidden" name="id_horario_viejo" id="inp-horario-viejo">
        <!-- Los tres campos del nuevo slot se inyectan por JS al enviar -->

        <p style="font-weight:700; color:#475569; font-size:.85rem; margin:14px 0 6px;">
            Selecciona el nuevo horario:
        </p>

        <?php
        // Reutilizamos las mismas semanas y horas ya calculadas arriba
        // pero pasamos la fecha|hora de la cita actual para bloquearla
        // (se inyecta como data-attr en el contenedor y JS la usa)
        ?>
        <div id="repro-calendario"
             data-fecha-bloqueada=""
             data-hora-bloqueada="">
            <?php foreach ($semanas as $idx_s => $semana): ?>
            <p style="font-weight:700; color:#475569; font-size:.82rem; margin:10px 0 4px;">
                Semana <?php echo $idx_s === 0 ? 'actual' : 'siguiente'; ?>
                (<?php echo date('d/m', strtotime($semana[0])); ?> – <?php echo date('d/m', strtotime($semana[4])); ?>)
            </p>
            <div style="overflow-x:auto; max-height:260px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px;">
                <table class="schedule-table" style="min-width:460px;">
                    <thead>
                        <tr>
                            <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;">Hora</th>
                            <?php foreach ($semana as $i => $fecha): ?>
                            <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;text-align:center;">
                                <?php echo ['Lun','Mar','Mié','Jue','Vie'][$i]; ?><br>
                                <span style="font-weight:400;font-size:.72rem;"><?php echo date('d/m', strtotime($fecha)); ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas_disponibles as $hora): ?>
                        <tr>
                            <td class="cell-time"><?php echo date('g:i a', strtotime($hora)); ?></td>
                            <?php foreach ($semana as $fecha): ?>
                                <?php
                                // Renderizamos igual que el calendario normal;
                                // el slot de la cita actual se marcará como bloqueado por JS
                                echo renderCeldaPaciente($fecha, $hora, $hoy, $slots_bd);
                                ?>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>

        <p id="repro-seleccion-info" style="margin:10px 0 6px; font-size:.88rem; color:#64748b;">
            Ningún horario seleccionado.
        </p>
        <button type="submit" class="btn-submit" id="btn-repro-submit" disabled
                style="margin-top:8px;">
            ✅ Confirmar Reprogramación
        </button>
    </form>
</div>

<script src="../../public/assets/js/paciente.js?v=3"></script>
</body>
</html>