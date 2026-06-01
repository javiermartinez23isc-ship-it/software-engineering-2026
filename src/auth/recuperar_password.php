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

    // Construir nombre completo normalizado (sin tildes, minúsculas) para comparar
    $nombre_bd = strtolower(trim(
        $user['nombre'] . ' ' .
        ($user['apellido_paterno'] ?? '') . ' ' .
        ($user['apellido_materno'] ?? '')
    ));
    $nombre_input = strtolower(trim($nombre_raw));

    // Normalizar tildes para comparación flexible
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n'];
    $nombre_bd_norm    = strtr($nombre_bd,    $map);
    $nombre_input_norm = strtr($nombre_input, $map);

    // Eliminar espacios múltiples
    $nombre_bd_norm    = preg_replace('/\s+/', ' ', $nombre_bd_norm);
    $nombre_input_norm = preg_replace('/\s+/', ' ', $nombre_input_norm);

    if ($nombre_bd_norm !== $nombre_input_norm) {
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

    $pass_escaped = mysqli_real_escape_string($conexion, $nueva_pass);

    // Verificar que el usuario exista
    $check = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE id_usuario = '$id_usuario'");
    if (!$check || mysqli_num_rows($check) === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']);
        exit();
    }

    // Actualizar contraseña (texto plano, igual que el sistema actual)
    $upd = mysqli_query($conexion,
        "UPDATE usuario SET contrasena_hash = '$pass_escaped' WHERE id_usuario = '$id_usuario'");

    if ($upd) {
        echo json_encode(['ok' => true, 'msg' => 'Contraseña actualizada correctamente.']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Error al actualizar: ' . mysqli_error($conexion)]);
    }
    exit();
}

echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida.']);
?>
