<?php
/**
 * API para eliminar mensajes
 * - Si eres el remitente: elimina completamente (ambos usuarios)
 * - Si eres el receptor: solo oculta para ti
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
if (!$user || !isset($user['id'])) {
    sendError('No estás logueado o la sesión expiró', 401);
}
$data = getJsonInput();

try {
    // Asegurar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $pdo = getConnection();
    
    // Validar datos requeridos
    if (!isset($data['message_id'])) {
        sendError('message_id es requerido', 400);
    }
    
    $messageId = (int)$data['message_id'];
    $deleteForAll = isset($data['delete_for_all']) ? (bool)$data['delete_for_all'] : false;
    
    // Obtener información del mensaje
    $stmt = $pdo->prepare("
        SELECT sender_id, receiver_id, deleted_for, is_deleted 
        FROM mensajes 
        WHERE id = ?
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        sendError('Mensaje no encontrado', 404);
    }
    
    $isSender = ($message['sender_id'] == $user['id']);
    $deleteType = '';
    
    if ($isSender && $deleteForAll) {
        // Si es el remitente y quiere eliminar para todos
        $stmt = $pdo->prepare("
            UPDATE mensajes 
            SET is_deleted = TRUE 
            WHERE id = ?
        ");
        $stmt->execute([$messageId]);
        $deleteType = 'complete';
        
    } else {
        // Eliminar solo para el usuario actual (remitente o receptor)
        $deletedFor = $message['deleted_for'] ? json_decode($message['deleted_for'], true) : [];
        
        if (!in_array($user['id'], $deletedFor)) {
            $deletedFor[] = $user['id'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE mensajes 
            SET deleted_for = ? 
            WHERE id = ?
        ");
        $stmt->execute([json_encode($deletedFor), $messageId]);
        $deleteType = 'hide';
    }
    
    sendSuccess([
        'message_id' => $messageId,
        'delete_type' => $deleteType
    ], 'Mensaje eliminado correctamente');
    
} catch (Exception $e) {
    error_log("Error en delete-message.php: " . $e->getMessage());
    sendError('Error al eliminar el mensaje', 500);
}
