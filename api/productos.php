<?php
require_once '../api_base.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Determinar la acción basada en el método y parámetros
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getProducto($id);
        } else {
            getProductos();
        }
        break;
        
    case 'POST':
        createProducto();
        break;
        
    case 'PUT':
        updateProducto($id);
        break;
        
    case 'DELETE':
        deleteProducto($id);
        break;
        
    default:
        sendError('Método no soportado', 405);
}

/**
 * Obtener lista de productos con filtros opcionales
 */
function getProductos() {
    try {
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : null;
        $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : null;
        $usuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $pdo = getConnection();
        
        $sql = "SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name,
                       COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas,
                       COUNT(v.puntuacion) as total_valoraciones
                FROM productos p 
                JOIN usuarios u ON p.user_id = u.id 
                LEFT JOIN valoraciones v ON u.id = v.usuario_id
                WHERE p.estado = 'disponible'";
        
        $params = [];
        
        if ($busqueda) {
            $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $params[] = "%$busqueda%";
            $params[] = "%$busqueda%";
        }
        
        if ($categoria) {
            $sql .= " AND p.categoria = ?";
            $params[] = $categoria;
        }
        
        if ($usuario) {
            $sql .= " AND p.user_id = ?";
            $params[] = $usuario;
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar total de productos para paginación
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM productos p 
                     JOIN usuarios u ON p.user_id = u.id 
                     WHERE p.estado = 'disponible'";
        
        $countParams = [];
        if ($busqueda) {
            $countSql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $countParams[] = "%$busqueda%";
            $countParams[] = "%$busqueda%";
        }
        if ($categoria) {
            $countSql .= " AND p.categoria = ?";
            $countParams[] = $categoria;
        }
        if ($usuario) {
            $countSql .= " AND p.user_id = ?";
            $countParams[] = $usuario;
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendSuccess([
            'productos' => $productos,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ], 'Productos obtenidos exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al obtener productos: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener un producto específico
 */
function getProducto($id) {
    if (!$id) {
        sendError('ID de producto requerido', 400);
    }
    
    try {
        $pdo = getConnection();
        

        // Obtener producto principal
        $stmt = $pdo->prepare("SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name,
                                      u.phone as vendedor_phone, u.email as vendedor_email,
                                      COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas,
                                      COUNT(v.puntuacion) as total_valoraciones
                               FROM productos p 
                               JOIN usuarios u ON p.user_id = u.id 
                               LEFT JOIN valoraciones v ON u.id = v.usuario_id
                               WHERE p.id = ?
                               GROUP BY p.id");
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) {
            sendError('Producto no encontrado', 404);
        }
        // Obtener categorías
        $catStmt = $pdo->prepare("SELECT c.id, c.nombre FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = ?");
        $catStmt->execute([$id]);
        $categorias = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        // Obtener imágenes
        $imgStmt = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE producto_id = ?");
        $imgStmt->execute([$id]);
        $imagenes = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
        $producto['categorias'] = $categorias;
        $producto['imagenes'] = $imagenes;
        sendSuccess($producto, 'Producto obtenido exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al obtener producto: ' . $e->getMessage(), 500);
    }
}

/**
 * Crear un nuevo producto (SIN PRECIO - App de trueques)
 */
function createProducto() {
    $userId = requireAuth();
    
    $data = getJsonInput();
    validateRequired($data, ['nombre', 'descripcion', 'categorias']);
    $data = sanitizeData($data);
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO productos (user_id, nombre, descripcion, estado, latitud, longitud) VALUES (?, ?, ?, ?, ?, ?)");
        $estado = isset($data['estado']) ? $data['estado'] : 'disponible';
        $latitud = isset($data['latitud']) ? $data['latitud'] : null;
        $longitud = isset($data['longitud']) ? $data['longitud'] : null;
        $success = $stmt->execute([
            $userId,
            $data['nombre'],
            $data['descripcion'],
            $estado,
            $latitud,
            $longitud
        ]);
        if ($success) {
            $productoId = $pdo->lastInsertId();
            // Categorías (array de IDs)
            if (!empty($data['categorias']) && is_array($data['categorias'])) {
                $catStmt = $pdo->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
                foreach ($data['categorias'] as $catId) {
                    $catStmt->execute([$productoId, $catId]);
                }
            }
            // Imágenes (array de rutas)
            if (!empty($data['imagenes']) && is_array($data['imagenes'])) {
                $imgStmt = $pdo->prepare("INSERT INTO producto_imagenes (producto_id, imagen) VALUES (?, ?)");
                foreach ($data['imagenes'] as $img) {
                    $imgStmt->execute([$productoId, $img]);
                }
            }
            sendSuccess(['id' => $productoId], 'Producto creado exitosamente para intercambio', 201);
        } else {
            sendError('Error al crear el producto', 500);
        }
    } catch (Exception $e) {
        sendError('Error al crear producto: ' . $e->getMessage(), 500);
    }
}

/**
 * Actualizar un producto existente
 */
function updateProducto($id) {
    if (!$id) {
        sendError('ID de producto requerido', 400);
    }
    
    $userId = requireAuth();
    $data = getJsonInput();
    
    if (empty($data)) {
        sendError('No hay datos para actualizar', 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Verificar que el producto existe y pertenece al usuario
        $stmt = $pdo->prepare("SELECT user_id FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            sendError('Producto no encontrado', 404);
        }
        
        if ($producto['user_id'] != $userId) {
            sendError('No tienes permisos para editar este producto', 403);
        }
        
        // Construir query dinámico
        $fields = [];
        $params = [];
        

        $allowedFields = ['nombre', 'descripcion', 'estado', 'latitud', 'longitud'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = sanitizeData($data[$field]);
            }
        }
        if (!empty($fields)) {
            $params[] = $id;
            $sql = "UPDATE productos SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            if (!$success) {
                sendError('Error al actualizar el producto', 500);
            }
        }
        // Actualizar categorías si se envían
        if (isset($data['categorias']) && is_array($data['categorias'])) {
            $delCat = $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id = ?");
            $delCat->execute([$id]);
            $catStmt = $pdo->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
            foreach ($data['categorias'] as $catId) {
                $catStmt->execute([$id, $catId]);
            }
        }
        // Actualizar imágenes si se envían
        if (isset($data['imagenes']) && is_array($data['imagenes'])) {
            $delImg = $pdo->prepare("DELETE FROM producto_imagenes WHERE producto_id = ?");
            $delImg->execute([$id]);
            $imgStmt = $pdo->prepare("INSERT INTO producto_imagenes (producto_id, imagen) VALUES (?, ?)");
            foreach ($data['imagenes'] as $img) {
                $imgStmt->execute([$id, $img]);
            }
        }
        sendSuccess(['id' => $id], 'Producto actualizado exitosamente');
        
    } catch (Exception $e) {
        sendError('Error al actualizar producto: ' . $e->getMessage(), 500);
    }
}

/**
 * Eliminar un producto
 */
function deleteProducto($id) {
    if (!$id) {
        sendError('ID de producto requerido', 400);
    }
    
    $userId = requireAuth();
    
    try {
        $pdo = getConnection();
        
        // Verificar que el producto existe y pertenece al usuario
        $stmt = $pdo->prepare("SELECT user_id FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            sendError('Producto no encontrado', 404);
        }
        
        if ($producto['user_id'] != $userId) {
            sendError('No tienes permisos para eliminar este producto', 403);
        }
        
        // Eliminar producto
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        if ($success) {
            sendSuccess(['id' => $id], 'Producto eliminado exitosamente');
        } else {
            sendError('Error al eliminar el producto', 500);
        }
        
    } catch (Exception $e) {
        sendError('Error al eliminar producto: ' . $e->getMessage(), 500);
    }
}
?>
