<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $other_user_id = $_GET['user_id'] ?? null;
    $propuesta_id = $_GET['propuesta_id'] ?? null;
    
    $pdo = getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Si se especifica propuesta_id, obtener esa propuesta específica
    if ($propuesta_id) {
        $stmt = $pdo->prepare("
            SELECT p.*,
                   ps.nombre as producto_solicitado_nombre, 
                   ps.imagen as producto_solicitado_imagen,
                   ps.precio_intercambio as producto_solicitado_precio,
                   po.nombre as producto_ofrecido_nombre, 
                   po.imagen as producto_ofrecido_imagen,
                   po.precio_intercambio as producto_ofrecido_precio,
                   u_sol.fullname as solicitante_nombre,
                   u_sol.avatar as solicitante_avatar,
                   u_rec.fullname as receptor_nombre,
                   u_rec.avatar as receptor_avatar
            FROM propuestas_intercambio p
            JOIN productos ps ON p.producto_solicitado_id = ps.id
            JOIN productos po ON p.producto_ofrecido_id = po.id
            JOIN usuarios u_sol ON p.solicitante_id = u_sol.id
            JOIN usuarios u_rec ON p.receptor_id = u_rec.id
            WHERE p.id = ?
        ");
        $stmt->execute([$propuesta_id]);
        $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($propuesta) {
            $propuesta['es_receptor'] = ($propuesta['receptor_id'] == $user_id);
            $propuesta['es_solicitante'] = ($propuesta['solicitante_id'] == $user_id);
            
            echo json_encode([
                'status' => 'success',
                'propuestas' => [$propuesta]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Propuesta no encontrada'
            ]);
        }
        exit;
    }
    
    // Si no se especifica propuesta_id, buscar por user_id
    if (!$other_user_id) {
        throw new Exception('Usuario no especificado');
    }
    
    // Obtener propuestas pendientes entre estos dos usuarios
    $stmt = $pdo->prepare("
        SELECT p.*,
               ps.nombre as producto_solicitado_nombre, 
               ps.imagen as producto_solicitado_imagen,
               ps.precio_intercambio as producto_solicitado_precio,
               po.nombre as producto_ofrecido_nombre, 
               po.imagen as producto_ofrecido_imagen,
               po.precio_intercambio as producto_ofrecido_precio,
               u_sol.fullname as solicitante_nombre,
               u_sol.avatar as solicitante_avatar,
               u_rec.fullname as receptor_nombre,
               u_rec.avatar as receptor_avatar
        FROM propuestas_intercambio p
        JOIN productos ps ON p.producto_solicitado_id = ps.id
        JOIN productos po ON p.producto_ofrecido_id = po.id
        JOIN usuarios u_sol ON p.solicitante_id = u_sol.id
        JOIN usuarios u_rec ON p.receptor_id = u_rec.id
        WHERE ((p.solicitante_id = ? AND p.receptor_id = ?) 
               OR (p.solicitante_id = ? AND p.receptor_id = ?))
          AND p.estado = 'pendiente'
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($propuesta) {
        // Determinar si el usuario actual es el receptor
        $propuesta['es_receptor'] = ($propuesta['receptor_id'] == $user_id);
        $propuesta['es_solicitante'] = ($propuesta['solicitante_id'] == $user_id);
    }
    
    echo json_encode([
        'success' => true,
        'propuesta' => $propuesta
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener-propuestas-pendientes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
