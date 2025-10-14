<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

try {
    // Recibir datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    $sender_ids = $data['sender_ids'] ?? [];
    
    if (empty($sender_ids)) {
        echo json_encode(['success' => false, 'message' => 'No se especificaron remitentes']);
        exit();
    }
    
    $pdo = getConnection();
    
    // Obtener información del usuario actual (Perseo responderá por él)
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Configurar charset para evitar problemas de codificación
    $pdo->exec("SET NAMES utf8mb4");
    
    // Mensajes automáticos variados para que no sea tan repetitivo
    $mensajes_auto = [
        "[Respuesta Automatica de Perseo]\n\nHola, {username} no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!",
        "[Respuesta Automatica de Perseo]\n\n{username} esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!",
        "[Respuesta Automatica de Perseo]\n\nActualmente {username} no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.",
    ];
    
    $respuestas_enviadas = 0;
    
    // Insertar respuesta automática para cada remitente
    foreach ($sender_ids as $sender_id) {
        // Seleccionar un mensaje aleatorio
        $mensaje_auto = str_replace('{username}', $current_user['username'], $mensajes_auto[array_rand($mensajes_auto)]);
        
        // Insertar mensaje automático de Perseo
        $stmt = $pdo->prepare("
            INSERT INTO mensajes (sender_id, receiver_id, message, is_perseo_auto, created_at)
            VALUES (?, ?, ?, 1, NOW())
        ");
        
        $stmt->execute([$current_user_id, $sender_id, $mensaje_auto]);
        $respuestas_enviadas++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Perseo ha enviado {$respuestas_enviadas} respuestas automáticas",
        'count' => $respuestas_enviadas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar respuestas automáticas: ' . $e->getMessage()
    ]);
}
