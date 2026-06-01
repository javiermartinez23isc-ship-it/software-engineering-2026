/* login.js */

// Si el navegador restaura esta página desde el bfcache (botón Atrás/Adelante),
// forzar una recarga real para que PHP destruya la sesión.
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        window.location.reload(true);
    }
});

/* ── Navegación entre vistas ── */
function mostrarRecuperacion(event) {
    if (event) event.preventDefault();
    document.getElementById('view-login').style.display    = 'none';
    document.getElementById('view-recovery').style.display = 'block';
    // Resetear el flujo al abrir
    resetRecovery();
}

function mostrarLogin(event) {
    if (event) event.preventDefault();
    document.getElementById('view-recovery').style.display = 'none';
    document.getElementById('view-login').style.display    = 'block';
    resetRecovery();
}

function resetRecovery() {
    // Volver al paso 1
    document.getElementById('recovery-step1').style.display = 'block';
    document.getElementById('recovery-step2').style.display = 'none';
    // Limpiar campos
    const campos = ['recoveryNombre', 'recoveryCorreo', 'newPassword', 'confirmPassword', 'recovery-user-id'];
    campos.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    // Ocultar errores
    ocultarError('recovery-error');
    ocultarError('recovery-error2');
}

/* ── Helpers de error ── */
function mostrarError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
}
function ocultarError(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

/* ── Paso 1: Verificar identidad ── */
function verificarIdentidad() {
    const nombre = document.getElementById('recoveryNombre').value.trim();
    const correo = document.getElementById('recoveryCorreo').value.trim();
    ocultarError('recovery-error');

    if (!nombre) { mostrarError('recovery-error', 'Ingresa tu nombre completo.'); return; }
    if (!correo || !correo.includes('@')) { mostrarError('recovery-error', 'Ingresa un correo válido.'); return; }

    const btn = document.getElementById('btn-verificar');
    btn.disabled    = true;
    btn.textContent = 'Verificando...';

    const formData = new FormData();
    formData.append('accion', 'verificar');
    formData.append('nombre', nombre);
    formData.append('correo', correo);

    fetch('../../src/auth/recuperar_password.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled    = false;
            btn.textContent = 'Verificar Identidad';
            if (data.ok) {
                document.getElementById('recovery-user-id').value = data.id_usuario;
                document.getElementById('recovery-step1').style.display = 'none';
                document.getElementById('recovery-step2').style.display = 'block';
            } else {
                mostrarError('recovery-error', data.msg);
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Verificar Identidad';
            mostrarError('recovery-error', 'Error de conexión. Intenta de nuevo.');
        });
}

/* ── Paso 2: Guardar nueva contraseña ── */
function guardarNuevaPassword() {
    const id       = document.getElementById('recovery-user-id').value;
    const nueva    = document.getElementById('newPassword').value;
    const confirma = document.getElementById('confirmPassword').value;
    ocultarError('recovery-error2');

    if (nueva.length < 6) { mostrarError('recovery-error2', 'La contraseña debe tener al menos 6 caracteres.'); return; }
    if (nueva !== confirma) { mostrarError('recovery-error2', 'Las contraseñas no coinciden.'); return; }

    const formData = new FormData();
    formData.append('accion',          'cambiar');
    formData.append('id_usuario',      id);
    formData.append('nueva_password',  nueva);

    fetch('../../src/auth/recuperar_password.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                alert('✅ Contraseña actualizada correctamente. Ahora puedes iniciar sesión.');
                mostrarLogin(null);
            } else {
                mostrarError('recovery-error2', data.msg);
            }
        })
        .catch(() => {
            mostrarError('recovery-error2', 'Error de conexión. Intenta de nuevo.');
        });
}

/* ── Enter en campos de recuperación ── */
document.addEventListener('DOMContentLoaded', function () {
    ['recoveryNombre', 'recoveryCorreo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keydown', e => { if (e.key === 'Enter') verificarIdentidad(); });
    });
    ['newPassword', 'confirmPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keydown', e => { if (e.key === 'Enter') guardarNuevaPassword(); });
    });
});
