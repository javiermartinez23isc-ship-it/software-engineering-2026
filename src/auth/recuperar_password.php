<?php
/**
 * recuperar_password.php
 * Endpoint AJAX para el flujo de recuperación de contraseña.
 * Acepta dos acciones via POST:
 *   accion=verificar  → comprueba nombre + correo, devuelve id_usuario
 *   accion=cambiar    → actualiza la contraseña del usuario verificado
 */
include_once(__DIR__ . '/../../config/db.php');

header('Content-Type: application/json');

$accion = $_POST['accion'] ?? '';

// ── Acción 1: Verificar identidad ────────────────────────────
if ($accion === 'verificar') {
    $nombre_raw = trim($_POST['nombre'] ?? '');
    $correo_raw = trim($_POST['correo'] ?? '');

    if (empty($nombre_raw) || empty($correo_raw)) {
        echo json_encode(['ok' => false, 'msg' => 'Completa todos los campos.']);
        exit();
    }

    $correo = mysqli_real_escape_string($conexion, $correo_raw);

    // Buscar usuario por correo
    $res = mysqli_query($conexion,
        "SELECT id_usuario, nombre, apellido_paterno, apellido_materno
         FROM usuario WHERE correo = '$correo'");
    $user = $res ? mysqli_fetch_assoc($res) : null;

    if (!$user) {
        echo json_encode(['ok' => false, 'msg' => 'No se encontró ninguna cuenta con ese correo.']);
        exit();
    }

    // Mapa de normalización de tildes
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n'];

    // Función helper para normalizar una cadena
    $normalizar = function($str) use ($map) {
        return preg_replace('/\s+/', ' ', strtr(strtolower(trim($str)), $map));
    };

    // Nombre completo desde BD
    $nombre_bd_completo = $normalizar(
        $user['nombre'] . ' ' .
        ($user['apellido_paterno'] ?? '') . ' ' .
        ($user['apellido_materno'] ?? '')
    );

    // Solo el nombre de pila desde BD
    $nombre_bd_solo = $normalizar($user['nombre']);

    // Lo que escribió el usuario
    $nombre_input_norm = $normalizar($nombre_raw);

    // Aceptar si coincide con nombre completo O solo con el nombre de pila
    if ($nombre_input_norm !== $nombre_bd_completo && $nombre_input_norm !== $nombre_bd_solo) {
        echo json_encode(['ok' => false, 'msg' => 'El nombre no coincide con el registrado para ese correo.']);
        exit();
    }

    echo json_encode(['ok' => true, 'id_usuario' => (int)$user['id_usuario']]);
    exit();
}

// ── Acción 2: Cambiar contraseña ─────────────────────────────
if ($accion === 'cambiar') {
    $id_usuario  = (int)($_POST['id_usuario'] ?? 0);
    $nueva_pass  = $_POST['nueva_password'] ?? '';

    if ($id_usuario <= 0 || strlen($nueva_pass) < 6) {
        echo json_encode(['ok' => false, 'msg' => 'Datos inválidos. La contraseña debe tener al menos 6 caracteres.']);
        exit();
    }

    $pass_hash    = mysqli_real_escape_string($conexion, password_hash($nueva_pass, PASSWORD_BCRYPT));

    // Verificar que el usuario exista
    $check = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = '$id_usuario'");
    if (!$check || mysqli_num_rows($check) === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']);
        exit();
    }

    // Actualizar contraseña con hash bcrypt
    $upd = mysqli_query($conexion,
        "UPDATE usuario SET contrasena_hash = '$pass_hash' WHERE id_usuario = '$id_usuario'");

    if ($upd) {
        echo json_encode(['ok' => true, 'msg' => 'Contraseña actualizada correctamente.']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Error al actualizar: ' . mysqli_error($conexion)]);
    }
    exit();
}

echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida.']);
?>
