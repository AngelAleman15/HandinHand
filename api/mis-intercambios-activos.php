<?php
/**
 * API: Obtener intercambios activos del usuario
 * GET /api/mis-intercambios-activos.php
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

try {
    $db = getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            s.*,
            p.estado as propuesta_estado,
            u1.id as u1_id,
            u1.fullname as usuario1_nombre,
            u1.avatar_path as usuario1_avatar,
            u2.id as u2_id,
            u2.fullname as usuario2_nombre,
            u2.avatar_path as usuario2_avatar,
            prod1.nombre as producto_ofrecido_nombre,
            prod1.imagen as producto_ofrecido_imagen,
            prod2.nombre as producto_solicitado_nombre,
            prod2.imagen as producto_solicitado_imagen,
            (SELECT COUNT(*) FROM acciones_seguimiento WHERE seguimiento_id = s.id) as total_acciones
        FROM seguimiento_intercambios s
        JOIN propuestas_intercambio p ON s.propuesta_id = p.id
        JOIN usuarios u1 ON s.usuario1_id = u1.id
        JOIN usuarios u2 ON s.usuario2_id = u2.id
        LEFT JOIN productos prod1 ON s.producto_ofrecido_id = prod1.id
        LEFT JOIN productos prod2 ON s.producto_solicitado_id = prod2.id
        WHERE (s.usuario1_id = ? OR s.usuario2_id = ?)
          AND s.estado NOT IN ('completado', 'cancelado')
        ORDER BY s.updated_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $intercambios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada intercambio, obtener las últimas acciones
    foreach ($intercambios as &$intercambio) {
        $stmt = $db->prepare("
            SELECT 
                a.*,
                u.fullname as usuario_nombre
            FROM acciones_seguimiento a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.seguimiento_id = ?
            ORDER BY a.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$intercambio['id']]);
        $intercambio['acciones_recientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Determinar si el usuario actual es usuario1 o usuario2
        $intercambio['soy_usuario1'] = ($intercambio['usuario1_id'] == $user_id);
        $intercambio['otro_usuario_nombre'] = $intercambio['soy_usuario1'] ? $intercambio['usuario2_nombre'] : $intercambio['usuario1_nombre'];
        $intercambio['otro_usuario_id'] = $intercambio['soy_usuario1'] ? $intercambio['u2_id'] : $intercambio['u1_id'];
    }
    
    echo json_encode([
        'success' => true,
        'intercambios' => $intercambios
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
