<?php
/**
 * API: Crear seguimiento de intercambio al aceptar propuesta
 * POST /api/crear-seguimiento.php
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

if (!isset($data['propuesta_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta propuesta_id']);
    exit;
}

try {
    $db = getConnection();
    $db->beginTransaction();
    
    $propuesta_id = $data['propuesta_id'];
    $lugar_encuentro = $data['lugar'] ?? null;
    $fecha_encuentro = $data['fecha'] ?? null;
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;
    
    // Verificar que la propuesta existe y está pendiente
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.solicitante_id,
            p.receptor_id,
            p.producto_ofrecido_id,
            p.producto_solicitado_id,
            p.estado
        FROM propuestas_intercambio p
        WHERE p.id = ? AND p.estado = 'pendiente'
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        throw new Exception('Propuesta no encontrada o ya no está pendiente');
    }
    
    // Verificar que el usuario actual es el receptor de la propuesta
    if ($propuesta['receptor_id'] != $user_id) {
        throw new Exception('No tienes permiso para aceptar esta propuesta');
    }
    
    // Actualizar estado de la propuesta a 'aceptada'
    $stmt = $db->prepare("UPDATE propuestas_intercambio SET estado = 'aceptada' WHERE id = ?");
    $stmt->execute([$propuesta_id]);
    
    // Crear registro de seguimiento
    $stmt = $db->prepare("
        INSERT INTO seguimiento_intercambios 
        (propuesta_id, usuario1_id, usuario2_id, producto_ofrecido_id, producto_solicitado_id, 
         estado, lugar_encuentro, fecha_encuentro, lat, lng)
        VALUES (?, ?, ?, ?, ?, 'confirmado', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $propuesta_id,
        $propuesta['solicitante_id'],
        $propuesta['receptor_id'],
        $propuesta['producto_ofrecido_id'],
        $propuesta['producto_solicitado_id'],
        $lugar_encuentro,
        $fecha_encuentro,
        $lat,
        $lng
    ]);
    
    $seguimiento_id = $db->lastInsertId();
    
    // Vincular seguimiento con propuesta
    $stmt = $db->prepare("UPDATE propuestas_intercambio SET seguimiento_id = ? WHERE id = ?");
    $stmt->execute([$seguimiento_id, $propuesta_id]);
    
    // Actualizar estado de productos a 'reservado'
    $stmt = $db->prepare("UPDATE productos SET estado = 'reservado' WHERE id IN (?, ?)");
    $stmt->execute([$propuesta['producto_ofrecido_id'], $propuesta['producto_solicitado_id']]);
    
    // Crear notificación para el usuario que propuso
    $stmt = $db->prepare("
        SELECT fullname as nombre_completo
        FROM usuarios WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $usuario_acepta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        INSERT INTO notificaciones 
        (usuario_id, tipo, de_usuario_id, titulo, mensaje, icono, url, metadata)
        VALUES (?, 'intercambio_aceptado', ?, '¡Intercambio aceptado!', ?, 'fa-check-circle', ?, ?)
    ");
    $stmt->execute([
        $propuesta['solicitante_id'],
        $user_id,
        $usuario_acepta['nombre_completo'] . ' ha aceptado tu propuesta de intercambio',
        'mis-intercambios.php',
        json_encode(['propuesta_id' => $propuesta_id, 'seguimiento_id' => $seguimiento_id])
    ]);
    
    // Insertar mensaje en el chat notificando la aceptación
    $stmt = $db->prepare("
        INSERT INTO mensajes (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");
    
    $mensaje_sistema = '✅ Intercambio aceptado y confirmado. Lugar: ' . $lugar_encuentro . '. Fecha: ' . $fecha_encuentro . '. Puedes ver el seguimiento en "Mis Intercambios".';
    
    // Enviar a ambos usuarios
    $stmt->execute([$user_id, $propuesta['solicitante_id'], $mensaje_sistema]);
    $stmt->execute([$propuesta['solicitante_id'], $user_id, $mensaje_sistema]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'seguimiento_id' => $seguimiento_id,
        'message' => 'Intercambio aceptado y seguimiento creado'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
