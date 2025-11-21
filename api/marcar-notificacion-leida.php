<?php
/**
 * API: Marcar notificaciones como leídas
 * POST /api/marcar-notificacion-leida.php
 * Body: { "notificacion_id": 123 } o { "marcar_todas": true }
 */

require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

try {
    $db = getConnection();
    
    if (isset($data['marcar_todas']) && $data['marcar_todas']) {
        // Marcar todas como leídas
        $stmt = $db->prepare("
            UPDATE notificaciones 
            SET leida = 1, fecha_leida = NOW() 
            WHERE usuario_id = ? AND leida = 0
        ");
        $stmt->execute([$user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Todas las notificaciones marcadas como leídas']);
        
    } elseif (isset($data['notificacion_id'])) {
        // Marcar una específica
        $stmt = $db->prepare("
            UPDATE notificaciones 
            SET leida = 1, fecha_leida = NOW() 
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$data['notificacion_id'], $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros inválidos']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
