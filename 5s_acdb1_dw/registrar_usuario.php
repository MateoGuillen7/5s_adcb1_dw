<?php

// Iniciar sesión obligatoriamente al inicio
// SIN esto, $_SESSION["msg_exito"] lanzará un warning y no funcionará la redirección segura
session_start();

// Incluir conexión PDO (configurada con seguridad y manejo de excepciones)
require "conexion.php";

// Inicializar variables para mensajes de feedback y persistencia de datos
// Si hay error, el formulario mostrará lo que el usuario ya escribió
$mensaje = "";
$tipo    = "";
$cedula  = "";
$nombre  = "";
$correo  = "";

// Verificar si el formulario fue enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Recibir y sanitizar entradas básicas
    $cedula   = trim($_POST["cedula"]);
    $nombre   = trim($_POST["nombre"]);
    $correo   = filter_var(trim($_POST["correo"]), FILTER_VALIDATE_EMAIL);
    $pass     = $_POST["password"];
    $pass2    = $_POST["password_confirm"];

    // VALIDACIONES DE SEGURIDAD
    if (strlen($cedula) !== 10 || !ctype_digit($cedula)) {
        $mensaje = "❌ La cédula debe tener exactamente 10 dígitos numéricos.";
        $tipo    = "error";
    } elseif (empty($nombre) || !$correo || empty($pass)) {
        $mensaje = "⚠️ Completa todos los campos correctamente.";
        $tipo    = "error";
    } elseif ($pass !== $pass2) {
        $mensaje = "⚠️ Las contraseñas no coinciden.";
        $tipo    = "error";
    } elseif (strlen($pass) < 6) {
        $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        $tipo    = "error";
    } else {
        // Verificar duplicados en BD usando sentencia preparada (previene inyección SQL)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ? OR correo = ?");
        $stmt->execute([$cedula, $correo]);

        // Si fetch() devuelve un registro, ya existe un usuario con esos datos
        if ($stmt->fetch()) {
            $mensaje = "⚠️ Ya existe un usuario con esa cédula o correo.";
            $tipo    = "error";
        } else {
            // Cifrar contraseña 
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            
            // Preparar inserción segura
            $stmt = $pdo->prepare("INSERT INTO usuarios (cedula, nombre, correo, password) VALUES (?, ?, ?, ?)");
            
            try {
                // Ejecutar y confirmar registro
                $stmt->execute([$cedula, $nombre, $correo, $hash]);
                
                // Guardar mensaje de éxito en sesión y redirigir 
                // Evita que el usuario reenvíe el formulario al actualizar la página
                $_SESSION["msg_exito"] = "✅ Registro exitoso. Inicia sesión.";
                header("Location: login.php");
                exit; // Detener ejecución inmediatamente tras redirección
            } catch (PDOException $e) {
                // Registrar error técnico en logs del servidor (NUNCA mostrar al usuario)
                error_log("Error al registrar usuario: " . $e->getMessage());
                $mensaje = "❌ Error interno al registrar. Intenta más tarde.";
                $tipo    = "error";
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
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="card">
        <h2>Crear Cuenta</h2>
        <p class="subtitle">Completa los datos para registrarte en el sistema</p>
        
        <!-- Mostrar mensaje dinámico solo si existe -->
        <?php if ($mensaje !== ""): ?>
            <div class="msg <?php echo $tipo; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Formulario se envía a sí mismo (action vacío = misma página) -->
        <form id="formRegistro" action="" method="POST">
            <div class="form-grid">
                
                <!-- Cédula con validación HTML5 + persistencia PHP -->
                <div class="form-group">
                    <label for="cedula">Número de Cédula</label>
                    <input type="text" id="cedula" name="cedula" placeholder="Ej: 1234567890" 
                           pattern="[0-9]{10}" required 
                           value="<?php echo htmlspecialchars($cedula); ?>">
                </div>
                
                <!-- Nombre con persistencia -->
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required 
                           value="<?php echo htmlspecialchars($nombre); ?>">
                </div>

                <!-- Correo con validación HTML5 + persistencia -->
                <div class="form-group full-width">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" placeholder="usuario@ejemplo.com" required 
                           value="<?php echo htmlspecialchars($correo); ?>">
                </div>

                <!-- Contraseñas (no se persisten por seguridad) -->
                <div class="form-group">
                    <label for="pass1">Contraseña</label>
                    <input type="password" id="pass1" name="password" placeholder="Mínimo 6 caracteres" required>
                </div>
                
                <div class="form-group">
                    <label for="pass2">Confirmar Contraseña</label>
                    <input type="password" id="pass2" name="password_confirm" placeholder="Repite la contraseña" required>
                </div>

                <!-- Botón de envío -->
                <button type="submit" class="btn-primary">Registrarse</button>
            </div>
        </form>

        <p class="footer-link">¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>

    <!-- Validación visual en cliente (JavaScript) -->
    <script>
        document.getElementById("formRegistro").addEventListener("submit", function(e) {
            // 1. Obtener valores de contraseñas
            const pass1 = document.getElementById("pass1").value;
            const pass2 = document.getElementById("pass2").value;

            // 2. Si no coinciden, detener envío y mostrar error visual
            if (pass1 !== pass2) {
                e.preventDefault();
                
                // Eliminar mensaje anterior si existe
                const existingMsg = document.querySelector(".msg.error");
                if (existingMsg) existingMsg.remove();
                
                // Crear nuevo mensaje compatible con estilos.css (.msg.error)
                const errorDiv = document.createElement("div");
                errorDiv.className = "msg error";
                errorDiv.textContent = "⚠️ Las contraseñas no coinciden.";
                
                // Insertar antes del formulario
                document.querySelector(".card").insertBefore(errorDiv, document.querySelector("form"));
                
                // Auto-eliminar después de 3 segundos
                setTimeout(() => errorDiv.remove(), 3000);
            }
        });
    </script>
</body>
</html>