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
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message']) || !isset($data['receiver_id'])) {
        throw new Exception('Datos incompletos');
    }

    $senderId = $_SESSION['user_id'];
    $receiverId = intval($data['receiver_id']);
    $message = trim($data['message']);
    $replyToMessageId = isset($data['reply_to_message_id']) ? intval($data['reply_to_message_id']) : null;

    // Insertar el mensaje
    $query = "INSERT INTO mensajes (sender_id, receiver_id, mensaje, reply_to_message_id, is_read, created_at) 
              VALUES (:sender_id, :receiver_id, :message, :reply_to, 0, NOW())";
              
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':sender_id' => $senderId,
        ':receiver_id' => $receiverId,
        ':message' => $message,
        ':reply_to' => $replyToMessageId
    ]);
    
    $messageId = $conn->lastInsertId();
    
    echo json_encode([
        'status' => 'success', 
        'message_id' => $messageId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error en save-message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar el mensaje']);
}
?>