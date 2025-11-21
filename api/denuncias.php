<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

// Log inicial
error_log("=== DENUNCIAS.PHP INICIO ===");
error_log("Session ID: " . session_id());
error_log("User ID en sesión: " . ($_SESSION['user_id'] ?? 'NO SESSION'));

validateMethod(['POST', 'GET']);

$user_id = requireAuth();

error_log("User ID después de requireAuth: " . $user_id);

$data = getJsonInput();

error_log("Data después de getJsonInput: " . json_encode($data));

try {
    $pdo = getConnection();
    
    $action = $data['action'] ?? '';
    
    // Log para debug
    error_log("Denuncias - Action: " . $action);
    error_log("Denuncias - Data completo: " . json_encode($data));
    
    if (empty($action)) {
        error_log("Denuncias - Error: Action vacío");
        sendError('Acción no especificada. Se requiere el parámetro "action"', 400);
    }
    
    switch ($action) {
        case 'crear':
            validateRequired($data, ['denunciado_id', 'motivo', 'descripcion']);
            
            $denunciado_id = intval($data['denunciado_id']);
            $motivo = strtolower(trim($data['motivo'])); // Normalizar a minúsculas
            $descripcion = sanitizeData($data['descripcion']);
            
            error_log("Denuncias - Crear denuncia: denunciado_id=$denunciado_id, motivo=$motivo");
            
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
                error_log("Denuncias - Motivo no válido: " . $motivo);
                sendError('Motivo de denuncia no válido. Opciones válidas: ' . implode(', ', $motivos_validos), 400);
            }
            
            // Insertar denuncia
            $stmt = $pdo->prepare("
                INSERT INTO denuncias (denunciante_id, denunciado_id, motivo, descripcion, estado)
                VALUES (?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([$user_id, $denunciado_id, $motivo, $descripcion]);
            
            sendSuccess(['denuncia_id' => $pdo->lastInsertId()], 'Denuncia enviada correctamente');
            break;
        
        case 'reportar_producto':
            // NUEVO: Reportar productos (para compatibilidad con producto.php)
            validateRequired($data, ['producto_id', 'motivo']);
            
            $producto_id = intval($data['producto_id']);
            $motivo = sanitizeData($data['motivo']);
            
            error_log("Denuncias - Reportar producto: producto_id=$producto_id, motivo=$motivo");
            
            // Verificar que el producto existe y obtener su dueño
            $stmt = $pdo->prepare("SELECT user_id FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                sendError('Producto no encontrado', 404);
            }
            
            $denunciado_id = $producto['user_id'];
            
            // No permitir reportar tus propios productos
            if ($user_id == $denunciado_id) {
                sendError('No puedes reportar tus propios productos', 400);
            }
            
            // Insertar denuncia con motivo "otro" y descripción del reporte
            $descripcion = "Reporte de producto #$producto_id: " . $motivo;
            
            $stmt = $pdo->prepare("
                INSERT INTO denuncias (denunciante_id, denunciado_id, motivo, descripcion, estado)
                VALUES (?, ?, 'otro', ?, 'pendiente')
            ");
            $stmt->execute([$user_id, $denunciado_id, $descripcion]);
            
            sendSuccess(['denuncia_id' => $pdo->lastInsertId()], 'Reporte enviado correctamente');
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
            error_log("Denuncias - Acción no válida: " . $action);
            sendError('Acción no válida: "' . $action . '". Acciones disponibles: crear, reportar_producto, listar', 400);
    }
    
} catch (PDOException $e) {
    error_log("Error en denuncias.php: " . $e->getMessage());
    sendError('Error al procesar la solicitud', 500);
}
?>
