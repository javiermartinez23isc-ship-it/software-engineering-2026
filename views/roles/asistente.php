<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

// Guard de rol: solo el asistente (tipo 2) puede acceder
if ($_SESSION['id_tipo_usuario'] != 2) {
    header("location: ../auth/acceso_denegado.php");
    exit();
}

// Impedir que el navegador guarde esta página en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

$id_asis = $_SESSION['usuario_id'];
$nombre_asistente = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : "Usuario";
$hoy = date('Y-m-d');

// 1. Citas registradas — solo activas (1,2,4)
$query_citas = "SELECT c.id_cita, c.id_horario, u.id_usuario, u.nombre as paciente, u.apellido_paterno, h.fecha, h.hora_inicio, e.estado as nombre_estado 
                FROM cita c 
                JOIN usuario u ON c.id_usuario = u.id_usuario 
                JOIN horario h ON c.id_horario = h.id_horario 
                JOIN estado_cita e ON c.id_estado_cita = e.id_estado_cita 
                WHERE c.id_estado_cita IN (1, 2, 4)
                AND h.fecha >= CURDATE()
                ORDER BY 
                    FIELD(c.id_estado_cita, 1, 2, 4),
                    h.fecha ASC, h.hora_inicio ASC";
$res_citas = mysqli_query($conexion, $query_citas);

// 2. Pacientes para el select
$query_pacientes = "SELECT id_usuario, nombre, apellido_paterno FROM usuario WHERE id_tipo_usuario = 3 ORDER BY nombre ASC";
$res_pacientes = mysqli_query($conexion, $query_pacientes);

// 3. CALENDARIO DINÁMICO (semana actual + siguiente, Lun-Vie, 9am-7pm)
$hoy        = date('Y-m-d');
$hoy_ts     = strtotime($hoy);
$dia_semana = (int)date('N'); // 1=Lun … 7=Dom
$lunes_ts   = $hoy_ts - (($dia_semana - 1) * 86400);

$semanas_asis = [];
for ($s = 0; $s < 2; $s++) {
    $sem = [];
    for ($d = 0; $d < 5; $d++) {
        $sem[] = date('Y-m-d', $lunes_ts + ($s * 7 + $d) * 86400);
    }
    $semanas_asis[] = $sem;
}

$horas_asis = [];
for ($h = 9; $h < 19; $h++) {
    $horas_asis[] = sprintf('%02d:00:00', $h);
}

// Slots ocupados en BD para el rango
$f_ini = $semanas_asis[0][0];
$f_fin = $semanas_asis[1][4];
$res_ocu_asis = mysqli_query($conexion,
    "SELECT fecha, hora_inicio, id_horario, estado, disponible FROM horario
     WHERE fecha BETWEEN '$f_ini' AND '$f_fin'");
$slots_asis = [];
if ($res_ocu_asis) {
    while ($r = mysqli_fetch_assoc($res_ocu_asis)) {
        $slots_asis[$r['fecha'] . '|' . $r['hora_inicio']] = [
            'id'         => $r['id_horario'],
            'estado'     => $r['estado'],
            'disponible' => $r['disponible']
        ];
    }
}

function renderCeldaAsistente($fecha, $hora, $hoy, $slots) {
    // Bloquear días pasados
    if ($fecha < $hoy) {
        return '<td class="cell-locked" title="Día pasado">—</td>';
    }
    // Bloquear horas pasadas del día actual
    if ($fecha === $hoy && $hora <= date('H:i:s')) {
        return '<td class="cell-locked" title="Hora pasada">—</td>';
    }
    $key = $fecha . '|' . $hora;
    if (isset($slots[$key])) {
        $s = $slots[$key];
        if ($s['estado'] === 'ocupado' || (int)($s['disponible'] ?? 1) === 0) {
            return '<td class="cell-locked" onclick="mostrarAlerta()" title="No disponible">🔒</td>';
        }
        return '<td class="cell-free" data-id="' . $s['id'] . '" data-fecha="' . $fecha . '" data-hora="' . $hora . '" onclick="seleccionarCelda(this)"></td>';
    }
    return '<td class="cell-free" data-id="" data-fecha="' . $fecha . '" data-hora="' . $hora . '" onclick="seleccionarCelda(this)"></td>';
}

// 4. Datos del perfil del asistente
$query_perfil_asis = "SELECT nombre, apellido_paterno, apellido_materno, telefono, correo, foto_perfil 
                      FROM usuario WHERE id_usuario = '$id_asis'";
$res_perfil_asis = mysqli_query($conexion, $query_perfil_asis);
$datos_perfil_asis = $res_perfil_asis ? mysqli_fetch_assoc($res_perfil_asis) : [];
if (!isset($datos_perfil_asis['foto_perfil'])) $datos_perfil_asis['foto_perfil'] = null;

// 5. Datos del consultorio
$logo_consultorio_asis   = '';
$nombre_consultorio_asis = 'Agenda Vital';
$res_conf_asis = @mysqli_query($conexion, "SELECT clave, valor FROM configuracion_consultorio WHERE clave IN ('logo_consultorio','nombre_consultorio')");
if ($res_conf_asis) {
    while ($row_c = mysqli_fetch_assoc($res_conf_asis)) {
        if ($row_c['clave'] === 'logo_consultorio')   $logo_consultorio_asis   = $row_c['valor'];
        if ($row_c['clave'] === 'nombre_consultorio') $nombre_consultorio_asis = $row_c['valor'];
    }
}

// (función renderCeldaAsistente ya definida arriba)
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
                <img src="../../public/assets/img/logo_agenda_vital.png" class="logo-img">
            </div>
            <span class="brand-name">Agenda Vital</span>
        </div>
        <div class="nav-right">
            <a href="../../src/auth/logout.php" class="logout-link">Cerrar Sesión</a>
            <div class="user-profile-nav">👤 <span><?php echo $nombre_asistente; ?></span></div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
        <nav class="sidebar" id="sidebar">
            <div class="profile-circle">
                <?php if (!empty($logo_consultorio_asis)): ?>
                    <img src="../../public/<?php echo htmlspecialchars($logo_consultorio_asis); ?>" 
                         alt="Logo del consultorio">
                <?php else: ?>
                    <span style="font-size:1.6rem; display:flex; align-items:center; justify-content:center; width:100%; height:100%;">🏥</span>
                <?php endif; ?>
            </div>
            <h3>Asistente</h3>
            <p class="sidebar-subtitle"><?php echo htmlspecialchars($nombre_consultorio_asis); ?></p>
            <hr class="sidebar-hr">
            <a onclick="ver('agenda')" id="m-agenda" class="menu-link active">📅 Agenda Dr. Nava</a>
            <a onclick="ver('nuevo')" id="m-nuevo" class="menu-link">🗓️ Registrar Cita</a>
            <a onclick="ver('registro')" id="m-registro" class="menu-link">👤 Registrar Paciente</a>
            <a onclick="ver('configuracion')" id="m-configuracion" class="menu-link">⚙️ Configuración</a>
        </nav>

        <main class="main">
            <div id="v-agenda" class="section active">
                <h1>Agenda del Dr. Nava</h1>
                <div class="card">
                    <h3>Citas Registradas</h3>
                    <div class="table-wrap">
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
                                        <div class="td-actions">
                                            <a href="historial.php?id=<?php echo $c['id_usuario']; ?>" class="status-pill" style="background: #0ea5e9; color: white; text-decoration: none; display: inline-block;">👁️ Historial</a>
                                            <a href="../../src/appointments/cancelar_cita.php?id=<?php echo $c['id_cita']; ?>&horario=<?php echo $c['id_horario']; ?>" 
                                               onclick="return confirm('¿Borrar esta cita?')" class="btn-delete">❌ Borrar</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No hay citas registradas en el sistema.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <button class="btn-primary-action" onclick="ver('nuevo')">+ Nueva Cita</button>
            </div>

            <div id="v-nuevo" class="section">
                <h1>🗓️ Nueva Cita para Dr. Nava</h1>
                <div class="card card-form-wide">
                    <form action="../../src/appointments/agendar_asistente.php" method="POST" id="form-nueva-cita" onsubmit="enviarCitaAsistente(event)">
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
                            <?php
                            $dias_label_asis = ['Lun','Mar','Mié','Jue','Vie'];
                            foreach ($semanas_asis as $idx_s => $semana_a):
                            ?>
                            <p style="font-weight:700; color:#475569; font-size:.82rem; margin:12px 0 5px;">
                                Semana <?php echo $idx_s === 0 ? 'actual' : 'siguiente'; ?>
                                (<?php echo date('d/m', strtotime($semana_a[0])); ?> – <?php echo date('d/m', strtotime($semana_a[4])); ?>)
                            </p>
                            <div style="overflow-x:auto; max-height:300px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:10px;">
                                <table class="schedule-table" style="min-width:460px;">
                                    <thead>
                                        <tr>
                                            <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;">Hora</th>
                                            <?php foreach ($semana_a as $i => $fecha_a): ?>
                                            <th style="position:sticky;top:0;z-index:2;background:#475569;color:#fff;text-align:center;">
                                                <?php echo $dias_label_asis[$i]; ?><br>
                                                <span style="font-weight:400;font-size:.72rem;"><?php echo date('d/m', strtotime($fecha_a)); ?></span>
                                            </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($horas_asis as $hora_a): ?>
                                        <tr>
                                            <td class="cell-time"><?php echo date('g:i a', strtotime($hora_a)); ?></td>
                                            <?php foreach ($semana_a as $fecha_a): ?>
                                                <?php echo renderCeldaAsistente($fecha_a, $hora_a, $hoy, $slots_asis); ?>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn-confirmar" id="btn-submit-cita" disabled>Confirmar Cita en Agenda</button>
                    </form>
                </div>
            </div>

            <div id="v-registro" class="section">
                <h1>👤 Crear Cuenta de Paciente</h1>
                <div class="card card-form-narrow">
                    <form action="../../src/patients/registrar_paciente_asis.php" method="POST">
                        <label>Nombre(s):</label>
                        <input type="text" name="nombre" required placeholder="Ej. María"
                               pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+"
                               title="Solo letras y espacios, sin números ni símbolos"
                               oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')">
                        
                        <div class="form-grid-2">
                            <div>
                                <label>Apellido Paterno:</label>
                                <input type="text" name="ap_paterno" required placeholder="López"
                                       pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+"
                                       title="Solo letras y espacios, sin números ni símbolos"
                                       oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')">
                            </div>
                            <div>
                                <label>Apellido Materno:</label>
                                <input type="text" name="ap_materno" placeholder="García"
                                       pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]*"
                                       title="Solo letras y espacios, sin números ni símbolos"
                                       oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div>
                                <label>Correo Electrónico:</label>
                                <input type="email" name="usuario" required placeholder="correo@ejemplo.com">
                            </div>
                            <div>
                                <label>Teléfono:</label>
                                <input type="tel" name="telefono" placeholder="Ej. 8711234567"
                                       pattern="[0-9]{7,15}"
                                       title="Solo números, entre 7 y 15 dígitos"
                                       oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                       maxlength="15">
                            </div>
                        </div>
                        
                        <hr class="form-hr">
                        <label>Contraseña Provisional:</label>
                        <input type="text" value="Nava2026*" readonly class="input-readonly">
                        
                        <button type="submit" class="btn-confirmar">Registrar Paciente</button>
                    </form>
                </div>
            </div>
            <div id="v-configuracion" class="section">
                <h1>⚙️ Configuración de Perfil</h1>
                <div class="card card-form-narrow">

                    <?php if (isset($_GET['perfil']) && $_GET['perfil'] === 'ok'): ?>
                        <div style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:12px 15px; border-radius:8px; margin-bottom:20px;">
                            ✅ Perfil actualizado correctamente.
                        </div>
                    <?php elseif (isset($_GET['perfil']) && $_GET['perfil'] === 'sin_cambios'): ?>
                        <div style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:12px 15px; border-radius:8px; margin-bottom:20px;">
                            ℹ️ No se realizaron cambios en el perfil.
                        </div>
                    <?php endif; ?>

                    <!-- Foto de perfil actual -->
                    <div style="text-align:center; margin-bottom:25px;">
                        <?php
                        $foto_asis = !empty($datos_perfil_asis['foto_perfil'])
                            ? '../../public/' . htmlspecialchars($datos_perfil_asis['foto_perfil'])
                            : '../../public/assets/img/imagen_persona_sin.png';
                        ?>
                        <img src="<?php echo $foto_asis; ?>" alt="Foto de perfil"
                             style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid var(--nava-cian);">
                        <p style="font-size:0.8rem; color:#64748b; margin-top:8px;">Foto de perfil actual</p>
                    </div>

                    <form method="POST" action="../../src/profile/actualizar_perfil.php" enctype="multipart/form-data">
                        <label>Nombre(s) *</label>
                        <input type="text" name="nombre" required
                               pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+"
                               title="Solo letras y espacios, sin números ni símbolos"
                               oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                               value="<?php echo htmlspecialchars($datos_perfil_asis['nombre'] ?? ''); ?>">

                        <div class="form-grid-2">
                            <div>
                                <label>Apellido Paterno</label>
                                <input type="text" name="apellido_paterno"
                                       pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]*"
                                       title="Solo letras y espacios, sin números ni símbolos"
                                       oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                                       value="<?php echo htmlspecialchars($datos_perfil_asis['apellido_paterno'] ?? ''); ?>">
                            </div>
                            <div>
                                <label>Apellido Materno</label>
                                <input type="text" name="apellido_materno"
                                       pattern="[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]*"
                                       title="Solo letras y espacios, sin números ni símbolos"
                                       oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
                                       value="<?php echo htmlspecialchars($datos_perfil_asis['apellido_materno'] ?? ''); ?>">
                            </div>
                        </div>

                        <label>Teléfono</label>
                        <input type="tel" name="telefono"
                               pattern="[0-9]{7,15}"
                               title="Solo números, entre 7 y 15 dígitos"
                               oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                               maxlength="15"
                               value="<?php echo htmlspecialchars($datos_perfil_asis['telefono'] ?? ''); ?>">

                        <label>Correo Electrónico *</label>
                        <input type="email" name="correo" required
                               pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                               title="Ingresa un correo válido con formato nombre@dominio.ext"
                               value="<?php echo htmlspecialchars($datos_perfil_asis['correo'] ?? ''); ?>">

                        <label>Foto de Perfil</label>
                        <input type="file" name="foto_perfil" accept="image/*"
                               style="padding:8px; background:white;">
                        <small style="color:#64748b; display:block; margin-top:4px;">JPG, PNG, GIF o WEBP. Máximo 2 MB.</small>
                        <?php if (!empty($datos_perfil_asis['foto_perfil'])): ?>
                        <button type="submit" name="quitar_foto" value="1"
                                onclick="return confirm('¿Estás seguro de que deseas quitar tu foto de perfil?')"
                                style="margin-top:10px; background:none; border:1px solid #ef4444; color:#ef4444; padding:7px 14px; border-radius:8px; font-size:0.83rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .18s;"
                                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                            🗑️ Quitar foto de perfil
                        </button>
                        <?php endif; ?>

                        <hr class="form-hr">
                        <label>Nueva Contraseña <span style="font-weight:normal; font-size:0.85rem;">(dejar en blanco para no cambiar)</span></label>
                        <input type="password" name="nueva_password" placeholder="Mínimo 6 caracteres">

                        <label>Confirmar Contraseña</label>
                        <input type="password" name="confirmar_password" placeholder="Repite la contraseña">

                        <button type="submit" class="btn-confirmar" style="margin-top:20px;">💾 Guardar Cambios</button>
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