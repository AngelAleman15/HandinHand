<?php
require_once 'config/database.php';

echo "Verificando estructura de la tabla mensajes...\n\n";

try {
    $pdo = getConnection();
    
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE mensajes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas actuales en la tabla 'mensajes':\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s %-10s\n", "Campo", "Tipo", "Nulo", "Clave", "Default");
    echo str_repeat("-", 80) . "\n";
    
    $requiredColumns = ['sender_id', 'receiver_id', 'message', 'is_read', 'read_at'];
    $foundColumns = [];
    
    foreach ($columns as $column) {
        printf("%-20s %-20s %-10s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?? 'NULL'
        );
        
        if (in_array($column['Field'], $requiredColumns)) {
            $foundColumns[] = $column['Field'];
        }
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Verificar columnas requeridas
    echo "Verificación de columnas requeridas:\n";
    foreach ($requiredColumns as $col) {
        $status = in_array($col, $foundColumns) ? "✓ PRESENTE" : "✗ FALTANTE";
        echo "$status - $col\n";
    }
    
    // Verificar índices
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Índices en la tabla 'mensajes':\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("SHOW INDEX FROM mensajes");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($indexes as $index) {
        printf("%-20s %-20s %-20s\n", 
            $index['Key_name'], 
            $index['Column_name'],
            $index['Non_unique'] ? 'Non-unique' : 'Unique'
        );
    }
    
    // Contar mensajes
    echo "\n" . str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mensajes");
    $count = $stmt->fetch();
    echo "Total de mensajes en la tabla: " . $count['total'] . "\n";
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as unread FROM mensajes WHERE is_read = 0");
        $unread = $stmt->fetch();
        echo "Mensajes no leídos: " . $unread['unread'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as `read_count` FROM mensajes WHERE is_read = 1");
        $read = $stmt->fetch();
        echo "Mensajes leídos: " . $read['read_count'] . "\n";
    }
    
    echo "\n✅ Verificación completada!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
