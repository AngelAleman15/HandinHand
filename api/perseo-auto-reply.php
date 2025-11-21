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
        // Seleccionar un mensaje aleatorio y agregar identificador 🤖
        $mensaje_auto = '🤖 ' . str_replace('{username}', $current_user['username'], $mensajes_auto[array_rand($mensajes_auto)]);

        // Emitir el mensaje por Socket.IO en tiempo real
        $socketData = [
            'sender_id' => $current_user_id,
            'receiver_id' => $sender_id,
            'message' => $mensaje_auto,
            'is_perseo_auto' => 1,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $socketUrl = 'http://localhost:3001/api/emit-message'; // Ajusta la URL si tu servidor Socket.IO está en otro puerto/dominio
        $ch = curl_init($socketUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($socketData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $socketResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Si la respuesta no es 200, loguear el error
        if ($httpCode !== 200) {
            error_log('Error al emitir mensaje Perseo por Socket.IO. HTTP code: ' . $httpCode . ' Response: ' . $socketResponse);
        }

        // Insertar mensaje automático de Perseo (usar campo 'message')
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
