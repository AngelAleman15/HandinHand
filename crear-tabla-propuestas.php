<?php
// Script para crear la tabla de propuestas_intercambio
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Leer y ejecutar el archivo SQL
    $sql = file_get_contents('sql/propuestas_intercambio.sql');
    
    $pdo->exec($sql);
    
    echo "✅ Tabla 'propuestas_intercambio' creada exitosamente!<br>";
    echo "<br>Puedes cerrar esta ventana y probar el sistema de intercambios.";
    
} catch (Exception $e) {
    echo "❌ Error al crear la tabla: " . $e->getMessage();
}
?>
