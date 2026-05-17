#  Sistema de Registro y Autenticación de Usuarios

Sistema web completo para gestión de cuentas desarrollado en PHP + MySQL. Implementación de registro, inicio de sesión seguro, edición de perfil, cambio de contraseña y cierre de sesión, siguiendo buenas prácticas de seguridad, separación de responsabilidades y experiencia de usuario.

## Características principales:
- Autenticación segura con password_hash() / password_verify()
- Sentencias preparadas PDO (protección contra inyección SQL)
- Tokens CSRF en formularios críticos (perfil y cambio de contraseña)
- Timeout de sesión por inactividad (30 minutos)
- Regeneración de ID de sesión tras login (previene *Session Fixation*)
- Interfaz responsive con paleta profesional y validación en cliente + servidor
- Arquitectura limpia: lógica PHP, HTML semántico y CSS unificado

## Instalación y configuración local:
1. Descargar o clonar el proyecto
   git clone https://github.com/MateoGuillen7/5s_adcb1_dw.git
2. Iniciar servicios
   En el panel de XAMPP encender:
   - Apache
   - MySQL
3. Importar BD
   - Abre http://localhost/phpmyadmin
   - Haz clic en Importar → pestaña superior
   - Selecciona el archivo registro_de_usuarios.sql incluido en la carpeta del proyecto
   - Haz clic en Continuar (se creará la BD registro_de_usuarios y la tabla usuarios automáticamente)

## Como probar el sistema:
1. Abre en tu navegador:
   - http://localhost/5s_acdb1_dw/registrar_usuario.php
2. Crea una cuenta con datos de prueba.
   - Serás redirigido al login. Inicia sesión con las credenciales creadas.
3. Explora las funcionalidades:
   - Edita nombre/correo en perfil_usuario.php
   - Cambia contraseña en cambiar_password.php
   - Cierra sesión y verifica que el acceso a rutas privadas sea bloqueado.
4. Usuario de prueba:
   - roberto@gmail.com - clave: 123456
5. Prueba la seguridad:
   - Intenta acceder a perfil_usuario.php sin iniciar sesión → Debe redirigir al login.
   - Ingresa contraseñas distintas → Validación JS y PHP bloquean el envío.
   - Espera 30 min de inactividad → La sesión expira automáticamente.

   


