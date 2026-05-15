<?php

// Bloquea acceso si no hay login activo o sesión expirada
require "auth.php";

// Incluir conexión segura a la base de datos 
require "conexion.php";

// Inicializar variables para mensajes de feedback y control de formulario
$mensaje = "";
$tipo    = "";

// Generar token CSRF único para este formulario (previene ataques de falsificación de petición cruzada)
// Se guarda en $_SESSION para validarlo estrictamente al recibir el POST
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION["csrf_token"];

// Procesar actualización de datos si el usuario envía el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validar token CSRF primero (comparación segura contra timing attacks)
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $mensaje = "❌ Solicitud inválida o expirada. Recarga la página e intenta de nuevo.";
        $tipo    = "error";
    } else {
        // Recibir y sanitizar datos del formulario
        $nombre = trim($_POST["nombre"]);
        $correo = filter_var(trim($_POST["correo"]), FILTER_VALIDATE_EMAIL);

        // Validaciones básicas de servidor
        if (empty($nombre) || !$correo) {
            $mensaje = "⚠️ Completa los campos con datos válidos.";
            $tipo    = "error";
        } else {
            // Verificar que el nuevo correo no pertenezca a OTRO usuario
            // Se excluye el ID actual (AND id != ?) 
            // para permitir que mantenga su propio correo si no lo modifica
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
            $stmt->execute([$correo, $_SESSION["user_id"]]);

            if ($stmt->fetch()) {
                $mensaje = "⚠️ Ese correo ya está registrado por otro usuario.";
                $tipo    = "error";
            } else {
                // Ejecutar actualización en la base de datos con sentencia preparada
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?");
                $stmt->execute([$nombre, $correo, $_SESSION["user_id"]]);

                // Actualizar variable de sesión en memoria para reflejar cambios inmediatamente
                // Esto evita que el usuario tenga que cerrar y volver a entrar para ver su nuevo nombre
                $_SESSION["user_nombre"] = $nombre;

                // Regenerar token CSRF tras operación exitosa (rotación de tokens = mayor seguridad)
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                $mensaje = "✅ Perfil actualizado correctamente.";
                $tipo    = "exito";
            }
        }
    }
}

// Consultar datos actuales del usuario para mostrarlos en la vista estática y pre-llenar el formulario
$stmt = $pdo->prepare("SELECT cedula, nombre, correo, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$usuario = $stmt->fetch();

// Verificación anti-sesión huérfana: si el ID de sesión ya no existe en BD, forzar logout seguro
if (!$usuario) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=session_invalid");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="card profile-card">
        <!-- Encabezado con avatar -->
        <div class="profile-header">
            <div class="avatar-placeholder">👤</div>
            <div>
                <h2>Hola, <?php echo htmlspecialchars($usuario["nombre"]); ?></h2>
                <p class="member-since">Miembro desde <?php echo date("F Y", strtotime($usuario["fecha_registro"])); ?></p>
            </div>
        </div>

        <!-- Mostrar mensaje de feedback (éxito o error) solo si la variable contiene texto -->
        <?php if ($mensaje !== ""): ?>
            <div class="msg <?php echo $tipo; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Información estática (izquierda) + Formulario de edición (derecha) -->
        <div class="profile-grid">
            <div class="info-section">
                <h3>📋 Información Personal</h3>
                <div class="info-item">
                    <span class="label">Cédula:</span>
                    <span class="value"><?php echo htmlspecialchars($usuario["cedula"]); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Correo actual:</span>
                    <span class="value"><?php echo htmlspecialchars($usuario["correo"]); ?></span>
                </div>
            </div>

            <div class="form-section">
                <h3>✏️ Actualizar Datos</h3>
                <!-- Formulario con token CSRF oculto para validación segura -->
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($usuario["nombre"]); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($usuario["correo"]); ?>" required>
                    </div>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Botones de navegación -->
        <div class="profile-actions">
            <a href="cambiar_password.php" class="btn-outline">🔑 Cambiar Contraseña</a>
            <a href="logout.php" class="btn-danger">🚪 Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>