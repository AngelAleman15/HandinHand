<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'handinhand');
define('DB_USER', 'root');
define('DB_PASS', '');

// Función para conectar a la base de datos
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
?>
