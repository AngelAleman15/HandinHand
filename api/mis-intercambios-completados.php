<?php
/**
 * API: Obtener intercambios completados del usuario
 * GET /api/mis-intercambios-completados.php
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
    
    // Obtener intercambios completados
    $stmt = $db->prepare("
        SELECT 
            s.*,
            u1.fullname as usuario1_nombre,
            u1.avatar_path as usuario1_avatar,
            u2.fullname as usuario2_nombre,
            u2.avatar_path as usuario2_avatar,
            p1.nombre as producto1_nombre,
            p1.imagen as producto1_imagen,
            p2.nombre as producto2_nombre,
            p2.imagen as producto2_imagen
        FROM seguimiento_intercambios s
        INNER JOIN usuarios u1 ON s.usuario1_id = u1.id
        INNER JOIN usuarios u2 ON s.usuario2_id = u2.id
        INNER JOIN productos p1 ON s.producto_ofrecido_id = p1.id
        INNER JOIN productos p2 ON s.producto_solicitado_id = p2.id
        WHERE (s.usuario1_id = ? OR s.usuario2_id = ?)
        AND s.estado = 'completado'
        ORDER BY s.fecha_completado DESC
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $intercambios_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar intercambios para determinar qué es "mi" producto y cuál es el "otro"
    $intercambios = [];
    foreach ($intercambios_raw as $int) {
        $es_usuario1 = ($int['usuario1_id'] == $user_id);
        
        $intercambio = [
            'id' => $int['id'],
            'propuesta_id' => $int['propuesta_id'],
            'estado' => $int['estado'],
            'fecha_completado' => $int['fecha_completado'],
            'lugar_encuentro' => $int['lugar_encuentro'],
            'fecha_encuentro' => $int['fecha_encuentro'],
            'created_at' => $int['created_at'],
            'usuario1_id' => $int['usuario1_id'],
            'usuario2_id' => $int['usuario2_id'],
            
            // Mi producto es el que ofrecí
            'mi_producto' => $es_usuario1 ? $int['producto1_nombre'] : $int['producto2_nombre'],
            'mi_producto_imagen' => $es_usuario1 ? $int['producto1_imagen'] : $int['producto2_imagen'],
            
            // Otro producto es el que recibí
            'otro_producto' => $es_usuario1 ? $int['producto2_nombre'] : $int['producto1_nombre'],
            'otro_producto_imagen' => $es_usuario1 ? $int['producto2_imagen'] : $int['producto1_imagen'],
            
            // Otro usuario
            'otro_usuario_id' => $es_usuario1 ? $int['usuario2_id'] : $int['usuario1_id'],
            'otro_usuario_nombre' => $es_usuario1 ? $int['usuario2_nombre'] : $int['usuario1_nombre'],
            'otro_usuario_avatar' => $es_usuario1 ? $int['usuario2_avatar'] : $int['usuario1_avatar']
        ];
        
        // Verificar si ya valoró (solo si existe la columna seguimiento_id en valoraciones)
        try {
            $stmt2 = $db->prepare("
                SELECT COUNT(*) as ya_valoro
                FROM valoraciones
                WHERE usuario_valorador_id = ? 
                AND usuario_valorado_id = ?
            ");
            $stmt2->execute([$user_id, $intercambio['otro_usuario_id']]);
            $result = $stmt2->fetch(PDO::FETCH_ASSOC);
            $intercambio['ya_valoro'] = $result['ya_valoro'] > 0;
        } catch (Exception $e) {
            // Si la tabla valoraciones no existe o no tiene la estructura esperada
            $intercambio['ya_valoro'] = false;
        }
        
        $intercambios[] = $intercambio;
    }
    
    echo json_encode([
        'success' => true,
        'intercambios' => $intercambios
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
