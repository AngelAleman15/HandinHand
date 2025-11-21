<?php
/**
 * Script para crear la tabla de denuncias en la base de datos
 * Ejecutar una sola vez para crear la estructura necesaria
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "<h2>🔧 Creando tabla de denuncias...</h2>";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/sql/crear_tabla_denuncias.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Separar las consultas SQL eliminando comentarios
    $lines = explode("\n", $sql);
    $statements = [];
    $currentStatement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorar líneas vacías y comentarios
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        $currentStatement .= ' ' . $line;
        
        // Si la línea termina con punto y coma, es el final de una declaración
        if (substr($line, -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    $executedQueries = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executedQueries++;
            } catch (PDOException $e) {
                // Si el error es que ya existe, continuamos
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ Tabla 'denuncias' creada correctamente ($executedQueries consultas ejecutadas)</p>";
    
    // Verificar que se creó
    $stmt = $pdo->query("SHOW TABLES LIKE 'denuncias'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Verificación: La tabla existe en la base de datos</p>";
        
        // Mostrar estructura de la tabla
        $stmt = $pdo->query("DESCRIBE denuncias");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Estructura de la tabla:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . ($col['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Error: La tabla no se creó</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error PDO: " . $e->getMessage() . "</p>";
    
    // Si el error es que ya existe, no es grave
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p style='color: orange;'>⚠️ La tabla ya existía. No hay problema.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='ver-perfil.php'>← Volver a perfiles</a> | <a href='test_denuncias.php'>🧪 Test del sistema</a></p>";
?>
