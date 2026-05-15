<?php

// Iniciar sesión 
session_start();

// Incluir conexión PDO 
require "conexion.php";

// Capturar mensajes flash desde sesión (registro exitoso, timeout, etc.)
$mensaje_flash = "";
$tipo_flash    = "";

if (isset($_SESSION["msg_exito"])) {
    $mensaje_flash = $_SESSION["msg_exito"];
    $tipo_flash    = "exito";
    unset($_SESSION["msg_exito"]);
} elseif (isset($_GET["msg"]) && $_GET["msg"] === "timeout") {
    $mensaje_flash = "⏳ Sesión expirada por inactividad. Inicia sesión nuevamente.";
    $tipo_flash    = "error";
}

// Inicializar variables para el formulario actual
$mensaje = "";
$tipo    = "";
$correo  = "";

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Recibir y validar formato de correo + contraseña
    $correo = filter_var(trim($_POST["correo"]), FILTER_VALIDATE_EMAIL);
    $pass   = $_POST["password"];

    // Validación en servidor 
    if (!$correo || empty($pass)) {
        $mensaje = "⚠️ Ingresa un correo válido y tu contraseña.";
        $tipo    = "error";
    } else {
        // Consultar usuario por correo (sin revelar si existe hasta verificar hash)
        $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        // Verificar contraseña con comparación segura contra timing attacks
        if ($usuario && password_verify($pass, $usuario["password"])) {
            // Seguridad: Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);

            // Guardar solo datos esenciales en sesión (NUNCA password ni info sensible)
            $_SESSION["user_id"]     = $usuario["id"];
            $_SESSION["user_nombre"] = $usuario["nombre"];
            $_SESSION["last_activity"] = time(); // Iniciar contador de timeout para auth.php
            


            // Redirigir a destino seguro (evita Open Redirect attacks)
            $redirect_url = "perfil_usuario.php"; // Destino por defecto
            if (isset($_GET["redirect"])) {
                $safe_redirect = $_GET["redirect"];
                // Solo permitir redirecciones internas 
                if (strpos($safe_redirect, "http") !== 0 && strpos($safe_redirect, "..") === false) {
                    $redirect_url = $safe_redirect;
                }
            }
            
            header("Location: " . $redirect_url);
            exit; // Detener ejecución inmediatamente tras enviar header
        } else {
            // 13. Mensaje genérico (previene enumeración de usuarios)
            $mensaje = "❌ Correo o contraseña incorrectos.";
            $tipo    = "error";
            
            // Registrar intento fallido 
            error_log("Intento de login fallido - Correo: " . $correo . " | IP: " . $_SERVER["REMOTE_ADDR"]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="card login-card">
        <h2>Bienvenido</h2>
        <p class="subtitle">Ingresa tus credenciales para acceder al sistema</p>

        <!-- Mostrar mensaje flash (de sesión) si existe -->
        <?php if ($mensaje_flash !== ""): ?>
            <div class="msg <?php echo $tipo_flash; ?>"><?php echo htmlspecialchars($mensaje_flash); ?></div>
        <?php endif; ?>

        <!-- Mostrar mensaje de error actual del formulario -->
        <?php if ($mensaje !== ""): ?>
            <div class="msg <?php echo $tipo; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Formulario de autenticación -->
        <form method="POST" action="">
            <div class="form-grid login-grid">
                <!-- Correo con persistencia (no se borra si hay error) -->
                <div class="form-group full-width">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" placeholder="usuario@ejemplo.com" 
                           required value="<?php echo htmlspecialchars($correo); ?>">
                </div>
                
                <!-- Contraseña sin persistencia (estándar de seguridad) -->
                <div class="form-group full-width">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <!-- Botón de envío -->
                <button type="submit" class="btn-primary full-width">Ingresar</button>
            </div>
        </form>

        <p class="footer-link">¿No tienes cuenta? <a href="registrar_usuario.php">Regístrate aquí</a></p>
    </div>
</body>
</html>