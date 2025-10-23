<?php
require_once '../api_base.php';
require_once '../config/database.php';

validateMethod(['GET']);

$userId = requireAuth();

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT u.id, u.fullname, u.username, u.email, u.phone, u.birthdate, u.created_at,
                                  COUNT(p.id) as total_productos,
                                  COALESCE(AVG(v.puntuacion), 0) as promedio_valoraciones,
                                  COUNT(v.puntuacion) as total_valoraciones
                           FROM usuarios u
                           LEFT JOIN productos p ON u.id = p.user_id
                           LEFT JOIN valoraciones v ON u.id = v.usuario_id
                           WHERE u.id = ?
                           GROUP BY u.id");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Obtener productos recientes del usuario
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion, precio, imagen, categoria, estado, created_at 
                               FROM productos 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT 5");
        $stmt->execute([$userId]);
        $productosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $user['productos_recientes'] = $productosRecientes;
        
        sendSuccess($user, 'Perfil obtenido exitosamente');
    } else {
        sendError('Usuario no encontrado', 404);
    }
    
} catch (Exception $e) {
    sendError('Error al obtener perfil: ' . $e->getMessage(), 500);
}
?>
