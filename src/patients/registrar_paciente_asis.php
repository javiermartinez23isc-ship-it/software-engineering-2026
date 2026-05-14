<?php
// CORRECCIÓN 1: Ruta para llegar a config/db.php desde src/patients/
include_once(__DIR__ . '/../../config/db.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitización de datos (usando el puerto 3307 configurado en tu db.php)
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $ap_paterno = mysqli_real_escape_string($conexion, $_POST['ap_paterno']);
    $correo = mysqli_real_escape_string($conexion, $_POST['usuario']);
    
    // Contraseña genérica para el primer acceso
    $pass_provisional = "Nava2026*"; 

    // id_tipo_usuario 3 corresponde a 'Paciente'
    $query = "INSERT INTO usuario (id_tipo_usuario, nombre, apellido_paterno, correo, contrasena_hash) 
              VALUES (3, '$nombre', '$ap_paterno', '$correo', '$pass_provisional')";

    if (mysqli_query($conexion, $query)) {
        // CORRECCIÓN 2: Redirección a la nueva ubicación de la vista del asistente
        // Subimos dos niveles (../../) y entramos a views/roles/
        echo "<script>
                alert('Paciente registrado con éxito.\\n\\nUsuario: $correo\\nContraseña: $pass_provisional\\n\\nFavor de entregar estos accesos al paciente.');
                window.location.href = '../../views/roles/asistente.php';
              </script>";
    } else {
        echo "Error en el registro: " . mysqli_error($conexion);
    }
}
?>