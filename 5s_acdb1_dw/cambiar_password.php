<?php

// Bloquea acceso si no hay login o sesión expirada
require "auth.php";

// Manejo de excepciones ya configurado en conexion.php
require "conexion.php";

// Inicializar variables para mensajes de feedback visual
$mensaje = "";
$tipo    = "";

// Generar token CSRF si no existe (previene ataques de falsificación de petición cruzada)
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION["csrf_token"];

// Procesar formulario de cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validar token CSRF con comparación segura (resistente a timing attacks)
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $mensaje = "❌ Solicitud inválida o expirada. Recarga la página e intenta de nuevo.";
        $tipo    = "error";
    } else {
        // Recibir contraseñas enviadas por el usuario
        $actual  = $_POST["actual"];
        $nueva   = $_POST["nueva"];
        $confirm = $_POST["confirmar"];

        // Validaciones de seguridad en servidor 
        if (empty($actual) || empty($nueva) || empty($confirm)) {
            $mensaje = "⚠️ Completa todos los campos.";
            $tipo    = "error";
        } elseif ($nueva !== $confirm) {
            $mensaje = "⚠️ La nueva contraseña y su confirmación no coinciden.";
            $tipo    = "error";
        } elseif (strlen($nueva) < 6) {
            $mensaje = "⚠️ La nueva contraseña debe tener al menos 6 caracteres.";
            $tipo    = "error";
        } elseif ($nueva === $actual) {
            // Evitar que el usuario "cambie" a la misma contraseña sin motivo
            $mensaje = "⚠️ La nueva contraseña debe ser diferente a la actual.";
            $tipo    = "error";
        } else {
            // Obtener hash actual almacenado en la base de datos
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION["user_id"]]);
            $hash_db = $stmt->fetchColumn();

            // Verificar que la contraseña actual ingresada sea correcta
            if (!password_verify($actual, $hash_db)) {
                $mensaje = "❌ La contraseña actual es incorrecta.";
                $tipo    = "error";
                
                // Registro opcional para auditoría o rate-limit futuro
                error_log("Intento fallido cambio de clave - User ID: " . $_SESSION["user_id"] . " | IP: " . $_SERVER["REMOTE_ADDR"]);
            } else {
                // Generar nuevo hash seguro con algoritmo actualizado 
                $hash_new = password_hash($nueva, PASSWORD_DEFAULT);

                // Actualizar campo password en la base de datos
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$hash_new, $_SESSION["user_id"]]);

                // Rotar token CSRF tras operación exitosa 
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                $mensaje = "✅ Contraseña actualizada correctamente.";
                $tipo    = "exito";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="card password-card">
        <!-- Enlace de regreso al perfil -->
        <a href="perfil_usuario.php" class="btn-back">← Volver al perfil</a>

        <h2>🔑 Cambiar Contraseña</h2>
        <p class="subtitle">Actualiza tu clave de acceso de forma segura</p>

        <!-- Mensaje de éxito o error solo si existe -->
        <?php if ($mensaje !== ""): ?>
            <div class="msg <?php echo $tipo; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Formulario con token CSRF oculto para validación estricta -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-grid password-grid">
                <!-- Contraseña actual (no se persiste por seguridad) -->
                <div class="form-group full-width">
                    <label for="actual">Contraseña Actual</label>
                    <input type="password" id="actual" name="actual" placeholder="Ingresa tu contraseña actual" required>
                </div>
                
                <!-- Nueva contraseña -->
                <div class="form-group">
                    <label for="nueva">Nueva Contraseña</label>
                    <input type="password" id="nueva" name="nueva" placeholder="Mínimo 6 caracteres" required>
                </div>
                
                <!-- Confirmación -->
                <div class="form-group">
                    <label for="confirmar">Confirmar Nueva</label>
                    <input type="password" id="confirmar" name="confirmar" placeholder="Repite la nueva contraseña" required>
                </div>

                <!-- Botón de envío -->
                <button type="submit" class="btn-primary full-width">Actualizar Contraseña</button>
            </div>
        </form>

        <p class="security-note">🔒 Tu nueva contraseña será cifrada automáticamente con bcrypt antes de guardarse.</p>
    </div>
</body>
</html>