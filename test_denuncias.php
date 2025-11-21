<?php
/**
 * Script de prueba para verificar el sistema de denuncias
 */
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "<h2>🧪 Test del Sistema de Denuncias</h2>";
    echo "<hr>";
    
    // 1. Verificar que la tabla existe
    echo "<h3>1️⃣ Verificando tabla 'denuncias'...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'denuncias'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ La tabla existe</p>";
        
        // Mostrar estructura
        $stmt = $pdo->query("DESCRIBE denuncias");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ La tabla NO existe</p>";
        echo "<p><a href='crear_tabla_denuncias.php'>➡️ Crear tabla ahora</a></p>";
    }
    
    echo "<hr>";
    
    // 2. Verificar motivos válidos
    echo "<h3>2️⃣ Motivos válidos de denuncia:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM denuncias LIKE 'motivo'");
    $motivo_col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($motivo_col) {
        echo "<p><strong>Tipo de campo:</strong> " . $motivo_col['Type'] . "</p>";
        
        // Extraer valores del ENUM
        if (preg_match("/^enum\('(.+)'\)$/i", $motivo_col['Type'], $matches)) {
            $motivos = explode("','", $matches[1]);
            
            echo "<ul>";
            foreach ($motivos as $motivo) {
                echo "<li><code>" . $motivo . "</code></li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<hr>";
    
    // 3. Contar denuncias existentes
    echo "<h3>3️⃣ Denuncias en el sistema:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM denuncias");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total de denuncias:</strong> " . $result['total'] . "</p>";
    
    // Mostrar últimas 5 denuncias
    if ($result['total'] > 0) {
        $stmt = $pdo->query("
            SELECT d.*, 
                   u1.fullname as denunciante, 
                   u2.fullname as denunciado
            FROM denuncias d
            LEFT JOIN usuarios u1 ON d.denunciante_id = u1.id
            LEFT JOIN usuarios u2 ON d.denunciado_id = u2.id
            ORDER BY d.created_at DESC
            LIMIT 5
        ");
        $denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Últimas 5 denuncias:</h4>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Denunciante</th><th>Denunciado</th><th>Motivo</th><th>Estado</th><th>Fecha</th></tr>";
        
        foreach ($denuncias as $d) {
            echo "<tr>";
            echo "<td>" . $d['id'] . "</td>";
            echo "<td>" . ($d['denunciante'] ?? 'N/A') . "</td>";
            echo "<td>" . ($d['denunciado'] ?? 'N/A') . "</td>";
            echo "<td><strong>" . $d['motivo'] . "</strong></td>";
            echo "<td>" . $d['estado'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($d['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p>✅ <strong>Test completado</strong></p>";
    echo "<p><a href='ver-perfil.php'>← Volver a perfiles</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
