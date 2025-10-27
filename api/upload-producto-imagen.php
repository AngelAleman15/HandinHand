<?php
// api/upload-producto-imagen.php
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

$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

// Verificar que el producto pertenece al usuario
$pdo = getConnection();
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? AND user_id = ?');
$stmt->execute([$producto_id, $user['id']]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$producto) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado o sin permisos']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se subió ninguna imagen válida']);
    exit;
}

$img = $_FILES['imagen'];
$ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido']);
    exit;
}

$dir = __DIR__ . '/../uploads/productos/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$filename = 'prod_' . $producto_id . '_' . time() . '.' . $ext;
$dest = $dir . $filename;
if (!move_uploaded_file($img['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
    exit;
}

// Actualizar ruta en la base de datos
$stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ? AND user_id = ?');
$stmt->execute(['uploads/productos/' . $filename, $producto_id, $user['id']]);

// Eliminar imagen anterior si existe y es diferente
if (!empty($producto['imagen']) && $producto['imagen'] !== 'uploads/productos/' . $filename) {
    $old = __DIR__ . '/../' . $producto['imagen'];
    if (file_exists($old)) {
        @unlink($old);
    }
}

echo json_encode(['success' => true, 'url' => 'uploads/productos/' . $filename]);
