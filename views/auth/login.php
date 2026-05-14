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
                <p class="login-title">Recuperar Acceso</p>
                <p class="login-subtitle">Ingresa el correo electrónico asociado a tu cuenta.</p>
                
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="recoveryEmail" placeholder="ejemplo@correo.com">
                </div>

                <button class="btn-login" onclick="enviarRecuperacion()">Enviar Instrucciones</button>
                <a href="#" class="forgot-password" style="text-align: center; margin-top: 20px;" onclick="mostrarLogin(event)">Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
    
    <!-- VINCULACIÓN DEL JS EXTERNO -->
    <script src="../../public/assets/js/login.js"></script>
</body>
</html>