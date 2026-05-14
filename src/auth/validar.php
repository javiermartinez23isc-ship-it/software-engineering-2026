<?php
// CORRECCIÓN 1: Ruta para llegar a la configuración desde src/auth/
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Validamos que los datos existan para evitar errores de "undefined index"
$usuario = isset($_POST['usuario']) ? mysqli_real_escape_string($conexion, $_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($usuario) || empty($password)) {
    echo "<script>alert('Por favor, llene todos los campos'); window.history.back();</script>";
    exit();
}

// CORRECCIÓN 2: Asegúrate de usar los nombres de columna correctos (correo y contrasena_hash)
$consulta = "SELECT id_usuario, id_tipo_usuario, nombre FROM usuario WHERE correo='$usuario' AND contrasena_hash='$password'";
$resultado = mysqli_query($conexion, $consulta);
$filas = mysqli_fetch_array($resultado);

if ($filas) {
    $_SESSION['usuario_id'] = $filas['id_usuario'];
    $_SESSION['nombre'] = $filas['nombre'];
    $_SESSION['id_tipo_usuario'] = $filas['id_tipo_usuario']; 
    
    // CORRECCIÓN 3: Redirecciones a la nueva carpeta views/roles/
    // Subimos dos niveles (../../) para salir de src/auth y entramos a views/roles/
    if ($filas['id_tipo_usuario'] == 1) { 
        header("location: ../../views/roles/doctor.php"); 
        exit();
    } else if ($filas['id_tipo_usuario'] == 2) { 
        header("location: ../../views/roles/asistente.php"); 
        exit();
    } else if ($filas['id_tipo_usuario'] == 3) { 
        header("location: ../../views/roles/paciente.php"); 
        exit();
    }
} else {
    echo "<script>alert('Error: Usuario o contraseña incorrectos'); window.history.back();</script>";
}

mysqli_free_result($resultado);
mysqli_close($conexion);
?>