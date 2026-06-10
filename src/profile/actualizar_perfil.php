<?php
// Procesador: Actualizar perfil de cualquier usuario (doctor, asistente, paciente)
include_once(__DIR__ . '/../../config/db.php');
session_start();

// 1. Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}

$id_usuario   = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['id_tipo_usuario'];

// Determinar ruta de regreso según rol
$rutas = [
    1 => '../../views/roles/doctor.php',
    2 => '../../views/roles/asistente.php',
    3 => '../../views/roles/paciente.php'
];
$ruta_regreso = $rutas[$tipo_usuario] ?? '../../views/auth/login.php';

// 2. Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $ruta_regreso");
    exit();
}

// ── Función de validación alfabética ──────────────────────────────────────────
function soloLetras($valor) {
    // Permite letras (incluyendo acentos y ñ), espacios y guiones
    return preg_match('/^[\p{L}\s\-]+$/u', $valor);
}

// ── Función de validación telefónica ─────────────────────────────────────────
function soloTelefono($valor) {
    // Permite dígitos, espacios, +, - y paréntesis
    return empty($valor) || preg_match('/^[\d\s\+\-\(\)]{7,15}$/', $valor);
}

// 3. Sanitizar y leer campos según el rol
// El paciente (tipo 3) solo puede cambiar teléfono y contraseña
// Doctor (1) y asistente (2) pueden cambiar todos sus datos

$nueva_password  = trim($_POST['nueva_password'] ?? '');
$confirmar_pass  = trim($_POST['confirmar_password'] ?? '');
$telefono        = mysqli_real_escape_string($conexion, trim($_POST['telefono'] ?? ''));

if ($tipo_usuario == 3) {
    // ── PACIENTE: solo teléfono y contraseña ──────────────────────────────────
    // Validar teléfono
    if (!soloTelefono($telefono)) {
        echo "<script>alert('Error: El teléfono solo puede contener números, espacios, +, - y paréntesis.'); window.history.back();</script>";
        exit();
    }

    // Manejar cambio de contraseña
    $pass_sql = '';
    if (!empty($nueva_password)) {
        if ($nueva_password !== $confirmar_pass) {
            echo "<script>alert('Error: Las contraseñas no coinciden.'); window.history.back();</script>";
            exit();
        }
        if (strlen($nueva_password) < 6) {
            echo "<script>alert('Error: La contraseña debe tener al menos 6 caracteres.'); window.history.back();</script>";
            exit();
        }
        $pass_escapada = mysqli_real_escape_string($conexion, password_hash($nueva_password, PASSWORD_BCRYPT));
        $pass_sql = ", contrasena_hash = '$pass_escapada'";
    }

    // Manejar foto de perfil
    $quitar_foto = isset($_POST['quitar_foto']) && $_POST['quitar_foto'] === '1';
    if ($quitar_foto) {
        // Borrar archivo físico si existe
        $res_foto = mysqli_query($conexion, "SELECT foto_perfil FROM usuario WHERE id_usuario = '$id_usuario'");
        $row_foto = mysqli_fetch_assoc($res_foto);
        if (!empty($row_foto['foto_perfil'])) {
            $ruta_fisica = __DIR__ . '/../../public/' . $row_foto['foto_perfil'];
            if (file_exists($ruta_fisica)) @unlink($ruta_fisica);
        }
        $foto_sql = ", foto_perfil = NULL";
    } else {
        $foto_sql = procesarFoto($conexion, $id_usuario);
        if ($foto_sql === false) exit();
    }

    $sql = "UPDATE usuario SET telefono = '$telefono' $foto_sql $pass_sql WHERE id_usuario = '$id_usuario'";
} else {
    // ── DOCTOR / ASISTENTE: todos los campos ─────────────────────────────────
    $nombre           = trim($_POST['nombre'] ?? '');
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
    $apellido_materno = trim($_POST['apellido_materno'] ?? '');
    $correo           = trim($_POST['correo'] ?? '');

    // Validar campos obligatorios
    if (empty($nombre) || empty($correo)) {
        echo "<script>alert('Error: El nombre y el correo son obligatorios.'); window.history.back();</script>";
        exit();
    }

    // Validar que nombre y apellidos sean solo letras
    if (!soloLetras($nombre)) {
        echo "<script>alert('Error: El nombre solo puede contener letras.'); window.history.back();</script>";
        exit();
    }
    if (!empty($apellido_paterno) && !soloLetras($apellido_paterno)) {
        echo "<script>alert('Error: El apellido paterno solo puede contener letras.'); window.history.back();</script>";
        exit();
    }
    if (!empty($apellido_materno) && !soloLetras($apellido_materno)) {
        echo "<script>alert('Error: El apellido materno solo puede contener letras.'); window.history.back();</script>";
        exit();
    }
    if (!soloTelefono($telefono)) {
        echo "<script>alert('Error: El teléfono solo puede contener números, espacios, +, - y paréntesis.'); window.history.back();</script>";
        exit();
    }

    // Sanitizar para SQL
    $nombre           = mysqli_real_escape_string($conexion, $nombre);
    $apellido_paterno = mysqli_real_escape_string($conexion, $apellido_paterno);
    $apellido_materno = mysqli_real_escape_string($conexion, $apellido_materno);
    $correo           = mysqli_real_escape_string($conexion, $correo);

    // Verificar que el correo no esté en uso por otro usuario
    $check_correo = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE correo = '$correo' AND id_usuario != '$id_usuario'");
    if (mysqli_num_rows($check_correo) > 0) {
        echo "<script>alert('Error: Este correo ya está registrado por otro usuario.'); window.history.back();</script>";
        exit();
    }

    // Manejar cambio de contraseña (opcional)
    $pass_sql = '';
    if (!empty($nueva_password)) {
        if ($nueva_password !== $confirmar_pass) {
            echo "<script>alert('Error: Las contraseñas no coinciden.'); window.history.back();</script>";
            exit();
        }
        if (strlen($nueva_password) < 6) {
            echo "<script>alert('Error: La contraseña debe tener al menos 6 caracteres.'); window.history.back();</script>";
            exit();
        }
        $pass_escapada = mysqli_real_escape_string($conexion, password_hash($nueva_password, PASSWORD_BCRYPT));
        $pass_sql = ", contrasena_hash = '$pass_escapada'";
    }

    // Manejar foto de perfil
    $quitar_foto = isset($_POST['quitar_foto']) && $_POST['quitar_foto'] === '1';
    if ($quitar_foto) {
        $res_foto = mysqli_query($conexion, "SELECT foto_perfil FROM usuario WHERE id_usuario = '$id_usuario'");
        $row_foto = mysqli_fetch_assoc($res_foto);
        if (!empty($row_foto['foto_perfil'])) {
            $ruta_fisica = __DIR__ . '/../../public/' . $row_foto['foto_perfil'];
            if (file_exists($ruta_fisica)) @unlink($ruta_fisica);
        }
        $foto_sql = ", foto_perfil = NULL";
    } else {
        $foto_sql = procesarFoto($conexion, $id_usuario);
        if ($foto_sql === false) exit();
    }

    $sql = "UPDATE usuario SET 
                nombre = '$nombre',
                apellido_paterno = '$apellido_paterno',
                apellido_materno = '$apellido_materno',
                telefono = '$telefono',
                correo = '$correo'
                $foto_sql
                $pass_sql
            WHERE id_usuario = '$id_usuario'";
}

// 4. Verificar si hay cambios reales antes de guardar
$datos_actuales = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT nombre, apellido_paterno, apellido_materno, telefono, correo, foto_perfil
     FROM usuario WHERE id_usuario = '$id_usuario'"));

$hay_cambios = false;

if ($tipo_usuario == 3) {
    // Paciente: cambio en teléfono, foto o contraseña
    if ($telefono !== ($datos_actuales['telefono'] ?? '')) $hay_cambios = true;
    if (!empty($nueva_password)) $hay_cambios = true;
    if ($quitar_foto) $hay_cambios = true;
    if ($foto_sql !== '' && $foto_sql !== false) $hay_cambios = true;
} else {
    // Doctor / Asistente: cambio en cualquier campo
    if ($nombre           !== ($datos_actuales['nombre']           ?? '')) $hay_cambios = true;
    if ($apellido_paterno !== ($datos_actuales['apellido_paterno'] ?? '')) $hay_cambios = true;
    if ($apellido_materno !== ($datos_actuales['apellido_materno'] ?? '')) $hay_cambios = true;
    if ($telefono         !== ($datos_actuales['telefono']         ?? '')) $hay_cambios = true;
    if ($correo           !== ($datos_actuales['correo']           ?? '')) $hay_cambios = true;
    if (!empty($nueva_password)) $hay_cambios = true;
    if ($quitar_foto) $hay_cambios = true;
    if ($foto_sql !== '' && $foto_sql !== false) $hay_cambios = true;
}

if (!$hay_cambios) {
    header("Location: $ruta_regreso?perfil=sin_cambios");
    exit();
}

// 5. Ejecutar UPDATE
if (mysqli_query($conexion, $sql)) {
    if ($tipo_usuario != 3) {
        $_SESSION['nombre'] = $_POST['nombre'] ?? $_SESSION['nombre'];
    }
    header("Location: $ruta_regreso?perfil=ok");
    exit();
} else {
    $error = addslashes(mysqli_error($conexion));
    echo "<script>alert('Error al actualizar el perfil: $error'); window.history.back();</script>";
}

// ── Función auxiliar: procesar subida de foto ─────────────────────────────────
function procesarFoto($conexion, $id_usuario) {
    // Verificar si la columna foto_perfil existe
    $col_check = mysqli_query($conexion, "SHOW COLUMNS FROM usuario LIKE 'foto_perfil'");
    if (!$col_check || mysqli_num_rows($col_check) === 0) {
        return ''; // Columna no existe aún, ignorar foto
    }

    if (!isset($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] === UPLOAD_ERR_NO_FILE) {
        return ''; // No se subió ninguna foto, no es error
    }

    if ($_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error al recibir la imagen. Código: " . $_FILES['foto_perfil']['error'] . "'); window.history.back();</script>";
        return false;
    }

    $archivo    = $_FILES['foto_perfil'];
    $ext        = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $permitidos)) {
        echo "<script>alert('Error: Solo se permiten imágenes JPG, PNG, GIF o WEBP.'); window.history.back();</script>";
        return false;
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        echo "<script>alert('Error: La imagen no debe superar 2 MB.'); window.history.back();</script>";
        return false;
    }

    // Verificar que sea realmente una imagen
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) {
        echo "<script>alert('Error: El archivo no es una imagen válida.'); window.history.back();</script>";
        return false;
    }

    $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $ext;
    $directorio     = __DIR__ . '/../../public/assets/img/perfiles/';

    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    if (!is_writable($directorio)) {
        echo "<script>alert('Error: El servidor no tiene permisos para guardar imágenes. Contacta al administrador.'); window.history.back();</script>";
        return false;
    }

    $ruta_destino = $directorio . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        $ruta_bd = mysqli_real_escape_string($conexion, 'assets/img/perfiles/' . $nombre_archivo);
        return ", foto_perfil = '$ruta_bd'";
    } else {
        echo "<script>alert('Error al guardar la imagen en el servidor. Verifica los permisos de la carpeta public/assets/img/perfiles/'); window.history.back();</script>";
        return false;
    }
}
?>
