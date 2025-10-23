<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $data['sender_id'] ?? null;

if (!$sender_id) {
    echo json_encode(['status' => 'error', 'message' => 'sender_id requerido']);
    exit();
}

try {
    $pdo = getConnection();
    
    // Marcar todos los mensajes de este remitente como leídos
    $stmt = $pdo->prepare("
        UPDATE mensajes
        SET is_read = 1, read_at = NOW()
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ");
    
    $stmt->execute([$current_user_id, $sender_id]);
    $affected = $stmt->rowCount();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Mensajes marcados como leídos',
        'affected' => $affected
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al marcar mensajes como leídos: ' . $e->getMessage()
    ]);
}
