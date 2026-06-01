<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Error: No se especificó un paciente.'); window.history.back();</script>";
    exit();
}

$id_paciente = mysqli_real_escape_string($conexion, $_GET['id']);
$es_doctor   = ($_SESSION['id_tipo_usuario'] == 1);

// Datos del paciente
$res_paciente   = mysqli_query($conexion, "SELECT nombre, apellido_paterno, apellido_materno, telefono, correo 
                                           FROM usuario 
                                           WHERE id_usuario = '$id_paciente' AND id_tipo_usuario = 3");
$datos_paciente = mysqli_fetch_assoc($res_paciente);

if (!$datos_paciente) {
    echo "<script>alert('Error: Paciente no encontrado.'); window.history.back();</script>";
    exit();
}

// Historial — incluye id_historial para editar/eliminar
$res_historial  = mysqli_query($conexion, "SELECT id_historial, fecha_consulta, motivo, diagnostico, tratamiento 
                                           FROM historial_medico 
                                           WHERE id_usuario = '$id_paciente' 
                                           ORDER BY fecha_consulta DESC");
$tiene_historial = (mysqli_num_rows($res_historial) > 0);
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico - Agenda Vital</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 24px 16px; color: #333; }

        .contenedor { max-width: 1060px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,.1); }

        /* ── Botón regresar ── */
        .btn-regresar { display: inline-block; background: #6c757d; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 6px; margin-bottom: 22px; font-size: .88rem; }
        .btn-regresar:hover { background: #5a6268; }

        /* ── Header paciente ── */
        .paciente-header { background: #e9f2f9; padding: 16px 20px; border-left: 5px solid #0056b3; border-radius: 8px; margin-bottom: 28px; }
        .paciente-header h2 { color: #0056b3; font-size: 1.2rem; margin-bottom: 4px; }
        .paciente-header p  { font-size: .88rem; color: #444; margin: 2px 0; }

        /* ── Alertas ── */
        .alerta-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alerta-exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }

        /* ── Formulario nueva consulta ── */
        .form-nueva-consulta { border: 1px solid #c8dff0; padding: 22px; border-radius: 10px; background: #f0f7ff; margin-bottom: 30px; }
        .form-nueva-consulta h3 { color: #0056b3; margin-bottom: 18px; font-size: 1rem; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: 5px; color: #475569; }
        .form-group input,
        .form-group textarea { width: 100%; padding: 9px 11px; border: 1px solid #ccc; border-radius: 6px; font-size: .9rem; font-family: inherit; }
        .form-group textarea { min-height: 80px; resize: vertical; }

        /* ── Botones genéricos ── */
        .btn-guardar  { background: #28a745; color: #fff; padding: 10px 22px; border-radius: 6px; border: none; cursor: pointer; font-size: .9rem; font-weight: 600; }
        .btn-guardar:hover  { background: #218838; }
        .btn-editar   { background: #0ea5e9; color: #fff; padding: 5px 12px; border-radius: 16px; border: none; cursor: pointer; font-size: .78rem; font-weight: 700; white-space: nowrap; }
        .btn-editar:hover   { background: #0284c7; }
        .btn-eliminar { background: #fee2e2; color: #dc2626; padding: 5px 12px; border-radius: 16px; border: none; cursor: pointer; font-size: .78rem; font-weight: 700; white-space: nowrap; }
        .btn-eliminar:hover { background: #fecaca; }

        /* ── Tabla ── */
        .table-wrap { overflow-x: auto; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; min-width: 600px; }
        th { text-align: left; padding: 10px 10px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: .78rem; white-space: nowrap; background: #f8fafc; }
        td { padding: 11px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:hover td { background: #f8fafc; }
        .td-acciones { display: flex; gap: 6px; align-items: center; }

        .estado-vacio { text-align: center; padding: 40px; color: #888; }

        /* ── Modal de edición ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; overflow-y: auto; padding: 20px; }
        .modal-overlay.open { display: flex; align-items: flex-start; justify-content: center; }
        .modal-box { background: #fff; border-radius: 14px; padding: 28px; width: min(560px, 96vw); margin: auto; box-shadow: 0 10px 30px rgba(0,0,0,.2); position: relative; }
        .modal-box h3 { color: #0056b3; margin-bottom: 20px; font-size: 1.05rem; }
        .modal-close { position: absolute; top: 12px; right: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; line-height: 1; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .btn-cancelar-modal { background: #e2e8f0; color: #475569; padding: 9px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: .9rem; }
    </style>
</head>
<body>
<div class="contenedor">

    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error">
            ❌ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['ok'])): ?>
        <div class="alerta-exito">
            ✅ <?php
                $ok = $_GET['ok'];
                if ($ok === 'guardado')   echo 'Consulta guardada correctamente.';
                elseif ($ok === 'editado')   echo 'Registro actualizado correctamente.';
                elseif ($ok === 'eliminado') echo 'Registro eliminado correctamente.';
            ?>
        </div>
    <?php endif; ?>

    <?php $ruta_regreso = $es_doctor ? 'doctor.php' : 'asistente.php'; ?>
    <a href="<?php echo $ruta_regreso; ?>" class="btn-regresar">← Volver al Panel</a>

    <div class="paciente-header">
        <h2><?php echo htmlspecialchars($datos_paciente['nombre'] . ' ' . $datos_paciente['apellido_paterno'] . ' ' . $datos_paciente['apellido_materno']); ?></h2>
        <p><strong>Correo:</strong> <?php echo htmlspecialchars($datos_paciente['correo']); ?></p>
        <p><strong>Teléfono:</strong> <?php echo !empty($datos_paciente['telefono']) ? htmlspecialchars($datos_paciente['telefono']) : 'No registrado'; ?></p>
    </div>

    <!-- ── Tabla de registros ── -->
    <h3 style="margin-bottom:14px; color:#1a237e;">Registro de Consultas</h3>

    <?php if ($tiene_historial): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:110px;">Fecha</th>
                    <th>Motivo</th>
                    <th>Diagnóstico</th>
                    <th>Tratamiento</th>
                    <?php if ($es_doctor): ?><th style="width:120px;">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($res_historial)): ?>
                <tr>
                    <td><?php echo date("d/M/Y", strtotime($fila['fecha_consulta'])); ?></td>
                    <td><?php echo htmlspecialchars($fila['motivo']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($fila['diagnostico'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($fila['tratamiento'])); ?></td>
                    <?php if ($es_doctor): ?>
                    <td>
                        <div class="td-acciones">
                            <button class="btn-editar"
                                data-id="<?php echo $fila['id_historial']; ?>"
                                data-fecha="<?php echo htmlspecialchars($fila['fecha_consulta']); ?>"
                                data-motivo="<?php echo htmlspecialchars($fila['motivo'], ENT_QUOTES); ?>"
                                data-diagnostico="<?php echo htmlspecialchars($fila['diagnostico'], ENT_QUOTES); ?>"
                                data-tratamiento="<?php echo htmlspecialchars($fila['tratamiento'], ENT_QUOTES); ?>">
                                ✏️ Editar
                            </button>
                            <form method="POST" action="../../src/historial/eliminar_historial.php"
                                  onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="id_historial" value="<?php echo $fila['id_historial']; ?>">
                                <input type="hidden" name="id_paciente"  value="<?php echo $id_paciente; ?>">
                                <button type="submit" class="btn-eliminar">🗑️ Eliminar</button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="estado-vacio">
            <p style="font-size:2.5rem;">📁</p>
            <p>Este paciente no cuenta con consultas previas registradas.</p>
        </div>
    <?php endif; ?>

</div>

<?php if ($es_doctor): ?>
<!-- ── Modal de edición ── -->
<div class="modal-overlay" id="modal-editar">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarEditar()">×</button>
        <h3>✏️ Editar Registro de Consulta</h3>
        <form method="POST" action="../../src/historial/editar_historial.php">
            <input type="hidden" name="id_historial" id="edit-id">
            <input type="hidden" name="id_paciente"  value="<?php echo $id_paciente; ?>">
            <div class="form-group">
                <label>Fecha de Consulta</label>
                <input type="date" name="fecha_consulta" id="edit-fecha"
                       max="<?php echo $hoy; ?>" required>
            </div>
            <div class="form-group">
                <label>Motivo de Consulta</label>
                <input type="text" name="motivo" id="edit-motivo" maxlength="255" required>
            </div>
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" id="edit-diagnostico" required></textarea>
            </div>
            <div class="form-group">
                <label>Tratamiento</label>
                <textarea name="tratamiento" id="edit-tratamiento" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancelar-modal" onclick="cerrarEditar()">Cancelar</button>
                <button type="submit" class="btn-guardar">💾 Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirEditar(id, fecha, motivo, diagnostico, tratamiento) {
    document.getElementById('edit-id').value          = id;
    document.getElementById('edit-fecha').value       = fecha;
    document.getElementById('edit-motivo').value      = motivo;
    document.getElementById('edit-diagnostico').value = diagnostico;
    document.getElementById('edit-tratamiento').value = tratamiento;
    document.getElementById('modal-editar').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function cerrarEditar() {
    document.getElementById('modal-editar').classList.remove('open');
    document.body.style.overflow = '';
}

// Event delegation: captura clics en cualquier .btn-editar de la tabla
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-editar');
    if (btn) {
        abrirEditar(
            btn.getAttribute('data-id'),
            btn.getAttribute('data-fecha'),
            btn.getAttribute('data-motivo'),
            btn.getAttribute('data-diagnostico'),
            btn.getAttribute('data-tratamiento')
        );
    }
});

// Cerrar modal al hacer clic en el overlay (fuera del box)
document.getElementById('modal-editar').addEventListener('click', function(e) {
    if (e.target === this) cerrarEditar();
});
</script>
<?php endif; ?>

</body>
</html>
