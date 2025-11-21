<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image_id']) || !isset($data['producto_id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$image_id = (int)$data['image_id'];
$producto_id = (int)$data['producto_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar que el producto pertenece al usuario
    $stmt = $pdo->prepare("SELECT user_id FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto || $producto['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para modificar este producto']);
        exit;
    }
    
    // Verificar que la imagen existe y pertenece al producto
    $stmt = $pdo->prepare("SELECT id FROM producto_imagenes WHERE id = ? AND producto_id = ?");
    $stmt->execute([$image_id, $producto_id]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$imagen) {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        exit;
    }
    
    // Quitar flag principal de todas las imágenes del producto
    $stmt = $pdo->prepare("UPDATE producto_imagenes SET es_principal = 0 WHERE producto_id = ?");
    $stmt->execute([$producto_id]);
    
    // Establecer esta imagen como principal
    $stmt = $pdo->prepare("UPDATE producto_imagenes SET es_principal = 1 WHERE id = ?");
    $stmt->execute([$image_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Imagen principal actualizada'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
