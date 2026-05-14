<?php
// CORRECCIÓN 1: Ruta para llegar a config/db.php desde src/appointments/
include_once(__DIR__ . '/../../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Limpiar datos de entrada (Usando el puerto 3307 configurado en db.php)
    $id_horario = mysqli_real_escape_string($conexion, $_POST['id_horario']);
    $id_usuario = $_SESSION['usuario_id']; 
    $tipo_user = isset($_SESSION['id_tipo_usuario']) ? $_SESSION['id_tipo_usuario'] : 3; 

    // CORRECCIÓN 2: Definir rutas hacia la nueva estructura de carpetas views/roles/
    $redireccion = ($tipo_user == 2) ? '../../views/roles/asistente.php' : '../../views/roles/paciente.php';

    // 2. DOBLE SEGURIDAD: Verificar si el paciente ya tiene una cita activa
    // Se agrega filtro CURDATE() para que citas viejas no concluidas NO bloqueen al usuario
    if ($tipo_user == 3) {
        $check_citas = mysqli_query($conexion, "SELECT c.id_cita 
                                                FROM cita c 
                                                JOIN horario h ON c.id_horario = h.id_horario 
                                                WHERE c.id_usuario = '$id_usuario' 
                                                AND c.id_estado_cita IN (1, 4)
                                                AND h.fecha >= CURDATE()");
        
        if (mysqli_num_rows($check_citas) > 0) {
            echo "<script>
                    alert('Acceso denegado: Ya cuentas con una cita pendiente o confirmada próximamente.'); 
                    window.location.href='$redireccion';
                  </script>";
            exit();
        }
    }

    // 3. Verificar disponibilidad real del horario
    $check_horario = mysqli_query($conexion, "SELECT estado FROM horario WHERE id_horario = '$id_horario'");
    $row_h = mysqli_fetch_assoc($check_horario);

    if ($row_h && $row_h['estado'] == 'ocupado') {
        echo "<script>alert('Error: Este horario acaba de ser reservado por alguien más.'); window.location.href='$redireccion';</script>";
        exit();
    }

    // 4. Proceder a insertar la cita (id_estado_cita = 1 es Pendiente)
    $sql_insert = "INSERT INTO cita (id_usuario, id_horario, id_estado_cita, fecha_registro) 
                   VALUES ('$id_usuario', '$id_horario', 1, NOW())";
    
    if (mysqli_query($conexion, $sql_insert)) {
        // 5. AQUÍ SE SINCRONIZA: Marcar el horario como ocupado en la tabla 'horario'
        mysqli_query($conexion, "UPDATE horario SET estado = 'ocupado' WHERE id_horario = '$id_horario'");
        
        echo "<script>alert('¡Cita agendada con éxito!'); window.location.href='$redireccion';</script>";
    } else {
        echo "Error crítico al agendar: " . mysqli_error($conexion);
    }
} else {
    // CORRECCIÓN 3: Redirigir al nuevo login.php si se accede sin POST
    header("Location: ../../views/auth/login.php");
    exit();
}
?>