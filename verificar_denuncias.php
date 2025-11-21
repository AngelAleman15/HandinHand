<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "<h2>🔍 Verificación rápida de tabla denuncias</h2>";
    
    // Verificar tabla
    $stmt = $pdo->query("SHOW TABLES LIKE 'denuncias'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla 'denuncias' existe</p>";
        
        // Verificar si tiene datos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM denuncias");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total de registros: " . $result['total'] . "</p>";
        
        // Verificar estructura del campo motivo
        $stmt = $pdo->query("SHOW COLUMNS FROM denuncias LIKE 'motivo'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📝 Tipo del campo 'motivo': <code>" . $col['Type'] . "</code></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Tabla 'denuncias' NO existe</p>";
        echo "<p><a href='crear_tabla_denuncias.php' style='background: #6a994e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Crear tabla ahora</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
<hr>
<p><a href="ver-perfil.php">← Volver</a> | <a href="test_denuncias.php">🧪 Test completo</a></p>
