<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

validateMethod(['POST', 'GET']);

$user_id = requireAuth();
$data = getJsonInput();

error_log("[amistades.php] user_id: $user_id, action: " . ($data['action'] ?? '')); // log inicio
error_log("[amistades.php] input: " . json_encode($data));

try {
    // Logging para depuración
    error_log('Datos recibidos en amistades.php: ' . json_encode($data));
    error_log('Usuario actual en sesión: ' . json_encode($user_id));

    $pdo = getConnection();
<<<<<<< HEAD
    $action = $data['action'] ?? '';
=======
    error_log("[amistades.php] Conexión a BD exitosa");
    $action = $data['action'] ?? '';
    error_log("[amistades.php] Acción recibida: $action");
>>>>>>> 263ae01ba057b88ac719a4f10164613050e44276
    switch ($action) {
        case 'enviar_solicitud':
            error_log("[amistades.php] Acción: enviar_solicitud");
            validateRequired($data, ['receptor_id']);
            $receptor_id = intval($data['receptor_id']);
            error_log("[amistades.php] receptor_id: $receptor_id");
            if ($user_id == $receptor_id) {
                error_log("[amistades.php] Solicitud a sí mismo");
                sendError('No puedes enviarte una solicitud a ti mismo', 400);
            }
            $stmt = $pdo->prepare("
                SELECT id FROM amistades 
                WHERE (usuario1_id = ? AND usuario2_id = ?) 
                   OR (usuario1_id = ? AND usuario2_id = ?)
            ");
            $stmt->execute([$user_id, $receptor_id, $receptor_id, $user_id]);
            if ($stmt->fetch()) {
                error_log("[amistades.php] Ya son amigos");
                sendError('Ya son amigos', 400);
            }
            // Verificar si existe cualquier solicitud previa entre los usuarios
            $stmt = $pdo->prepare("
                SELECT id, estado FROM solicitudes_amistad 
                WHERE ((solicitante_id = ? AND receptor_id = ?) 
                    OR (solicitante_id = ? AND receptor_id = ?))
            ");
            $stmt->execute([$user_id, $receptor_id, $receptor_id, $user_id]);
            $solicitud = $stmt->fetch();
            if ($solicitud) {
                if ($solicitud['estado'] === 'pendiente') {
                    error_log("[amistades.php] Solicitud pendiente ya existe");
                    sendError('Ya existe una solicitud pendiente entre estos usuarios', 400);
                } elseif ($solicitud['estado'] === 'aceptada') {
                    error_log("[amistades.php] Solicitud ya aceptada");
                    sendError('Ya son amigos (solicitud ya aceptada)', 400);
                } elseif ($solicitud['estado'] === 'rechazada') {
                    error_log("[amistades.php] Solicitud rechazada previamente");
                    sendError('Ya existe una solicitud rechazada entre estos usuarios. Puedes intentar nuevamente más tarde.', 400);
                } else {
                    error_log("[amistades.php] Solicitud previa encontrada, estado: " . $solicitud['estado']);
                    sendError('Ya existe una solicitud previa entre estos usuarios', 400);
                }
            }
            // Si no existe ninguna relación ni solicitud previa, crear nueva solicitud
            $stmt = $pdo->prepare("
                INSERT INTO solicitudes_amistad (solicitante_id, receptor_id, estado)
                VALUES (?, ?, 'pendiente')
            ");
            $stmt->execute([$user_id, $receptor_id]);
            error_log("[amistades.php] Solicitud creada correctamente");
            sendSuccess(['solicitud_id' => $pdo->lastInsertId()], 'Solicitud enviada');
            break;
            
        case 'aceptar_solicitud':
            validateRequired($data, ['solicitante_id']);
            
            $solicitante_id = intval($data['solicitante_id']);
            
            // Verificar que la solicitud existe
            $stmt = $pdo->prepare("
                SELECT id FROM solicitudes_amistad 
                WHERE solicitante_id = ? AND receptor_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$solicitante_id, $user_id]);
            $solicitud = $stmt->fetch();
            
            if (!$solicitud) {
                sendError('Solicitud no encontrada', 404);
            }
            
            // Actualizar solicitud
            $stmt = $pdo->prepare("
                UPDATE solicitudes_amistad 
                SET estado = 'aceptada' 
                WHERE id = ?
            ");
            $stmt->execute([$solicitud['id']]);
            
            // Crear amistad (siempre menor ID primero)
            $usuario1 = min($solicitante_id, $user_id);
            $usuario2 = max($solicitante_id, $user_id);
            
            $stmt = $pdo->prepare("
                INSERT INTO amistades (usuario1_id, usuario2_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$usuario1, $usuario2]);
            
            // Actualizar estadísticas
            $stmt = $pdo->prepare("
                UPDATE estadisticas_usuario 
                SET total_amigos = total_amigos + 1 
                WHERE usuario_id IN (?, ?)
            ");
            $stmt->execute([$solicitante_id, $user_id]);
            
            sendSuccess(null, 'Solicitud aceptada');
            break;
            
        case 'rechazar_solicitud':
            validateRequired($data, ['solicitante_id']);
            
            $solicitante_id = intval($data['solicitante_id']);
            
            $stmt = $pdo->prepare("
                UPDATE solicitudes_amistad 
                SET estado = 'rechazada' 
                WHERE solicitante_id = ? AND receptor_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$solicitante_id, $user_id]);
            
            sendSuccess(null, 'Solicitud rechazada');
            break;
            
        case 'eliminar_amistad':
        case 'eliminar_amigo':
            validateRequired($data, ['amigo_id']);
            $amigo_id = intval($data['amigo_id']);
            // Eliminar amistad en ambas direcciones
            $stmt = $pdo->prepare("DELETE FROM amistades WHERE (usuario1_id = ? AND usuario2_id = ?) OR (usuario1_id = ? AND usuario2_id = ?)");
            $stmt->execute([$user_id, $amigo_id, $amigo_id, $user_id]);
<<<<<<< HEAD

            // Eliminar todas las solicitudes de amistad entre ambos usuarios (de cualquier estado)
            $stmt = $pdo->prepare("
                DELETE FROM solicitudes_amistad
                WHERE (solicitante_id = ? AND receptor_id = ?)
                   OR (solicitante_id = ? AND receptor_id = ?)
            ");
            $stmt->execute([$user_id, $amigo_id, $amigo_id, $user_id]);
            
=======
            // Eliminar todas las solicitudes previas entre ambos usuarios
            $stmt = $pdo->prepare("DELETE FROM solicitudes_amistad WHERE (solicitante_id = ? AND receptor_id = ?) OR (solicitante_id = ? AND receptor_id = ?)");
            $stmt->execute([$user_id, $amigo_id, $amigo_id, $user_id]);
            error_log("[amistades.php] Amistad eliminada entre $user_id y $amigo_id");
>>>>>>> 263ae01ba057b88ac719a4f10164613050e44276
            // Actualizar estadísticas
            $stmt = $pdo->prepare("UPDATE estadisticas_usuario SET total_amigos = GREATEST(total_amigos - 1, 0) WHERE usuario_id IN (?, ?)");
            $stmt->execute([$user_id, $amigo_id]);
            sendSuccess(null, 'Amistad eliminada');
            break;
            
        case 'listar_amigos':
            $stmt = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN a.usuario1_id = ? THEN u2.id
                        ELSE u1.id
                    END as id,
                    CASE 
                        WHEN a.usuario1_id = ? THEN u2.fullname
                        ELSE u1.fullname
                    END as nombre,
                    CASE 
                        WHEN a.usuario1_id = ? THEN u2.username
                        ELSE u1.username
                    END as username,
                    CASE 
                        WHEN a.usuario1_id = ? THEN u2.avatar_path
                        ELSE u1.avatar_path
                    END as avatar
                FROM amistades a
                JOIN usuarios u1 ON a.usuario1_id = u1.id
                JOIN usuarios u2 ON a.usuario2_id = u2.id
                WHERE a.usuario1_id = ? OR a.usuario2_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
            $amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess($amigos);
            break;
            
        case 'listar_solicitudes_pendientes':
            error_log('DEBUG listar_solicitudes_pendientes: user_id (receptor) = ' . $user_id);
            $stmt = $pdo->prepare("
                SELECT 
                    s.*,
                    u.fullname as solicitante_nombre,
                    u.username as solicitante_username,
                    u.avatar_path as solicitante_avatar
                FROM solicitudes_amistad s
                JOIN usuarios u ON s.solicitante_id = u.id
                WHERE s.receptor_id = ? AND s.estado = 'pendiente'
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('DEBUG listar_solicitudes_pendientes: resultado = ' . json_encode($solicitudes));
            sendSuccess($solicitudes);
            break;
            
        default:
            error_log("[amistades.php] Acción no válida: $action");
            sendError('Acción no válida', 400);
    }
    
} catch (PDOException $e) {
    error_log("[amistades.php] Error PDO: " . $e->getMessage());
    sendError('Error al procesar la solicitud', 500);
} catch (Throwable $e) {
    error_log("[amistades.php] Error general: " . $e->getMessage());
    sendError('Error inesperado en amistades.php', 500);
}
?>
