<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

validateMethod(['POST', 'GET']);

$user_id = requireAuth();
$data = getJsonInput();

try {
    $pdo = getConnection();
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'crear':
            validateRequired($data, ['denunciado_id', 'motivo', 'descripcion']);
            
            $denunciado_id = intval($data['denunciado_id']);
            $motivo = sanitizeData($data['motivo']);
            $descripcion = sanitizeData($data['descripcion']);
            
            // Verificar que no se denuncie a sí mismo
            if ($user_id == $denunciado_id) {
                sendError('No puedes denunciarte a ti mismo', 400);
            }
            
            // Verificar que el usuario denunciado existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$denunciado_id]);
            if (!$stmt->fetch()) {
                sendError('Usuario no encontrado', 404);
            }
            
            // Verificar motivos válidos
            $motivos_validos = ['spam', 'fraude', 'contenido_inapropiado', 'acoso', 'suplantacion', 'otro'];
            if (!in_array($motivo, $motivos_validos)) {
                sendError('Motivo de denuncia no válido', 400);
            }
            
            // Insertar denuncia
            $stmt = $pdo->prepare("
                INSERT INTO denuncias (denunciante_id, denunciado_id, motivo, descripcion, estado)
                VALUES (?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([$user_id, $denunciado_id, $motivo, $descripcion]);
            
            sendSuccess(['denuncia_id' => $pdo->lastInsertId()], 'Denuncia enviada correctamente');
            break;
            
        case 'listar':
            // Solo administradores pueden listar denuncias
            $stmt = $pdo->prepare("
                SELECT 
                    d.*,
                    u1.fullname as denunciante_nombre,
                    u2.fullname as denunciado_nombre
                FROM denuncias d
                JOIN usuarios u1 ON d.denunciante_id = u1.id
                JOIN usuarios u2 ON d.denunciado_id = u2.id
                ORDER BY d.created_at DESC
            ");
            $stmt->execute();
            $denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess($denuncias);
            break;
            
        default:
            sendError('Acción no válida', 400);
    }
    
} catch (PDOException $e) {
    error_log("Error en denuncias.php: " . $e->getMessage());
    sendError('Error al procesar la solicitud', 500);
}
?>
