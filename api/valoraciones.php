<?php
require_once '../api_base.php';
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = isset($_GET['user']) ? (int)$_GET['user'] : null;

switch ($method) {
    case 'GET':
        getValoraciones($userId);
        break;
        
    case 'POST':
        createValoracion();
        break;
        
    default:
        sendError('Método no soportado', 405);
}

/**
 * Obtener valoraciones de un usuario
 */
function getValoraciones($userId) {
    if (!$userId) {
        sendError('ID de usuario requerido', 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Obtener estadísticas de valoraciones
        $stmt = $pdo->prepare("SELECT 
                                  AVG(puntuacion) as promedio,
                                  COUNT(*) as total,
                                  SUM(CASE WHEN puntuacion = 5 THEN 1 ELSE 0 END) as cinco_estrellas,
                                  SUM(CASE WHEN puntuacion = 4 THEN 1 ELSE 0 END) as cuatro_estrellas,
                                  SUM(CASE WHEN puntuacion = 3 THEN 1 ELSE 0 END) as tres_estrellas,
                                  SUM(CASE WHEN puntuacion = 2 THEN 1 ELSE 0 END) as dos_estrellas,
                                  SUM(CASE WHEN puntuacion = 1 THEN 1 ELSE 0 END) as una_estrella
                               FROM valoraciones 
                               WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener valoraciones detalladas
        $stmt = $pdo->prepare("SELECT v.*, u.username as valorador_username, u.fullname as valorador_name
                               FROM valoraciones v
                               JOIN usuarios u ON v.valorador_id = u.id
                               WHERE v.usuario_id = ?
                               ORDER BY v.created_at DESC");
        $stmt->execute([$userId]);
        $valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccess([
            'estadisticas' => $estadisticas,
            'valoraciones' => $valoraciones
        ], 'Valoraciones obtenidas exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al obtener valoraciones: ' . $e->getMessage(), 500);
    }
}

/**
 * Crear una nueva valoración
 */
function createValoracion() {
    $valoradorId = requireAuth();
    
    $data = getJsonInput();
    validateRequired($data, ['usuario_id', 'puntuacion']);
    
    $usuarioId = (int)$data['usuario_id'];
    $puntuacion = (int)$data['puntuacion'];
    $comentario = isset($data['comentario']) ? sanitizeData($data['comentario']) : null;
    
    // Validaciones
    if ($puntuacion < 1 || $puntuacion > 5) {
        sendError('La puntuación debe estar entre 1 y 5', 400);
    }
    
    if ($valoradorId == $usuarioId) {
        sendError('No puedes valorarte a ti mismo', 400);
    }
    
    if ($comentario && strlen($comentario) > 500) {
        sendError('El comentario no puede exceder 500 caracteres', 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Verificar que el usuario a valorar existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        if (!$stmt->fetch()) {
            sendError('Usuario no encontrado', 404);
        }
        
        // Verificar si ya existe una valoración de este valorador a este usuario
        $stmt = $pdo->prepare("SELECT id FROM valoraciones WHERE usuario_id = ? AND valorador_id = ?");
        $stmt->execute([$usuarioId, $valoradorId]);
        if ($stmt->fetch()) {
            sendError('Ya has valorado a este usuario', 409);
        }
        
        // Crear la valoración
        $stmt = $pdo->prepare("INSERT INTO valoraciones (usuario_id, valorador_id, puntuacion, comentario, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        
        $success = $stmt->execute([$usuarioId, $valoradorId, $puntuacion, $comentario]);
        
        if ($success) {
            $valoracionId = $pdo->lastInsertId();
            sendSuccess(['id' => $valoracionId], 'Valoración creada exitosamente', 201);
        } else {
            sendError('Error al crear la valoración', 500);
        }
        
    } catch (Exception $e) {
        sendError('Error al crear valoración: ' . $e->getMessage(), 500);
    }
}
?>
