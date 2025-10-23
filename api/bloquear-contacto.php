<?php
// No iniciar sesión aquí, api_base.php lo hará en requireAuth()
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

// Asegurar que solo enviamos JSON
header('Content-Type: application/json; charset=utf-8');
validateMethod(['POST']);

try {
    $pdo = getConnection();
    $user_id = requireAuth();
    $data = getJsonInput();
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'eliminar_chat':
            validateRequired($data, ['contacto_id']);
            
            $contacto_id = intval($data['contacto_id']);
            
            // Verificar que no sean amigos
            $stmt = $pdo->prepare("
                SELECT id FROM amistades 
                WHERE (usuario1_id = ? AND usuario2_id = ?) 
                   OR (usuario1_id = ? AND usuario2_id = ?)
            ");
            $stmt->execute([$user_id, $contacto_id, $contacto_id, $user_id]);
            
            if ($stmt->fetch()) {
                sendError('No puedes eliminar el chat de un amigo. Primero debes eliminar la amistad.', 400);
            }
            
            // Eliminar todos los mensajes de esta conversación
            $stmt = $pdo->prepare("
                DELETE FROM mensajes 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
            ");
            $stmt->execute([$user_id, $contacto_id, $contacto_id, $user_id]);
            
            sendSuccess(['deleted' => true], 'Chat eliminado correctamente');
            break;
            
        case 'bloquear_contacto':
            validateRequired($data, ['contacto_id']);
            
            $contacto_id = intval($data['contacto_id']);
            
            // Por ahora solo eliminamos el chat
            // En el futuro se puede implementar una tabla de bloqueos
            $stmt = $pdo->prepare("
                DELETE FROM mensajes 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
            ");
            $stmt->execute([$user_id, $contacto_id, $contacto_id, $user_id]);
            
            sendSuccess(['blocked' => true], 'Contacto bloqueado y chat eliminado');
            break;
            
        default:
            sendError('Acción no válida', 400);
    }
    
} catch (PDOException $e) {
    error_log("Error en bloquear-contacto.php: " . $e->getMessage());
    sendError('Error al procesar la solicitud', 500);
}
?>
