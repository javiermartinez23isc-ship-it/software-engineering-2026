<?php
include_once(__DIR__ . '/../../config/db.php');
session_start();

// Solo el asistente (tipo 2) puede registrar pacientes
if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['id_tipo_usuario'] !== 2) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/roles/asistente.php');
    exit();
}

// ── Recoger y sanear entradas ────────────────────────────────────────────────
$nombre     = trim($_POST['nombre']     ?? '');
$ap_paterno = trim($_POST['ap_paterno'] ?? '');
$ap_materno = trim($_POST['ap_materno'] ?? '');   // opcional
$correo     = trim($_POST['usuario']    ?? '');
$telefono   = trim($_POST['telefono']   ?? '');

// ── Validaciones ─────────────────────────────────────────────────────────────
if (empty($nombre)) {
    echo "<script>alert('Error: El nombre es obligatorio.'); window.history.back();</script>";
    exit();
}
if (!preg_match('/^[\p{L}\s\-]+$/u', $nombre)) {
    echo "<script>alert('Error: El nombre solo puede contener letras, espacios y guiones.'); window.history.back();</script>";
    exit();
}

if (empty($ap_paterno)) {
    echo "<script>alert('Error: El apellido paterno es obligatorio.'); window.history.back();</script>";
    exit();
}
if (!preg_match('/^[\p{L}\s\-]+$/u', $ap_paterno)) {
    echo "<script>alert('Error: El apellido paterno solo puede contener letras, espacios y guiones.'); window.history.back();</script>";
    exit();
}

// Apellido materno es opcional; si se ingresa debe ser válido
if ($ap_materno !== '' && !preg_match('/^[\p{L}\s\-]+$/u', $ap_materno)) {
    echo "<script>alert('Error: El apellido materno solo puede contener letras, espacios y guiones.'); window.history.back();</script>";
    exit();
}

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Error: El correo electrónico no tiene un formato válido.'); window.history.back();</script>";
    exit();
}

if ($telefono !== '' && !preg_match('/^[\d\s\+\-\(\)]{7,15}$/', $telefono)) {
    echo "<script>alert('Error: El teléfono solo puede contener números, espacios, +, - y paréntesis (7-15 caracteres).'); window.history.back();</script>";
    exit();
}

// ── Escapar para uso seguro en consultas ─────────────────────────────────────
$nombre_esc     = mysqli_real_escape_string($conexion, $nombre);
$ap_paterno_esc = mysqli_real_escape_string($conexion, $ap_paterno);
$ap_materno_esc = mysqli_real_escape_string($conexion, $ap_materno);
$correo_esc     = mysqli_real_escape_string($conexion, $correo);
$telefono_esc   = mysqli_real_escape_string($conexion, $telefono);

// ── Verificar correo duplicado ────────────────────────────────────────────────
$check = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE correo = '$correo_esc'");
if (!$check) {
    echo "<script>alert('Error de base de datos al verificar correo.'); window.history.back();</script>";
    exit();
}
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Error: Este correo ya está registrado en el sistema.'); window.history.back();</script>";
    exit();
}

// ── Insertar nuevo paciente ───────────────────────────────────────────────────
$pass_provisional = 'Nava2026*';

// ap_materno puede ser vacío → se guarda como NULL si está vacío
$ap_materno_sql = ($ap_materno_esc !== '') ? "'$ap_materno_esc'" : 'NULL';
$telefono_sql   = ($telefono_esc   !== '') ? "'$telefono_esc'"   : 'NULL';

$query = "INSERT INTO usuario 
            (id_tipo_usuario, nombre, apellido_paterno, apellido_materno, telefono, correo, contrasena_hash, contrasena_provisional)
          VALUES 
            (3, '$nombre_esc', '$ap_paterno_esc', $ap_materno_sql, $telefono_sql, '$correo_esc', '$pass_provisional', 1)";

if (mysqli_query($conexion, $query)) {
    $correo_js = addslashes($correo);
    echo "<script>
            alert('Paciente registrado con éxito.\\n\\nUsuario: $correo_js\\nContraseña: $pass_provisional\\n\\nFavor de entregar estos accesos al paciente.');
            window.location.href = '../../views/roles/asistente.php';
          </script>";
} else {
    $err = addslashes(mysqli_error($conexion));
    echo "<script>alert('Error en el registro: $err'); window.history.back();</script>";
}
?>
