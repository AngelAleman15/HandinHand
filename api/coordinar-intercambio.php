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

if (!isset($input['propuesta_id']) || !isset($input['lugar']) || !isset($input['fecha_hora'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$propuesta_id = (int)$input['propuesta_id'];
$lugar = trim($input['lugar']);
$fecha_hora = $input['fecha_hora'];
$notas = isset($input['notas']) ? trim($input['notas']) : null;

try {
    $pdo = getConnection();
    
    // Verificar que el usuario es parte de la propuesta
    $stmt = $pdo->prepare("
        SELECT 
            solicitante_id, 
            receptor_id,
            estado
        FROM propuestas_intercambio 
        WHERE id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        echo json_encode([
            'success' => false,
            'message' => 'Propuesta no encontrada'
        ]);
        exit;
    }
    
    if ($propuesta['solicitante_id'] != $user_id && $propuesta['receptor_id'] != $user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permiso para coordinar este intercambio'
        ]);
        exit;
    }
    
    if ($propuesta['estado'] !== 'pendiente') {
        echo json_encode([
            'success' => false,
            'message' => 'Esta propuesta ya no está pendiente'
        ]);
        exit;
    }
    
    // Verificar si ya existe una coordinación
    $stmt = $pdo->prepare("SELECT id FROM coordinacion_intercambios WHERE propuesta_id = ?");
    $stmt->execute([$propuesta_id]);
    $coordinacion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coordinacion_existente) {
        // Actualizar coordinación existente
        $stmt = $pdo->prepare("
            UPDATE coordinacion_intercambios 
            SET lugar_propuesto = ?,
                fecha_hora_propuesta = ?,
                propuesto_por_user_id = ?,
                notas = ?,
                confirmado_por_solicitante = IF(? = ?, TRUE, FALSE),
                confirmado_por_receptor = IF(? = ?, TRUE, FALSE),
                estado = 'coordinando',
                updated_at = NOW()
            WHERE propuesta_id = ?
        ");
        $stmt->execute([
            $lugar,
            $fecha_hora,
            $user_id,
            $notas,
            $user_id, $propuesta['solicitante_id'],
            $user_id, $propuesta['receptor_id'],
            $propuesta_id
        ]);
    } else {
        // Crear nueva coordinación
        $stmt = $pdo->prepare("
            INSERT INTO coordinacion_intercambios 
            (propuesta_id, lugar_propuesto, fecha_hora_propuesta, propuesto_por_user_id, notas,
             confirmado_por_solicitante, confirmado_por_receptor, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'coordinando')
        ");
        
        $confirmado_solicitante = ($user_id == $propuesta['solicitante_id']);
        $confirmado_receptor = ($user_id == $propuesta['receptor_id']);
        
        $stmt->execute([
            $propuesta_id,
            $lugar,
            $fecha_hora,
            $user_id,
            $notas,
            $confirmado_solicitante,
            $confirmado_receptor
        ]);
    }
    
    // Enviar mensaje automático al chat
    $mensaje_coord = json_encode([
        'tipo' => 'coordinacion_propuesta',
        'lugar' => $lugar,
        'fecha_hora' => $fecha_hora,
        'notas' => $notas,
        'propuesto_por' => $user_id
    ], JSON_UNESCAPED_UNICODE);
    
    $other_user_id = ($user_id == $propuesta['solicitante_id']) 
        ? $propuesta['receptor_id'] 
        : $propuesta['solicitante_id'];
    
    $stmt = $pdo->prepare("
        INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
        VALUES (?, ?, ?, 'coordinacion', NOW())
    ");
    $stmt->execute([$user_id, $other_user_id, $mensaje_coord]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Propuesta de coordinación enviada'
    ]);
    
} catch (Exception $e) {
    error_log("Error en coordinar-intercambio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la coordinación'
    ]);
}
?>
