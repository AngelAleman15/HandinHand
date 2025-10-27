<?php
require_once 'config/database.php';

echo "Iniciando migración del sistema de chat...\n";

try {
    $pdo = getConnection();
    
    // Leer el archivo SQL
    $sql = file_get_contents('update_chat_system.sql');
    
    // Dividir en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, '--') === 0) continue;
        
        try {
            $pdo->exec($query);
            echo "✓ Consulta ejecutada correctamente\n";
        } catch (PDOException $e) {
            echo "⚠ Advertencia: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Migración completada exitosamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
