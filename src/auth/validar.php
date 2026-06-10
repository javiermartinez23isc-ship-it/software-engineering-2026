<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

$usuario  = isset($_POST['usuario'])  ? mysqli_real_escape_string($conexion, $_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($usuario) || empty($password)) {
    echo "<script>alert('Por favor, llene todos los campos'); window.history.back();</script>";
    exit();
}

// Buscar usuario por correo (sin comparar contraseña en SQL)
$consulta  = "SELECT id_usuario, id_tipo_usuario, nombre, contrasena_hash, contrasena_provisional
              FROM usuario WHERE correo = '$usuario'";
$resultado = mysqli_query($conexion, $consulta);
$filas     = mysqli_fetch_assoc($resultado);

if ($filas && password_verify($password, $filas['contrasena_hash'])) {
    $_SESSION['usuario_id']      = $filas['id_usuario'];
    $_SESSION['nombre']          = $filas['nombre'];
    $_SESSION['id_tipo_usuario'] = $filas['id_tipo_usuario'];

    // Si es paciente con contraseña provisional → forzar cambio
    if ($filas['id_tipo_usuario'] == 3 && (int)$filas['contrasena_provisional'] === 1) {
        header("Location: ../../views/auth/cambiar_password.php");
        exit();
    }

    if ($filas['id_tipo_usuario'] == 1) {
        header("Location: ../../views/roles/doctor.php");
        exit();
    } elseif ($filas['id_tipo_usuario'] == 2) {
        header("Location: ../../views/roles/asistente.php");
        exit();
    } elseif ($filas['id_tipo_usuario'] == 3) {
        header("Location: ../../views/roles/paciente.php");
        exit();
    }
} else {
    echo "<script>alert('Error: Usuario o contraseña incorrectos'); window.history.back();</script>";
}
?>
