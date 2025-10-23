<?php
require_once '../api_base.php';
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getConversacion($id);
        } else {
            getConversaciones();
        }
        break;
        
    case 'POST':
        enviarMensaje();
        break;
        
    case 'PUT':
        marcarComoLeido($id);
        break;
        
    default:
        sendError('Método no soportado', 405);
}

/**
 * Obtener lista de conversaciones del usuario
 */
function getConversaciones() {
    $userId = requireAuth();
    
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT DISTINCT
                                    CASE 
                                        WHEN m.remitente_id = ? THEN m.destinatario_id 
                                        ELSE m.remitente_id 
                                    END as contacto_id,
                                    u.username as contacto_username,
                                    u.fullname as contacto_name,
                                    p.id as producto_id,
                                    p.nombre as producto_nombre,
                                    p.imagen as producto_imagen,
                                    MAX(m.created_at) as ultimo_mensaje_fecha,
                                    (SELECT mensaje FROM mensajes m2 
                                     WHERE (m2.remitente_id = ? AND m2.destinatario_id = contacto_id AND m2.producto_id = p.id)
                                        OR (m2.destinatario_id = ? AND m2.remitente_id = contacto_id AND m2.producto_id = p.id)
                                     ORDER BY m2.created_at DESC LIMIT 1) as ultimo_mensaje,
                                    COUNT(CASE WHEN m.destinatario_id = ? AND m.leido = 0 THEN 1 END) as mensajes_no_leidos
                               FROM mensajes m
                               JOIN usuarios u ON (u.id = CASE WHEN m.remitente_id = ? THEN m.destinatario_id ELSE m.remitente_id END)
                               JOIN productos p ON m.producto_id = p.id
                               WHERE m.remitente_id = ? OR m.destinatario_id = ?
                               GROUP BY contacto_id, p.id
                               ORDER BY ultimo_mensaje_fecha DESC");
        
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        $conversaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccess($conversaciones, 'Conversaciones obtenidas exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al obtener conversaciones: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener mensajes de una conversación específica
 */
function getConversacion($productoId) {
    $userId = requireAuth();
    $contactoId = isset($_GET['contacto']) ? (int)$_GET['contacto'] : null;
    
    if (!$contactoId) {
        sendError('ID de contacto requerido', 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Obtener información del producto
        $stmt = $pdo->prepare("SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name
                               FROM productos p
                               JOIN usuarios u ON p.user_id = u.id
                               WHERE p.id = ?");
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            sendError('Producto no encontrado', 404);
        }
        
        // Obtener mensajes de la conversación
        $stmt = $pdo->prepare("SELECT m.*, 
                                      ur.username as remitente_username,
                                      ur.fullname as remitente_name,
                                      ud.username as destinatario_username,
                                      ud.fullname as destinatario_name
                               FROM mensajes m
                               JOIN usuarios ur ON m.remitente_id = ur.id
                               JOIN usuarios ud ON m.destinatario_id = ud.id
                               WHERE m.producto_id = ? 
                                 AND ((m.remitente_id = ? AND m.destinatario_id = ?) 
                                   OR (m.remitente_id = ? AND m.destinatario_id = ?))
                               ORDER BY m.created_at ASC");
        
        $stmt->execute([$productoId, $userId, $contactoId, $contactoId, $userId]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marcar mensajes como leídos
        $stmt = $pdo->prepare("UPDATE mensajes SET leido = 1 
                               WHERE producto_id = ? AND destinatario_id = ? AND remitente_id = ?");
        $stmt->execute([$productoId, $userId, $contactoId]);
        
        sendSuccess([
            'producto' => $producto,
            'mensajes' => $mensajes
        ], 'Conversación obtenida exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al obtener conversación: ' . $e->getMessage(), 500);
    }
}

/**
 * Enviar un nuevo mensaje
 */
function enviarMensaje() {
    $userId = requireAuth();
    
    $data = getJsonInput();
    validateRequired($data, ['producto_id', 'destinatario_id', 'mensaje']);
    
    $productoId = (int)$data['producto_id'];
    $destinatarioId = (int)$data['destinatario_id'];
    $mensaje = sanitizeData($data['mensaje']);
    
    if (strlen($mensaje) > 1000) {
        sendError('El mensaje no puede exceder 1000 caracteres', 400);
    }
    
    if ($userId == $destinatarioId) {
        sendError('No puedes enviarte mensajes a ti mismo', 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Verificar que el producto existe
        $stmt = $pdo->prepare("SELECT id FROM productos WHERE id = ?");
        $stmt->execute([$productoId]);
        if (!$stmt->fetch()) {
            sendError('Producto no encontrado', 404);
        }
        
        // Verificar que el destinatario existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$destinatarioId]);
        if (!$stmt->fetch()) {
            sendError('Destinatario no encontrado', 404);
        }
        
        // Insertar mensaje
        $stmt = $pdo->prepare("INSERT INTO mensajes (producto_id, remitente_id, destinatario_id, mensaje, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        
        $success = $stmt->execute([$productoId, $userId, $destinatarioId, $mensaje]);
        
        if ($success) {
            $mensajeId = $pdo->lastInsertId();
            sendSuccess(['id' => $mensajeId], 'Mensaje enviado exitosamente', 201);
        } else {
            sendError('Error al enviar mensaje', 500);
        }
        
    } catch (Exception $e) {
        sendError('Error al enviar mensaje: ' . $e->getMessage(), 500);
    }
}

/**
 * Marcar mensajes como leídos
 */
function marcarComoLeido($productoId) {
    if (!$productoId) {
        sendError('ID de producto requerido', 400);
    }
    
    $userId = requireAuth();
    $data = getJsonInput();
    $remitenteId = isset($data['remitente_id']) ? (int)$data['remitente_id'] : null;
    
    if (!$remitenteId) {
        sendError('ID de remitente requerido', 400);
    }
    
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("UPDATE mensajes SET leido = 1 
                               WHERE producto_id = ? AND destinatario_id = ? AND remitente_id = ?");
        
        $success = $stmt->execute([$productoId, $userId, $remitenteId]);
        
        if ($success) {
            $affected = $stmt->rowCount();
            sendSuccess(['mensajes_marcados' => $affected], 'Mensajes marcados como leídos');
        } else {
            sendError('Error al marcar mensajes como leídos', 500);
        }
        
    } catch (Exception $e) {
        sendError('Error al marcar mensajes como leídos: ' . $e->getMessage(), 500);
    }
}
?>
