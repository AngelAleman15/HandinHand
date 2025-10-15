<?php
/**
 * API para editar mensajes propios
 * Solo el remitente puede editar sus mensajes
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../api/api_base.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

validateMethod(['POST']);
requireLogin();

$user = getCurrentUser();
$data = getJsonInput();

try {
    $pdo = getConnection();
    
    // Validar datos requeridos
    if (!isset($data['message_id']) || !isset($data['new_message'])) {
        sendError('message_id y new_message son requeridos', 400);
    }
    
    $messageId = (int)$data['message_id'];
    $newMessage = trim($data['new_message']);
    
    // Validar que el mensaje no esté vacío
    if (empty($newMessage)) {
        sendError('El mensaje no puede estar vacío', 400);
    }
    
    // Verificar que el mensaje exista y pertenezca al usuario
    $stmt = $pdo->prepare("
        SELECT sender_id, is_deleted 
        FROM mensajes 
        WHERE id = ?
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        sendError('Mensaje no encontrado', 404);
    }
    
    if ($message['sender_id'] != $user['id']) {
        sendError('No tienes permiso para editar este mensaje', 403);
    }
    
    if ($message['is_deleted']) {
        sendError('No puedes editar un mensaje eliminado', 400);
    }
    
    // Actualizar el mensaje
    $stmt = $pdo->prepare("
        UPDATE mensajes 
        SET mensaje = ?, edited_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$newMessage, $messageId]);
    
    sendSuccess([
        'message_id' => $messageId,
        'new_message' => $newMessage,
        'edited_at' => date('Y-m-d H:i:s')
    ], 'Mensaje editado correctamente');
    
} catch (Exception $e) {
    error_log("Error en edit-message.php: " . $e->getMessage());
    sendError('Error al editar el mensaje', 500);
}
