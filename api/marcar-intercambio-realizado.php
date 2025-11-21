<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['propuesta_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Propuesta no especificada'
    ]);
    exit;
}

$propuesta_id = (int)$input['propuesta_id'];

try {
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    // Obtener información de la propuesta
    $stmt = $pdo->prepare("
        SELECT 
            p.solicitante_id,
            p.receptor_id,
            p.producto_solicitado_id,
            p.producto_ofrecido_id,
            p.estado,
            c.confirmado_por_solicitante,
            c.confirmado_por_receptor
        FROM propuestas_intercambio p
        LEFT JOIN coordinacion_intercambios c ON p.id = c.propuesta_id
        WHERE p.id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        throw new Exception('Propuesta no encontrada');
    }
    
    // Verificar que el usuario es parte de la propuesta
    if ($propuesta['solicitante_id'] != $user_id && $propuesta['receptor_id'] != $user_id) {
        throw new Exception('No autorizado');
    }
    
    // Verificar que ambos confirmaron la coordinación
    if (!$propuesta['confirmado_por_solicitante'] || !$propuesta['confirmado_por_receptor']) {
        throw new Exception('Ambos usuarios deben confirmar la coordinación antes de marcar como realizado');
    }
    
    // Marcar coordinación como realizada
    $stmt = $pdo->prepare("
        UPDATE coordinacion_intercambios 
        SET estado = 'realizado',
            updated_at = NOW()
        WHERE propuesta_id = ?
    ");
    $stmt->execute([$propuesta_id]);
    
    // Marcar propuesta como completada
    $stmt = $pdo->prepare("
        UPDATE propuestas_intercambio 
        SET estado = 'completada',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$propuesta_id]);
    
    // Marcar productos como intercambiados (ya no disponibles)
    $stmt = $pdo->prepare("
        UPDATE productos 
        SET disponible = FALSE,
            updated_at = NOW()
        WHERE id IN (?, ?)
    ");
    $stmt->execute([
        $propuesta['producto_solicitado_id'],
        $propuesta['producto_ofrecido_id']
    ]);
    
    // Enviar mensaje de felicitaciones al chat
    $mensaje_completado = json_encode([
        'tipo' => 'intercambio_completado',
        'mensaje' => '¡Intercambio completado exitosamente! 🎉🎊 Gracias por usar HandinHand'
    ], JSON_UNESCAPED_UNICODE);
    
    $other_user_id = ($user_id == $propuesta['solicitante_id']) 
        ? $propuesta['receptor_id'] 
        : $propuesta['solicitante_id'];
    
    $stmt = $pdo->prepare("
        INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
        VALUES (?, ?, ?, 'sistema', NOW())
    ");
    $stmt->execute([$user_id, $other_user_id, $mensaje_completado]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Intercambio marcado como realizado exitosamente'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en marcar-intercambio-realizado.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
