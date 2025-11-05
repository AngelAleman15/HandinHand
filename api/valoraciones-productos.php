<?php
// API para valoraciones de PRODUCTOS (no usuarios)
// Permite crear, obtener y eliminar valoraciones de productos

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

header('Content-Type: application/json; charset=utf-8');
validateMethod(['POST', 'GET', 'DELETE']);

try {
    $pdo = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener valoraciones de un producto
        $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : null;
        
        if (!$producto_id) {
            sendError('ID de producto requerido', 400);
        }
        
        // Obtener todas las valoraciones del producto
        $stmt = $pdo->prepare("
            SELECT 
                vp.*,
                u.fullname as usuario_nombre,
                u.username as usuario_username,
                u.avatar_path as usuario_avatar
            FROM valoraciones_productos vp
            JOIN usuarios u ON vp.usuario_id = u.id
            WHERE vp.producto_id = ?
            ORDER BY vp.created_at DESC
        ");
        $stmt->execute([$producto_id]);
        $valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas del producto
        $stmt = $pdo->prepare("
            SELECT promedio_estrellas, total_valoraciones 
            FROM productos 
            WHERE id = ?
        ");
        $stmt->execute([$producto_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccess([
            'valoraciones' => $valoraciones,
            'promedio' => floatval($stats['promedio_estrellas'] ?? 0),
            'total' => intval($stats['total_valoraciones'] ?? 0)
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear nueva valoración de producto
        $user_id = requireAuth();
        $data = getJsonInput();
        
        $action = $data['action'] ?? 'crear';
        
        if ($action === 'crear') {
            validateRequired($data, ['producto_id', 'puntuacion']);
            
            $producto_id = intval($data['producto_id']);
            $puntuacion = intval($data['puntuacion']);
            $comentario = isset($data['comentario']) && !empty(trim($data['comentario'])) 
                ? trim($data['comentario']) 
                : null;
            
            // Validar puntuación (1 a 5 estrellas)
            if ($puntuacion < 1 || $puntuacion > 5) {
                sendError('La puntuación debe ser entre 1 y 5 estrellas', 400);
            }
            
            // Verificar que el producto existe
            $stmt = $pdo->prepare("SELECT id, user_id FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                sendError('Producto no encontrado', 404);
            }
            
            // No permitir valorar su propio producto
            if ($producto['user_id'] == $user_id) {
                sendError('No puedes valorar tu propio producto', 400);
            }
            
            // Verificar si ya valoró este producto
            $stmt = $pdo->prepare("
                SELECT id FROM valoraciones_productos 
                WHERE producto_id = ? AND usuario_id = ?
            ");
            $stmt->execute([$producto_id, $user_id]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                // Actualizar valoración existente
                $stmt = $pdo->prepare("
                    UPDATE valoraciones_productos 
                    SET puntuacion = ?, comentario = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE producto_id = ? AND usuario_id = ?
                ");
                $stmt->execute([$puntuacion, $comentario, $producto_id, $user_id]);
                $mensaje = 'Tu valoración ha sido actualizada';
                $valoracion_id = $existe['id'];
            } else {
                // Crear nueva valoración
                $stmt = $pdo->prepare("
                    INSERT INTO valoraciones_productos (producto_id, usuario_id, puntuacion, comentario)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$producto_id, $user_id, $puntuacion, $comentario]);
                $valoracion_id = $pdo->lastInsertId();
                $mensaje = '¡Gracias por tu valoración!';
            }
            
            // Actualizar estadísticas del producto
            $stmt = $pdo->prepare("
                UPDATE productos 
                SET 
                    promedio_estrellas = (
                        SELECT ROUND(AVG(puntuacion), 1) 
                        FROM valoraciones_productos 
                        WHERE producto_id = ?
                    ),
                    total_valoraciones = (
                        SELECT COUNT(*) 
                        FROM valoraciones_productos 
                        WHERE producto_id = ?
                    )
                WHERE id = ?
            ");
            $stmt->execute([$producto_id, $producto_id, $producto_id]);
            
            sendSuccess(['valoracion_id' => $valoracion_id], $mensaje);
            
        } else {
            sendError('Acción no válida', 400);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Eliminar valoración
        $user_id = requireAuth();
        $data = getJsonInput();
        
        validateRequired($data, ['valoracion_id']);
        $valoracion_id = intval($data['valoracion_id']);
        
        // Verificar que la valoración existe y pertenece al usuario
        $stmt = $pdo->prepare("
            SELECT producto_id, usuario_id 
            FROM valoraciones_productos 
            WHERE id = ?
        ");
        $stmt->execute([$valoracion_id]);
        $valoracion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$valoracion) {
            sendError('Valoración no encontrada', 404);
        }
        
        if ($valoracion['usuario_id'] != $user_id) {
            sendError('No tienes permiso para eliminar esta valoración', 403);
        }
        
        $producto_id = $valoracion['producto_id'];
        
        // Eliminar la valoración
        $stmt = $pdo->prepare("DELETE FROM valoraciones_productos WHERE id = ?");
        $stmt->execute([$valoracion_id]);
        
        // Actualizar estadísticas del producto
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET 
                promedio_estrellas = (
                    SELECT COALESCE(ROUND(AVG(puntuacion), 1), 0.0) 
                    FROM valoraciones_productos 
                    WHERE producto_id = ?
                ),
                total_valoraciones = (
                    SELECT COUNT(*) 
                    FROM valoraciones_productos 
                    WHERE producto_id = ?
                )
            WHERE id = ?
        ");
        $stmt->execute([$producto_id, $producto_id, $producto_id]);
        
        sendSuccess(['deleted' => true], 'Valoración eliminada correctamente');
    }
    
} catch (PDOException $e) {
    error_log("Error en valoraciones-productos.php: " . $e->getMessage());
    
    $mensaje = 'Error al procesar la solicitud';
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $mensaje = 'Ya has valorado este producto';
    } elseif (strpos($e->getMessage(), 'Foreign key') !== false) {
        $mensaje = 'Producto no encontrado';
    }
    
    sendError($mensaje, 500);
}
