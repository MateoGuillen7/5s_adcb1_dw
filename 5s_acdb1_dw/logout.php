<?php
// Iniciar o reanudar la sesión para acceder a los datos de autenticación actuales
session_start();

// Eliminar todas las variables de sesión registradas (limpiar datos del usuario en memoria)
session_unset();

// Eliminar la cookie de sesión del navegador para una limpieza completa del cliente
// Esto previene que el navegador conserve el identificador de sesión tras el cierre
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir completamente la sesión y eliminar los archivos temporales del servidor
session_destroy();

// Redirigir al usuario a la página de inicio de sesión
header("Location: login.php");

// Detener la ejecución del script inmediatamente
// Evita que se procese código posterior y garantiza que la redirección se ejecute sin demoras
exit;
?>