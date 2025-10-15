<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

try {
    $conn = getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $currentUserId = $_SESSION['user_id'];
    $otherUserId = isset($data['other_user_id']) ? intval($data['other_user_id']) : 0;
    
    if (!$otherUserId) {
        throw new Exception('ID de usuario no proporcionado');
    }

    // Registrar que este usuario eliminó el chat con el otro usuario
    $query = "INSERT INTO chat_eliminado (user_id, other_user_id, eliminado_en) 
              VALUES (:userId, :otherUserId, NOW())
              ON DUPLICATE KEY UPDATE eliminado_en = NOW()";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':userId' => $currentUserId,
        ':otherUserId' => $otherUserId
    ]);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Historial de chat eliminado'
    ]);

} catch (Exception $e) {
    error_log("Error en delete-chat-history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar historial']);
}
?>
