<?php
/**
 * API: Crear denuncia de intercambio
 * POST /api/denunciar-intercambio.php
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

if (!isset($data['seguimiento_id']) || !isset($data['motivo']) || !isset($data['descripcion'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros requeridos']);
    exit;
}

try {
    $db = getConnection();
    $db->beginTransaction();
    
    $seguimiento_id = $data['seguimiento_id'];
    $motivo = $data['motivo'];
    $descripcion = $data['descripcion'];
    $evidencias = $data['evidencias'] ?? null;
    
    // Obtener datos del seguimiento
    $stmt = $db->prepare("
        SELECT usuario1_id, usuario2_id 
        FROM seguimiento_intercambios 
        WHERE id = ?
    ");
    $stmt->execute([$seguimiento_id]);
    $seguimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seguimiento) {
        throw new Exception('Seguimiento no encontrado');
    }
    
    // Verificar que el usuario es parte del intercambio
    if ($seguimiento['usuario1_id'] != $user_id && $seguimiento['usuario2_id'] != $user_id) {
        throw new Exception('No tienes permiso para denunciar este intercambio');
    }
    
    $denunciado_id = ($seguimiento['usuario1_id'] == $user_id) ? $seguimiento['usuario2_id'] : $seguimiento['usuario1_id'];
    
    // Crear denuncia
    $stmt = $db->prepare("
        INSERT INTO denuncias_intercambio 
        (seguimiento_id, denunciante_id, denunciado_id, motivo, descripcion, evidencias)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $seguimiento_id,
        $user_id,
        $denunciado_id,
        $motivo,
        $descripcion,
        $evidencias ? json_encode($evidencias) : null
    ]);
    
    // Actualizar estado del seguimiento
    $stmt = $db->prepare("UPDATE seguimiento_intercambios SET estado = 'denunciado' WHERE id = ?");
    $stmt->execute([$seguimiento_id]);
    
    // Crear notificación para moderadores (aquí puedes filtrar usuarios admin)
    // Por ahora, solo registramos la denuncia
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Denuncia registrada correctamente. Un moderador la revisará pronto.'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
