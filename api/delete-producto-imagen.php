<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;

if (!$image_id || !$producto_id) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

$pdo = getConnection();

try {
    // Verificar que el producto pertenece al usuario
    $stmt = $pdo->prepare('SELECT id FROM productos WHERE id = ? AND user_id = ?');
    $stmt->execute([$producto_id, $user['id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    // Obtener la imagen antes de eliminar
    $stmt = $pdo->prepare('SELECT imagen, es_principal FROM producto_imagenes WHERE id = ? AND producto_id = ?');
    $stmt->execute([$image_id, $producto_id]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$imagen) {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Eliminar la imagen de la BD
    $stmt = $pdo->prepare('DELETE FROM producto_imagenes WHERE id = ? AND producto_id = ?');
    $stmt->execute([$image_id, $producto_id]);
    
    // Si era la imagen principal, marcar otra como principal
    if ($imagen['es_principal'] == 1) {
        $stmt = $pdo->prepare('SELECT id, imagen FROM producto_imagenes WHERE producto_id = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$producto_id]);
        $nueva_principal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nueva_principal) {
            // Marcar como principal
            $stmt = $pdo->prepare('UPDATE producto_imagenes SET es_principal = 1 WHERE id = ?');
            $stmt->execute([$nueva_principal['id']]);
            
            // Actualizar tabla productos
            $stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
            $stmt->execute([$nueva_principal['imagen'], $producto_id]);
        }
    }
    
    $pdo->commit();
    
    // Eliminar archivo físico
    $file_path = __DIR__ . '/../' . $imagen['imagen'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
    
    echo json_encode(['success' => true, 'message' => 'Imagen eliminada']);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
