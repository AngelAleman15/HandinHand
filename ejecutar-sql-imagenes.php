<?php
/**
 * Script temporal para crear la tabla producto_imagenes
 * Ejecuta este archivo UNA VEZ visitando: http://localhost/MisTrabajos/HandinHand/ejecutar-sql-imagenes.php
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Leer el archivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/add_es_principal_to_imagenes.sql');
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "✅ <strong>Tabla 'producto_imagenes' creada exitosamente</strong><br><br>";
    
    // Verificar que la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'producto_imagenes'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Verificado: La tabla existe en la base de datos<br><br>";
        
        // Mostrar estructura de la tabla
        $stmt = $pdo->query("DESCRIBE producto_imagenes");
        echo "<h3>Estructura de la tabla:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #6a994e; color: white;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><br>";
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "🎉 <strong>¡Todo listo!</strong> Ahora puedes volver a <a href='crear-producto.php'>crear-producto.php</a> y la página funcionará correctamente.";
        echo "</p>";
        
        echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "⚠️ <strong>Importante:</strong> Puedes eliminar este archivo (ejecutar-sql-imagenes.php) después de ejecutarlo.";
        echo "</p>";
    } else {
        echo "❌ Error: La tabla no se creó correctamente";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "❌ <strong>Error al ejecutar el SQL:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "<br>";
    echo "<p><strong>Solución alternativa:</strong> Ejecuta manualmente el siguiente SQL en phpMyAdmin:</p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo htmlspecialchars(file_get_contents(__DIR__ . '/sql/add_es_principal_to_imagenes.sql'));
    echo "</pre>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        max-width: 1000px;
        margin: 40px auto;
        padding: 20px;
        background: #f8f9fa;
    }
    h3 {
        color: #2c3e50;
        margin-top: 20px;
    }
    table {
        width: 100%;
        background: white;
        margin: 10px 0;
    }
    td, th {
        text-align: left;
    }
    a {
        color: #6a994e;
        text-decoration: none;
        font-weight: bold;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
