<?php
/**
 * API: Obtener notificaciones del usuario
 * GET /api/notificaciones.php
 * Query params: ?no_leidas=1 (opcional, solo no leídas)
 */

require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$solo_no_leidas = isset($_GET['no_leidas']) && $_GET['no_leidas'] == '1';

try {
    $db = getConnection();
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'notificaciones'");
    if ($stmt->rowCount() == 0) {
        // Tabla no existe, devolver array vacío
        echo json_encode([
            'success' => true,
            'notificaciones' => [],
            'total_no_leidas' => 0,
            'warning' => 'Tabla notificaciones no existe. Ejecuta el archivo SQL primero.'
        ]);
        exit;
    }
    
    $query = "
        SELECT 
            n.*,
            u.fullname as de_usuario_nombre,
            u.avatar_path as de_usuario_avatar
        FROM notificaciones n
        LEFT JOIN usuarios u ON n.de_usuario_id = u.id
        WHERE n.usuario_id = ?
    ";
    
    if ($solo_no_leidas) {
        $query .= " AND n.leida = 0";
    }
    
    $query .= " ORDER BY n.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener conteo de no leídas
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$user_id]);
    $total_no_leidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'notificaciones' => $notificaciones,
        'total_no_leidas' => $total_no_leidas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
