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

// Manejar cambio de imagen principal
if (isset($_POST['set_principal'])) {
    $imagen_id = intval($_POST['set_principal']);
    $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
    
    if (!$producto_id || !$imagen_id) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    try {
        $pdo = getConnection();
        
        // Verificar que el producto pertenece al usuario
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? AND user_id = ?');
        $stmt->execute([$producto_id, $user['id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // Quitar principal de todas
        $stmt = $pdo->prepare('UPDATE producto_imagenes SET es_principal = 0 WHERE producto_id = ?');
        $stmt->execute([$producto_id]);
        
        // Establecer nueva principal
        $stmt = $pdo->prepare('UPDATE producto_imagenes SET es_principal = 1 WHERE id = ? AND producto_id = ?');
        $stmt->execute([$imagen_id, $producto_id]);
        
        // Actualizar tabla productos (compatibilidad)
        $stmt = $pdo->prepare('SELECT imagen FROM producto_imagenes WHERE id = ?');
        $stmt->execute([$imagen_id]);
        $nueva_principal = $stmt->fetchColumn();
        
        if ($nueva_principal) {
            $stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
            $stmt->execute([$nueva_principal, $producto_id]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Imagen principal actualizada']);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Subida de imágenes múltiples
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

// Verificar si hay imágenes
if (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No se seleccionó ninguna imagen']);
    exit;
}

// Verificar cuántas imágenes ya tiene el producto
$stmt = $pdo->prepare('SELECT COUNT(*) FROM producto_imagenes WHERE producto_id = ?');
$stmt->execute([$producto_id]);
$existingImages = (int)$stmt->fetchColumn();

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$dir = __DIR__ . '/../img/productos/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$uploadedFiles = [];
$errors = [];

// Procesar cada imagen
$files = $_FILES['imagenes'];
$fileCount = count($files['name']);

// Validar que no exceda el límite de 6 imágenes
if ($existingImages + $fileCount > 6) {
    echo json_encode([
        'success' => false, 
        'message' => "Máximo 6 imágenes por producto. Actualmente tienes $existingImages."
    ]);
    exit;
}

for ($i = 0; $i < $fileCount; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        $errors[] = "Error al subir {$files['name'][$i]}";
        continue;
    }
    
    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        $errors[] = "{$files['name'][$i]}: formato no permitido";
        continue;
    }
    
    // Validar tamaño (5MB)
    if ($files['size'][$i] > 5 * 1024 * 1024) {
        $errors[] = "{$files['name'][$i]}: archivo muy grande (máx 5MB)";
        continue;
    }
    
    $filename = 'prod_' . $producto_id . '_' . time() . '_' . uniqid() . '.' . $ext;
    $dest = $dir . $filename;
    
    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
        $uploadedFiles[] = $filename;
    } else {
        $errors[] = "Error al guardar {$files['name'][$i]}";
    }
}

if (empty($uploadedFiles)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No se pudo subir ninguna imagen',
        'errors' => $errors
    ]);
    exit;
}

// Guardar en base de datos
try {
    $pdo->beginTransaction();
    
    foreach ($uploadedFiles as $index => $filename) {
        // Si no hay imágenes existentes, la primera será principal
        $es_principal = ($existingImages === 0 && $index === 0) ? 1 : 0;
        
        // Guardar con la ruta relativa completa
        $ruta_imagen = 'img/productos/' . $filename;
        
        $stmt = $pdo->prepare('INSERT INTO producto_imagenes (producto_id, imagen, es_principal) VALUES (?, ?, ?)');
        $stmt->execute([$producto_id, $ruta_imagen, $es_principal]);
        
        // Si es la primera imagen principal, actualizar tabla productos
        if ($es_principal == 1) {
            $stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
            $stmt->execute([$ruta_imagen, $producto_id]);
        }
    }
    
    $pdo->commit();
    
    $count = count($uploadedFiles);
    $message = $count === 1 ? 'Imagen subida exitosamente' : "$count imágenes subidas exitosamente";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'uploaded' => $count,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Eliminar archivos subidos si hay error en BD
    foreach ($uploadedFiles as $filename) {
        $filepath = $dir . $filename;
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al guardar en base de datos: ' . $e->getMessage()
    ]);
}


