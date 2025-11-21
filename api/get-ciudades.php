<?php
/**
 * API para obtener ciudades de un departamento
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_GET['departamento_id']) || empty($_GET['departamento_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de departamento requerido'
        ]);
        exit;
    }
    
    $departamentoId = (int)$_GET['departamento_id'];
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            nombre, 
            es_capital 
        FROM ciudades 
        WHERE departamento_id = ? 
        ORDER BY es_capital DESC, nombre ASC
    ");
    
    $stmt->execute([$departamentoId]);
    $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'ciudades' => $ciudades
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-ciudades.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener ciudades'
    ]);
}
?>
