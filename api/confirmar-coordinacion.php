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
    
    // Obtener información de la propuesta y coordinación
    $stmt = $pdo->prepare("
        SELECT 
            p.solicitante_id,
            p.receptor_id,
            c.confirmado_por_solicitante,
            c.confirmado_por_receptor
        FROM propuestas_intercambio p
        INNER JOIN coordinacion_intercambios c ON p.id = c.propuesta_id
        WHERE p.id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Propuesta o coordinación no encontrada'
        ]);
        exit;
    }
    
    // Verificar que el usuario es parte de la propuesta
    if ($data['solicitante_id'] != $user_id && $data['receptor_id'] != $user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }
    
    // Actualizar confirmación según quien sea el usuario
    if ($user_id == $data['solicitante_id']) {
        $stmt = $pdo->prepare("
            UPDATE coordinacion_intercambios 
            SET confirmado_por_solicitante = TRUE,
                updated_at = NOW()
            WHERE propuesta_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE coordinacion_intercambios 
            SET confirmado_por_receptor = TRUE,
                updated_at = NOW()
            WHERE propuesta_id = ?
        ");
    }
    $stmt->execute([$propuesta_id]);
    
    // Verificar si ambos confirmaron
    $stmt = $pdo->prepare("
        SELECT confirmado_por_solicitante, confirmado_por_receptor 
        FROM coordinacion_intercambios 
        WHERE propuesta_id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $confirmaciones = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($confirmaciones['confirmado_por_solicitante'] && $confirmaciones['confirmado_por_receptor']) {
        // Ambos confirmaron, actualizar estado
        $stmt = $pdo->prepare("
            UPDATE coordinacion_intercambios 
            SET estado = 'confirmado',
                updated_at = NOW()
            WHERE propuesta_id = ?
        ");
        $stmt->execute([$propuesta_id]);
        
        // También actualizar la propuesta a "aceptada"
        $stmt = $pdo->prepare("
            UPDATE propuestas_intercambio 
            SET estado = 'aceptada',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$propuesta_id]);
        
        // Enviar mensaje de confirmación al chat
        $mensaje_confirmacion = json_encode([
            'tipo' => 'intercambio_confirmado',
            'mensaje' => '¡Ambos han confirmado el intercambio! 🎉'
        ], JSON_UNESCAPED_UNICODE);
        
        $other_user_id = ($user_id == $data['solicitante_id']) 
            ? $data['receptor_id'] 
            : $data['solicitante_id'];
        
        $stmt = $pdo->prepare("
            INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
            VALUES (?, ?, ?, 'sistema', NOW())
        ");
        $stmt->execute([$user_id, $other_user_id, $mensaje_confirmacion]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Coordinación confirmada',
        'ambos_confirmaron' => ($confirmaciones['confirmado_por_solicitante'] && $confirmaciones['confirmado_por_receptor'])
    ]);
    
} catch (Exception $e) {
    error_log("Error en confirmar-coordinacion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al confirmar coordinación'
    ]);
}
?>
