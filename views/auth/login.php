<?php
// Al llegar al login, destruir cualquier sesión activa
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Impedir que el navegador guarde esta página en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Vital | Acceso</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- VINCULACIÓN DEL CSS EXTERNO -->
    <link rel="stylesheet" href="../../public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="brand-section"></div>
        <div class="form-section">
            <div class="brand-text-container">
                <h1>Agenda Vital</h1>
                <h2>Consultorio Nava</h2>
                <p>Gestión de citas médicas</p>
            </div>

            <!-- FORMULARIO DE LOGIN -->
            <form id="view-login" class="fade-view" action="../../src/auth/validar.php" method="POST">
                <p class="login-title">Bienvenido</p>
                <p class="login-subtitle">Ingresa tus credenciales de acceso.</p>
                
                <div class="input-group">
                    <label>Usuario (Correo)</label>
                    <input type="text" name="usuario" id="userInput" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <a href="#" class="forgot-password" onclick="mostrarRecuperacion(event)">¿Olvidaste tu contraseña?</a>

                <button type="submit" class="btn-login">Entrar al Sistema</button>
            </form>

            <!-- VISTA DE RECUPERACIÓN -->
            <div id="view-recovery" class="fade-view" style="display: none;">
                <p class="login-title">Recuperar Contraseña</p>
                <p class="login-subtitle">Ingresa tu nombre completo y correo para verificar tu identidad.</p>

                <!-- Paso 1: Verificar identidad -->
                <div id="recovery-step1">
                    <div class="input-group">
                        <label>Nombre Completo</label>
                        <input type="text" id="recoveryNombre" placeholder="Ej. Jorge Humberto Esquivel">
                    </div>
                    <div class="input-group">
                        <label>Correo Electrónico</label>
                        <input type="email" id="recoveryCorreo" placeholder="correo@ejemplo.com">
                    </div>
                    <div id="recovery-error" style="display:none; background:#fee2e2; color:#b91c1c; padding:10px 12px; border-radius:8px; font-size:.83rem; margin-bottom:14px;"></div>
                    <button class="btn-login" id="btn-verificar" onclick="verificarIdentidad()">Verificar Identidad</button>
                </div>

                <!-- Paso 2: Nueva contraseña (oculto hasta verificar) -->
                <div id="recovery-step2" style="display:none;">
                    <div style="background:#dcfce7; color:#166534; padding:10px 12px; border-radius:8px; font-size:.83rem; margin-bottom:16px;">
                        ✅ Identidad verificada. Ahora establece tu nueva contraseña.
                    </div>
                    <input type="hidden" id="recovery-user-id">
                    <div class="input-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" id="newPassword" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="input-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" id="confirmPassword" placeholder="Repite la contraseña">
                    </div>
                    <div id="recovery-error2" style="display:none; background:#fee2e2; color:#b91c1c; padding:10px 12px; border-radius:8px; font-size:.83rem; margin-bottom:14px;"></div>
                    <button class="btn-login" onclick="guardarNuevaPassword()">Guardar Nueva Contraseña</button>
                </div>

                <a href="#" class="forgot-password" style="text-align:center; margin-top:18px;" onclick="mostrarLogin(event)">← Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
    
    <!-- VINCULACIÓN DEL JS EXTERNO -->
    <script src="../../public/assets/js/login.js"></script>
</body>
</html>