<?php

// Iniciar sesión con configuración de seguridad reforzada
session_start();

// Verificar timeout de sesión
// cierra la sesión automáticamente si el usuario está inactivo
$timeout_duration = 1800; // 30 minutos en segundos
if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"]) > $timeout_duration) {
    // Sesión expirada por inactividad: limpiar y redirigir
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit;
}

// Actualizar timestamp de última actividad en cada carga de página protegida
// Esto reinicia el contador de timeout mientras el usuario navega activamente
$_SESSION["last_activity"] = time();

// Verificar si el usuario está autenticado (existe user_id en sesión)
// Si NO existe, el usuario no ha iniciado sesión o su sesión fue destruida
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    
    // Redirigir al login con parámetro de retorno
    // guardar la URL actual para redirigir de vuelta tras login exitoso
    $current_url = $_SERVER["REQUEST_URI"];
    header("Location: login.php?redirect=" . urlencode($current_url));
    
    // Detener ejecución inmediatamente para evitar que se cargue contenido privado
    exit;
}
?>