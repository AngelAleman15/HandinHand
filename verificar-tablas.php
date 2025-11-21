<?php
require_once 'config/database.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $db = getConnection();
    
    echo "=== ESTRUCTURA COMPLETA DE TABLAS RELEVANTES ===\n\n";
    
    $tablas = [
        'usuarios',
        'productos', 
        'mensajes',
        'propuestas_intercambio',
        'seguimiento_intercambios',
        'acciones_seguimiento',
        'notificaciones',
        'denuncias_intercambio'
    ];
    
    foreach ($tablas as $tabla) {
        $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$tabla':\n";
            
            $stmt = $db->query("DESCRIBE $tabla");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
            echo "\n";
        } else {
            echo "❌ Tabla '$tabla' NO EXISTE\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
