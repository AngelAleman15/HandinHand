<?php
/**
 * Script para crear la tabla de coordinación de intercambios
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Crear Tabla Coordinación</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Crear Tabla de Coordinación de Intercambios</h1>";

try {
    $pdo = getConnection();
    
    // Leer el archivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/coordinacion_intercambios.sql');
    
    echo "<h2>📄 Ejecutando SQL...</h2>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "<div class='success'>✅ Tabla 'coordinacion_intercambios' creada exitosamente</div>";
    
    // Verificar que se creó correctamente
    $stmt = $pdo->query("SHOW TABLES LIKE 'coordinacion_intercambios'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Verificación exitosa: La tabla existe en la base de datos</div>";
        
        // Mostrar estructura de la tabla
        $stmt = $pdo->query("DESCRIBE coordinacion_intercambios");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📋 Estructura de la tabla:</h3>";
        echo "<pre>";
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']} " . 
                 ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($col['Default'] !== null ? " DEFAULT {$col['Default']}" : '') . 
                 "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='error'>⚠️ La tabla no se encontró después de crearla</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error al crear la tabla: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "
        <hr>
        <p><a href='mensajeria.php'>← Volver a Mensajería</a></p>
    </div>
</body>
</html>";
?>
