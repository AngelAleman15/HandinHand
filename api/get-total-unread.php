<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'total' => 0]);
    exit();
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = getConnection();
    
    // Obtener total de mensajes no leídos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM mensajes
        WHERE receiver_id = ? AND is_read = 0
    ");
    
    $stmt->execute([$current_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => (int)$result['total']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'total' => 0,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
