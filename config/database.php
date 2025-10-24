<?php
// Configuración de la base de datos
define('DB_HOST', 'db');
define('DB_NAME', 'handinhand');
define('DB_USER', 'user');
define('DB_PASS', 'userpass');

// Función para conectar a la base de datos
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch(PDOException $e) {
        // Log el error en lugar de mostrarlo
        error_log("Error de conexión a BD: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
}
?>
