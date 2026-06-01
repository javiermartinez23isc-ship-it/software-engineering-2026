<?php
session_start();

// 1. Destruir todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, borramos también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruir la sesión.
session_destroy();

// 4. CORRECCIÓN DE REDIRECCIÓN:
// Subimos dos niveles (../../) para salir de src/auth y entramos a views/auth/login.php
header("Location: ../../views/auth/login.php");
exit;
?>