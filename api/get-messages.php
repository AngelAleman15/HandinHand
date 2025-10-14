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
    
    // Obtener los parámetros
    $currentUserId = $_SESSION['user_id'];
    $otherUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if (!$otherUserId) {
        throw new Exception('ID de usuario no proporcionado');
    }

    // Obtener mensajes entre los dos usuarios
    $query = "SELECT m.*, 
              CASE 
                  WHEN m.sender_id = :currentUser1 THEN true 
                  ELSE false 
              END as is_own_message
              FROM mensajes m 
              WHERE (m.sender_id = :currentUser2 AND m.receiver_id = :otherUser1)
              OR (m.sender_id = :otherUser2 AND m.receiver_id = :currentUser3)
              ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':currentUser1' => $currentUserId,
        ':currentUser2' => $currentUserId,
        ':currentUser3' => $currentUserId,
        ':otherUser1' => $otherUserId,
        ':otherUser2' => $otherUserId
    ]);
    
    $messages = [];
    while ($row = $stmt->fetch()) {
        $messages[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'timestamp' => $row['created_at'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'is_own_message' => (bool)$row['is_own_message'],
            'is_read' => (bool)($row['is_read'] ?? false)
        ];
    }
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (Exception $e) {
    error_log("Error en get-messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener mensajes']);
}
?>