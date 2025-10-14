<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = getConnection();
    
    // Obtener conteo de mensajes no leídos por cada usuario
    $stmt = $pdo->prepare("
        SELECT 
            sender_id,
            COUNT(*) as unread_count
        FROM mensajes
        WHERE receiver_id = ? AND is_read = 0
        GROUP BY sender_id
    ");
    
    $stmt->execute([$current_user_id]);
    $unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a un objeto con user_id como clave
    $counts = [];
    foreach ($unread_counts as $row) {
        $counts[$row['sender_id']] = (int)$row['unread_count'];
    }
    
    echo json_encode([
        'status' => 'success',
        'unread_counts' => $counts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener mensajes no leídos: ' . $e->getMessage()
    ]);
}
