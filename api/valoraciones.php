<?php
// Desactivar display de errores para evitar contaminar JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// No iniciar sesión aquí, api_base.php lo hará en requireAuth()
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

// Asegurar que solo enviamos JSON
header('Content-Type: application/json; charset=utf-8');
validateMethod(['POST', 'GET']);

try {
    $pdo = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Listar valoraciones de un usuario
        $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
        
        if (!$usuario_id) {
            sendError('ID de usuario requerido', 400);
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                v.*,
                u.fullname as valorador_nombre,
                u.username as valorador_username,
                u.avatar_path as valorador_avatar
            FROM valoraciones v
            JOIN usuarios u ON v.valorador_id = u.id
            WHERE v.usuario_id = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$usuario_id]);
        $valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular promedio
        $stmt = $pdo->prepare("SELECT AVG(puntuacion) as promedio FROM valoraciones WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $promedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0;
        
        sendSuccess([
            'valoraciones' => $valoraciones,
            'promedio' => round($promedio, 1),
            'total' => count($valoraciones)
        ]);
    } else {
        // POST - Crear o actualizar valoración
        $user_id = requireAuth();
        $data = getJsonInput();
        
        // Log para debug
        error_log("Valoraciones POST - Data recibida: " . json_encode($data));
        
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'crear':
                validateRequired($data, ['usuario_id', 'puntuacion']);
                
                $usuario_id = intval($data['usuario_id']);
                $puntuacion = floatval($data['puntuacion']);
                $comentario = isset($data['comentario']) && !empty($data['comentario']) 
                    ? trim($data['comentario']) 
                    : null;
                
                // Validar puntuación (1 a 5, números enteros)
                if ($puntuacion < 1 || $puntuacion > 5) {
                    sendError('Puntuación debe ser entre 1 y 5', 400);
                }
                
                // No valorarse a sí mismo
                if ($user_id == $usuario_id) {
                    sendError('No puedes valorarte a ti mismo', 400);
                }
                
                // Crear nueva valoración (permitir múltiples)
                $stmt = $pdo->prepare("
                    INSERT INTO valoraciones (usuario_id, valorador_id, puntuacion, comentario)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$usuario_id, $user_id, $puntuacion, $comentario]);
                $valoracion_id = $pdo->lastInsertId();
                $mensaje = '¡Gracias por tu valoración! Tu opinión ayuda a la comunidad.';
                
                // Actualizar estadísticas del usuario valorado
                $stmt = $pdo->prepare("
                    UPDATE estadisticas_usuario 
                    SET 
                        promedio_valoracion = (SELECT AVG(puntuacion) FROM valoraciones WHERE usuario_id = ?),
                        total_valoraciones = (SELECT COUNT(*) FROM valoraciones WHERE usuario_id = ?)
                    WHERE usuario_id = ?
                ");
                $stmt->execute([$usuario_id, $usuario_id, $usuario_id]);
                
                sendSuccess(['valoracion_id' => $valoracion_id], $mensaje);
                break;
            
            case 'eliminar':
                validateRequired($data, ['valoracion_id']);
                
                $valoracion_id = intval($data['valoracion_id']);
                
                // Verificar que la valoración existe y pertenece al usuario actual
                $stmt = $pdo->prepare("
                    SELECT usuario_id, valorador_id FROM valoraciones WHERE id = ?
                ");
                $stmt->execute([$valoracion_id]);
                $valoracion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$valoracion) {
                    sendError('Valoración no encontrada', 404);
                }
                
                // Solo el autor puede eliminar su propia valoración
                if ($valoracion['valorador_id'] != $user_id) {
                    sendError('No tienes permiso para eliminar esta valoración', 403);
                }
                
                $usuario_valorado_id = $valoracion['usuario_id'];
                
                // Eliminar la valoración
                $stmt = $pdo->prepare("DELETE FROM valoraciones WHERE id = ?");
                $stmt->execute([$valoracion_id]);
                
                // Actualizar estadísticas del usuario valorado
                $stmt = $pdo->prepare("
                    UPDATE estadisticas_usuario 
                    SET 
                        promedio_valoracion = (SELECT COALESCE(AVG(puntuacion), 0) FROM valoraciones WHERE usuario_id = ?),
                        total_valoraciones = (SELECT COUNT(*) FROM valoraciones WHERE usuario_id = ?)
                    WHERE usuario_id = ?
                ");
                $stmt->execute([$usuario_valorado_id, $usuario_valorado_id, $usuario_valorado_id]);
                
                sendSuccess(['deleted' => true], 'Valoración eliminada correctamente');
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
    }
    
} catch (PDOException $e) {
    error_log("Error en valoraciones.php: " . $e->getMessage());
    
    // Mensaje más específico según el tipo de error
    $mensaje = 'Error al procesar la solicitud';
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $mensaje = 'Ya has valorado a este usuario anteriormente';
    } elseif (strpos($e->getMessage(), 'Foreign key') !== false) {
        $mensaje = 'Usuario no encontrado';
    }
    
    sendError($mensaje, 500);
}

