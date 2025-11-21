<?php
/**
 * API: Gestionar acciones de seguimiento de intercambio
 * POST /api/accion-seguimiento.php
 * Acciones: en_camino, demorado, mensaje_rapido, entregado, cancelar, denunciar
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
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['seguimiento_id']) || !isset($data['accion'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros: seguimiento_id y accion']);
    exit;
}

try {
    $db = getConnection();
    $db->beginTransaction();
    
    $seguimiento_id = $data['seguimiento_id'];
    $accion = $data['accion'];
    $mensaje = $data['mensaje'] ?? null;
    $metadata = $data['metadata'] ?? null;
    
    // Obtener datos del seguimiento
    $stmt = $db->prepare("
        SELECT 
            s.*,
            u1.fullname as nombre_usuario1,
            u2.fullname as nombre_usuario2,
            p1.nombre as producto_ofrecido,
            p2.nombre as producto_solicitado
        FROM seguimiento_intercambios s
        JOIN usuarios u1 ON s.usuario1_id = u1.id
        JOIN usuarios u2 ON s.usuario2_id = u2.id
        JOIN productos p1 ON s.producto_ofrecido_id = p1.id
        JOIN productos p2 ON s.producto_solicitado_id = p2.id
        WHERE s.id = ?
    ");
    $stmt->execute([$seguimiento_id]);
    $seguimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguimiento) {
        throw new Exception('Seguimiento no encontrado');
    }
    
    // Verificar que el usuario es parte del intercambio
    if ($seguimiento['usuario1_id'] != $user_id && $seguimiento['usuario2_id'] != $user_id) {
        throw new Exception('No tienes permiso para realizar acciones en este intercambio');
    }
    
    // Determinar el otro usuario
    $otro_usuario_id = ($seguimiento['usuario1_id'] == $user_id) ? $seguimiento['usuario2_id'] : $seguimiento['usuario1_id'];
    $es_usuario1 = ($seguimiento['usuario1_id'] == $user_id);
    
    $notificacion_titulo = '';
    $notificacion_mensaje = '';
    $notificacion_tipo = '';
    $notificacion_icono = 'fa-bell';
    $nuevo_estado = null;
    
    switch ($accion) {
        case 'en_camino':
            // Actualizar estado según quién está en camino
            if ($es_usuario1) {
                $nuevo_estado = ($seguimiento['estado'] == 'en_camino_usuario2') ? 'en_camino_ambos' : 'en_camino_usuario1';
            } else {
                $nuevo_estado = ($seguimiento['estado'] == 'en_camino_usuario1') ? 'en_camino_ambos' : 'en_camino_usuario2';
            }
            
            $stmt = $db->prepare("UPDATE seguimiento_intercambios SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $seguimiento_id]);
            
            $notificacion_tipo = 'en_camino';
            $notificacion_titulo = '🚗 En camino al encuentro';
            $notificacion_mensaje = ($es_usuario1 ? $seguimiento['nombre_usuario1'] : $seguimiento['nombre_usuario2']) . ' está en camino al punto de encuentro';
            $notificacion_icono = 'fa-car';
            break;
            
        case 'demorado':
            $notificacion_tipo = 'demorado';
            $notificacion_titulo = '⏰ Demora en el encuentro';
            $notificacion_mensaje = ($es_usuario1 ? $seguimiento['nombre_usuario1'] : $seguimiento['nombre_usuario2']) . ' reportó: ' . ($mensaje ?? 'Estoy demorado');
            $notificacion_icono = 'fa-clock';
            break;
            
        case 'mensaje_rapido':
            $notificacion_tipo = 'demorado'; // Reutilizamos el tipo
            $notificacion_titulo = '💬 Mensaje del intercambio';
            $notificacion_mensaje = ($es_usuario1 ? $seguimiento['nombre_usuario1'] : $seguimiento['nombre_usuario2']) . ': ' . $mensaje;
            $notificacion_icono = 'fa-comment';
            break;
            
        case 'entregado':
            // Marcar como entregado por el usuario actual
            $campo_entrega = $es_usuario1 ? 'usuario1_entregado' : 'usuario2_entregado';
            $stmt = $db->prepare("UPDATE seguimiento_intercambios SET $campo_entrega = 1 WHERE id = ?");
            $stmt->execute([$seguimiento_id]);
            
            // Verificar si ambos marcaron como entregado
            $stmt = $db->prepare("SELECT usuario1_entregado, usuario2_entregado FROM seguimiento_intercambios WHERE id = ?");
            $stmt->execute([$seguimiento_id]);
            $entrega = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($entrega['usuario1_entregado'] && $entrega['usuario2_entregado']) {
                // AMBOS CONFIRMARON - COMPLETAR INTERCAMBIO
                $stmt = $db->prepare("
                    UPDATE seguimiento_intercambios 
                    SET estado = 'completado', fecha_completado = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$seguimiento_id]);
                
                // Actualizar propuesta
                $stmt = $db->prepare("UPDATE propuestas_intercambio SET estado = 'completada' WHERE id = ?");
                $stmt->execute([$seguimiento['propuesta_id']]);
                
                // MARCAR PRODUCTOS COMO INTERCAMBIADOS (no eliminar por las FK)
                $stmt = $db->prepare("UPDATE productos SET estado = 'intercambiado' WHERE id IN (?, ?)");
                $stmt->execute([$seguimiento['producto_ofrecido_id'], $seguimiento['producto_solicitado_id']]);
                
                // Notificar a ambos usuarios
                $stmt = $db->prepare("
                    INSERT INTO notificaciones 
                    (usuario_id, tipo, titulo, mensaje, icono, url, metadata)
                    VALUES (?, 'intercambio_completado', '✅ Intercambio completado', 'El intercambio se ha completado exitosamente. Los productos han sido marcados como intercambiados.', 'fa-check-circle', 'mis-intercambios.php', ?)
                ");
                $meta = json_encode(['seguimiento_id' => $seguimiento_id]);
                $stmt->execute([$seguimiento['usuario1_id'], $meta]);
                $stmt->execute([$seguimiento['usuario2_id'], $meta]);
                
                $notificacion_tipo = 'intercambio_completado';
                $notificacion_titulo = '🎉 ¡Intercambio completado!';
                $notificacion_mensaje = 'El intercambio ha sido completado exitosamente';
                $notificacion_icono = 'fa-trophy';
                
            } else {
                // Solo uno confirmó
                $nuevo_estado = $es_usuario1 ? 'entregado_usuario1' : 'entregado_usuario2';
                $stmt = $db->prepare("UPDATE seguimiento_intercambios SET estado = ? WHERE id = ?");
                $stmt->execute([$nuevo_estado, $seguimiento_id]);
                
                $notificacion_tipo = 'entregado';
                $notificacion_titulo = '📦 Producto entregado';
                $notificacion_mensaje = ($es_usuario1 ? $seguimiento['nombre_usuario1'] : $seguimiento['nombre_usuario2']) . ' confirmó la entrega. Confirma tú también para completar el intercambio.';
                $notificacion_icono = 'fa-box-check';
            }
            break;
            
        case 'cancelar':
            $stmt = $db->prepare("UPDATE seguimiento_intercambios SET estado = 'cancelado' WHERE id = ?");
            $stmt->execute([$seguimiento_id]);
            
            $stmt = $db->prepare("UPDATE propuestas_intercambio SET estado = 'rechazada' WHERE id = ?");
            $stmt->execute([$seguimiento['propuesta_id']]);
            
            // Liberar productos
            $stmt = $db->prepare("UPDATE productos SET estado = 'disponible' WHERE id IN (?, ?)");
            $stmt->execute([$seguimiento['producto_ofrecido_id'], $seguimiento['producto_solicitado_id']]);
            
            $notificacion_tipo = 'intercambio_aceptado'; // Reutilizamos
            $notificacion_titulo = '❌ Intercambio cancelado';
            $notificacion_mensaje = ($es_usuario1 ? $seguimiento['nombre_usuario1'] : $seguimiento['nombre_usuario2']) . ' canceló el intercambio';
            $notificacion_icono = 'fa-times-circle';
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    // Registrar la acción
    $stmt = $db->prepare("
        INSERT INTO acciones_seguimiento (seguimiento_id, usuario_id, tipo, mensaje, metadata)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $seguimiento_id,
        $user_id,
        $accion,
        $mensaje,
        $metadata ? json_encode($metadata) : null
    ]);
    
    // Crear notificación para el otro usuario (excepto si es completado, ya se creó arriba)
    if ($accion != 'entregado' || !($entrega['usuario1_entregado'] && $entrega['usuario2_entregado'])) {
        $stmt = $db->prepare("
            INSERT INTO notificaciones 
            (usuario_id, tipo, de_usuario_id, titulo, mensaje, icono, url, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $otro_usuario_id,
            $notificacion_tipo,
            $user_id,
            $notificacion_titulo,
            $notificacion_mensaje,
            $notificacion_icono,
            'mis-intercambios.php',
            json_encode(['seguimiento_id' => $seguimiento_id, 'accion' => $accion])
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Acción registrada correctamente',
        'nuevo_estado' => $nuevo_estado ?? $seguimiento['estado']
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
