<?php

// Definir parámetros de conexión
$host    = "localhost";
$db      = "registro_de_usuarios";
$user    = "root";
$pass    = "";
$charset = "utf8mb4";

// Construir DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Seguridad y comportamiento para PDO (PHP Data Objects)
$options = [
    // Lanzar excepciones en lugar de errores silenciosos (permite try/catch)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Devolver resultados como array asociativo (más legible: $row["nombre"])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Usar sentencias preparadas NATIVAS de MySQL (previene inyección SQL real)
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // Deshabilitar carga de archivos locales vía SQL (previene ataque LOAD DATA)
    PDO::MYSQL_ATTR_LOCAL_INFILE => false,
];

// Conectar dentro de bloque try/catch para manejo controlado de errores
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
  
    
} catch (\PDOException $e) {
   
    error_log("Error de conexión a BD: " . $e->getMessage());
    die("Error interno del servidor. Intenta más tarde.");
}
?>