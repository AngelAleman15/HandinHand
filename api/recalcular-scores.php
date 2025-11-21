<?php
/**
 * API para recalcular scores del FYP automáticamente
 * Se ejecuta en segundo plano después de cada interacción
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    // Obtener conexión a la base de datos
    $pdo = getConnection();
    
    // Verificar que las tablas existan
    $check = $pdo->query("SHOW TABLES LIKE 'producto_scores'")->fetch();
    if (!$check) {
        throw new Exception('Tabla producto_scores no existe');
    }
    
    // Ejecutar el procedimiento almacenado para actualizar scores
    $pdo->exec("CALL actualizar_scores_productos()");
    
    // Opcional: Calcular similitudes (esto puede ser más pesado)
    // Descomentar si quieres que también recalcule similitudes
    // $pdo->exec("CALL calcular_similitudes_productos()");
    
    // Obtener estadísticas rápidas
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_productos,
            SUM(total_vistas) as vistas,
            SUM(total_guardados) as guardados,
            SUM(total_chats) as chats
        FROM producto_scores
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Scores recalculados',
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
