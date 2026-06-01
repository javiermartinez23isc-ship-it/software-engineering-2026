<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Agenda Vital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f7f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 50px 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 480px;
            width: 90%;
        }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        h1 { font-size: 1.6rem; color: #dc2626; margin-bottom: 10px; }
        .codigo { font-size: 0.85rem; color: #94a3b8; margin-bottom: 15px; letter-spacing: 1px; }
        p { color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px; }
        .btn-group { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn {
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-back {
            background: #e2e8f0;
            color: #475569;
        }
        .btn-login {
            background: #059669;
            color: white;
        }
        .divider {
            border: 0;
            border-top: 1px solid #f1f5f9;
            margin: 25px 0;
        }
        .brand { font-size: 0.8rem; color: #cbd5e1; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🚫</div>
        <div class="codigo">ERROR 403 — ACCESO DENEGADO</div>
        <h1>No tienes permiso para acceder a esta página</h1>
        <hr class="divider">
        <p>
            Esta sección está restringida a un tipo de usuario específico.<br>
            Si crees que esto es un error, inicia sesión con la cuenta correcta.
        </p>
        <div class="btn-group">
            <button class="btn btn-back" onclick="history.back()">← Volver atrás</button>
            <a href="../auth/login.php" class="btn btn-login">🔑 Ir al Login</a>
        </div>
        <p class="brand">Agenda Vital &mdash; Sistema de Gestión de Citas</p>
    </div>
</body>
</html>
