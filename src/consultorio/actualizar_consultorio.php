<?php
/**
 * Procesador: Actualizar nombre y logo del consultorio
 * Solo accesible por el Doctor (id_tipo_usuario = 1)
 */
include_once(__DIR__ . '/../../config/db.php');
session_start();

$ruta_doctor = '../../views/roles/doctor.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php");
    exit();
}

// 2. Solo el doctor puede gestionar el consultorio
if ($_SESSION['id_tipo_usuario'] != 1) {
    header("Location: ../../views/auth/acceso_denegado.php");
    exit();
}

// 3. Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $ruta_doctor");
    exit();
}

// 4. Crear tabla si no existe
$sql_create = "CREATE TABLE IF NOT EXISTS `configuracion_consultorio` (
    `id_config` int(11) NOT NULL AUTO_INCREMENT,
    `clave` varchar(100) NOT NULL,
    `valor` text DEFAULT NULL,
    PRIMARY KEY (`id_config`),
    UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conexion, $sql_create);

// 5. Procesar nombre del consultorio
$nombre_consultorio = trim($_POST['nombre_consultorio'] ?? '');
if (!empty($nombre_consultorio)) {
    // Validar: solo letras, números, espacios y caracteres comunes
    if (!preg_match('/^[\p{L}0-9\s\.\-\,\'\"]{1,100}$/u', $nombre_consultorio)) {
        echo "<script>alert('Error: El nombre del consultorio contiene caracteres no permitidos.'); window.history.back();</script>";
        exit();
    }
    $nombre_esc = mysqli_real_escape_string($conexion, $nombre_consultorio);
    mysqli_query($conexion, "INSERT INTO configuracion_consultorio (clave, valor) 
                              VALUES ('nombre_consultorio', '$nombre_esc')
                              ON DUPLICATE KEY UPDATE valor = '$nombre_esc'");
}

// 6. Procesar logo del consultorio
if (isset($_FILES['logo_consultorio']) && $_FILES['logo_consultorio']['error'] !== UPLOAD_ERR_NO_FILE) {

    $archivo = $_FILES['logo_consultorio'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error al recibir la imagen. Código: " . $archivo['error'] . "'); window.history.back();</script>";
        exit();
    }

    // Validar extensión
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $permitidos)) {
        echo "<script>alert('Error: Solo se permiten imágenes JPG, PNG, GIF o WEBP.'); window.history.back();</script>";
        exit();
    }

    // Validar tamaño (2 MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        echo "<script>alert('Error: La imagen no debe superar 2 MB.'); window.history.back();</script>";
        exit();
    }

    // Validar que sea imagen real
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) {
        echo "<script>alert('Error: El archivo no es una imagen válida.'); window.history.back();</script>";
        exit();
    }

    // Tipos MIME permitidos
    $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($info['mime'], $mimes_permitidos)) {
        echo "<script>alert('Error: El tipo de imagen no está permitido.'); window.history.back();</script>";
        exit();
    }

    // Crear directorio si no existe
    $directorio = __DIR__ . '/../../public/assets/img/logos/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    if (!is_writable($directorio)) {
        echo "<script>alert('Error: El servidor no tiene permisos para guardar imágenes.'); window.history.back();</script>";
        exit();
    }

    // Eliminar logo anterior si existe
    $res_logo_ant = mysqli_query($conexion, "SELECT valor FROM configuracion_consultorio WHERE clave = 'logo_consultorio'");
    if ($res_logo_ant && $row_ant = mysqli_fetch_assoc($res_logo_ant)) {
        $ruta_ant = __DIR__ . '/../../public/' . $row_ant['valor'];
        if (!empty($row_ant['valor']) && file_exists($ruta_ant)) {
            @unlink($ruta_ant);
        }
    }

    // Guardar nuevo logo
    $nombre_archivo = 'logo_consultorio_' . time() . '.' . $ext;
    $ruta_destino   = $directorio . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        $ruta_bd = mysqli_real_escape_string($conexion, 'assets/img/logos/' . $nombre_archivo);
        mysqli_query($conexion, "INSERT INTO configuracion_consultorio (clave, valor) 
                                  VALUES ('logo_consultorio', '$ruta_bd')
                                  ON DUPLICATE KEY UPDATE valor = '$ruta_bd'");
    } else {
        echo "<script>alert('Error al guardar la imagen. Verifica los permisos de la carpeta public/assets/img/logos/'); window.history.back();</script>";
        exit();
    }
}

// 7. Redirigir con éxito
header("Location: $ruta_doctor?consultorio=ok");
exit();
?>
