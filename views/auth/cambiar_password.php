<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Solo pacientes autenticados con contraseña provisional
if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['id_tipo_usuario'] !== 3) {
    header('Location: login.php');
    exit();
}

// Si ya cambió su contraseña, redirigir al panel
$id_usuario = (int)$_SESSION['usuario_id'];
$check = mysqli_query($conexion,
    "SELECT contrasena_provisional FROM usuario WHERE id_usuario = '$id_usuario'");
$row = mysqli_fetch_assoc($check);
if (!$row || (int)$row['contrasena_provisional'] === 0) {
    header('Location: ../roles/paciente.php');
    exit();
}

$nombre = $_SESSION['nombre'] ?? 'Paciente';
$error  = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva    = trim($_POST['nueva_password']     ?? '');
    $confirma = trim($_POST['confirmar_password'] ?? '');

    if (empty($nueva) || empty($confirma)) {
        $error = 'Por favor completa ambos campos.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirma) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($nueva === 'Nava2026*') {
        $error = 'Debes elegir una contraseña diferente a la provisional.';
    } else {
        $pass_esc = mysqli_real_escape_string($conexion, password_hash($nueva, PASSWORD_BCRYPT));
        $ok = mysqli_query($conexion,
            "UPDATE usuario
             SET contrasena_hash = '$pass_esc', contrasena_provisional = 0
             WHERE id_usuario = '$id_usuario'");

        if ($ok) {
            header('Location: ../roles/paciente.php');
            exit();
        } else {
            $error = 'Error al guardar la contraseña. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Vital — Cambiar Contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a237e 0%, #00bcd4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            width: min(420px, 100%);
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo-wrap img {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        h1 {
            font-size: 1.3rem;
            color: #1a237e;
            text-align: center;
            margin-bottom: 6px;
        }

        .aviso {
            background: #fff9c3;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: .85rem;
            color: #92400e;
            margin-bottom: 22px;
            text-align: center;
            line-height: 1.5;
        }

        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .85rem;
            color: #991b1b;
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-weight: 700;
            font-size: .85rem;
            color: #475569;
            margin-bottom: 6px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: .95rem;
            font-family: inherit;
            margin-bottom: 16px;
            transition: border-color .18s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #00bcd4;
        }

        .requisitos {
            font-size: .78rem;
            color: #94a3b8;
            margin-top: -12px;
            margin-bottom: 16px;
        }

        button[type="submit"] {
            width: 100%;
            background: #00bcd4;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 32px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background .18s;
            margin-top: 4px;
        }
        button[type="submit"]:hover { background: #0097a7; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrap">
        <img src="../../public/assets/img/logo_agenda_vital.png" alt="Agenda Vital">
    </div>

    <h1>Bienvenido, <?php echo htmlspecialchars($nombre); ?></h1>

    <div class="aviso">
        🔐 Por seguridad, debes crear una contraseña personal antes de continuar.<br>
        Tu contraseña provisional ya no será válida después de este paso.
    </div>

    <?php if ($error): ?>
        <div class="error-box">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="nueva_password">Nueva Contraseña</label>
        <input type="password" id="nueva_password" name="nueva_password"
               placeholder="Mínimo 6 caracteres" required autofocus>
        <p class="requisitos">Mínimo 6 caracteres. No puede ser la contraseña provisional.</p>

        <label for="confirmar_password">Confirmar Contraseña</label>
        <input type="password" id="confirmar_password" name="confirmar_password"
               placeholder="Repite tu nueva contraseña" required>

        <button type="submit">✅ Guardar y Continuar</button>
    </form>
</div>
</body>
</html>
