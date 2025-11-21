<?php
/**
 * API para eliminar productos
 * Elimina el producto, sus imágenes de la BD y los archivos físicos
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Verificar login
requireLogin();
$user = getCurrentUser();

// Permitir DELETE y POST (algunos clientes envían POST en lugar de DELETE)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener ID del producto
$producto_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Para DELETE, el ID viene en query string
    $producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
} else {
    // Para POST, puede venir en el body o query string
    parse_str(file_get_contents("php://input"), $data);
    $producto_id = isset($data['id']) ? intval($data['id']) : (isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0));
}

if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar que el producto existe y pertenece al usuario
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? AND user_id = ?');
    $stmt->execute([$producto_id, $user['id']]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado o sin permisos para eliminarlo']);
        exit;
    }
    
    // Obtener todas las imágenes del producto para eliminarlas físicamente
    $stmt = $pdo->prepare('SELECT imagen FROM producto_imagenes WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Eliminar de producto_imagenes (CASCADE eliminará automáticamente por FK, pero lo hacemos explícito)
    $stmt = $pdo->prepare('DELETE FROM producto_imagenes WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    
    // 2. Eliminar de producto_vistas
    $stmt = $pdo->prepare('DELETE FROM producto_vistas WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    
    // 3. Eliminar de producto_guardados
    $stmt = $pdo->prepare('DELETE FROM producto_guardados WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    
    // 4. Eliminar de producto_chats
    $stmt = $pdo->prepare('DELETE FROM producto_chats WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    
    // 5. Eliminar de producto_scores
    $stmt = $pdo->prepare('DELETE FROM producto_scores WHERE producto_id = ?');
    $stmt->execute([$producto_id]);
    
    // 6. Eliminar el producto principal
    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->execute([$producto_id]);
    
    // Confirmar transacción
    $pdo->commit();
    
    // Eliminar archivos físicos de imágenes
    $imagenes_eliminadas = 0;
    $errores_eliminacion = [];
    
    foreach ($imagenes as $imagen_path) {
        // Intentar eliminar de img/productos/
        $ruta_completa = __DIR__ . '/../img/productos/' . basename($imagen_path);
        if (file_exists($ruta_completa)) {
            if (@unlink($ruta_completa)) {
                $imagenes_eliminadas++;
            } else {
                $errores_eliminacion[] = basename($imagen_path);
            }
        }
    }
    
    // También intentar eliminar la imagen principal del producto si está en una ruta diferente
    if (!empty($producto['imagen'])) {
        $imagen_principal = $producto['imagen'];
        
        // Intentar diferentes rutas posibles
        $rutas_posibles = [
            __DIR__ . '/../' . $imagen_principal,
            __DIR__ . '/../img/productos/' . basename($imagen_principal),
            __DIR__ . '/../uploads/productos/' . basename($imagen_principal)
        ];
        
        foreach ($rutas_posibles as $ruta) {
            if (file_exists($ruta) && is_file($ruta)) {
                @unlink($ruta);
                break;
            }
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Producto eliminado exitosamente',
        'producto_id' => $producto_id,
        'imagenes_eliminadas' => $imagenes_eliminadas
    ];
    
    if (!empty($errores_eliminacion)) {
        $response['advertencias'] = [
            'mensaje' => 'Algunas imágenes no se pudieron eliminar físicamente',
            'archivos' => $errores_eliminacion
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el producto',
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado',
        'error' => $e->getMessage()
    ]);
}
