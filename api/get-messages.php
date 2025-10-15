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

    // Obtener mensajes entre los dos usuarios, excluyendo los eliminados por el usuario actual
    // y los mensajes enviados después de que el usuario eliminó el chat
    $query = "SELECT m.*, 
              CASE 
                  WHEN m.sender_id = :currentUser1 THEN true 
                  ELSE false 
              END as is_own_message,
              reply_msg.mensaje as reply_to_message,
              reply_sender.username as reply_to_username
              FROM mensajes m 
              LEFT JOIN mensajes reply_msg ON m.reply_to_message_id = reply_msg.id
              LEFT JOIN usuarios reply_sender ON reply_msg.sender_id = reply_sender.id
              LEFT JOIN mensajes_eliminados me ON m.id = me.mensaje_id AND me.user_id = :currentUser4
              LEFT JOIN chat_eliminado ce ON ce.user_id = :currentUser5 
                AND ce.other_user_id = :otherUser3 
                AND m.created_at < ce.eliminado_en
              WHERE ((m.sender_id = :currentUser2 AND m.receiver_id = :otherUser1)
                OR (m.sender_id = :otherUser2 AND m.receiver_id = :currentUser3))
              AND me.id IS NULL
              AND ce.id IS NULL
              ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':currentUser1' => $currentUserId,
        ':currentUser2' => $currentUserId,
        ':currentUser3' => $currentUserId,
        ':currentUser4' => $currentUserId,
        ':currentUser5' => $currentUserId,
        ':otherUser1' => $otherUserId,
        ':otherUser2' => $otherUserId,
        ':otherUser3' => $otherUserId
    ]);
    
    $messages = [];
    while ($row = $stmt->fetch()) {
        $messages[] = [
            'id' => $row['id'],
            'message' => $row['mensaje'],
            'timestamp' => $row['created_at'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'is_own_message' => (bool)$row['is_own_message'],
            'is_read' => (bool)($row['is_read'] ?? false),
            'is_perseo_auto' => isset($row['is_perseo_auto']) ? (bool)$row['is_perseo_auto'] : false,
            'reply_to_message_id' => $row['reply_to_message_id'],
            'reply_to_message' => $row['reply_to_message'],
            'reply_to_username' => $row['reply_to_username']
        ];
    }
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (Exception $e) {
    error_log("Error en get-messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener mensajes']);
}
?>