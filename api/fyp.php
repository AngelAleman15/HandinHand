<?php
/**
 * API para Sistema FYP (For You Page)
 * Maneja interacciones: vistas, guardados, chats
 * HandinHand Platform
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

try {
    $pdo = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET: Obtener productos recomendados
    if ($method === 'GET') {
        $accion = $_GET['accion'] ?? 'recomendados';
        $usuario_id = $_SESSION['user_id'] ?? null;
        
        if ($accion === 'recomendados') {
            // Obtener productos recomendados
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $productos = obtenerProductosRecomendados($pdo, $usuario_id, $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'productos' => $productos,
                'total' => count($productos)
            ]);
            
        } elseif ($accion === 'similares') {
            // Obtener productos similares a uno específico
            $producto_id = $_GET['producto_id'] ?? null;
            if (!$producto_id) {
                throw new Exception('producto_id requerido');
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $similares = obtenerProductosSimilares($pdo, $producto_id, $limit);
            
            echo json_encode([
                'success' => true,
                'similares' => $similares
            ]);
            
        } elseif ($accion === 'guardados') {
            // Obtener productos guardados del usuario
            if (!$usuario_id) {
                throw new Exception('Debes iniciar sesión');
            }
            
            $guardados = obtenerProductosGuardados($pdo, $usuario_id);
            
            echo json_encode([
                'success' => true,
                'guardados' => $guardados
            ]);
        }
        
    // POST: Registrar interacciones
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $accion = $data['accion'] ?? null;
        $producto_id = $data['producto_id'] ?? null;
        
        if (!$producto_id) {
            throw new Exception('producto_id requerido');
        }
        
        if ($accion === 'vista') {
            // Registrar vista de producto
            registrarVista($pdo, $producto_id, $_SESSION['user_id'] ?? null);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vista registrada'
            ]);
            
        } elseif ($accion === 'guardar') {
            // Guardar producto (favorito)
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Debes iniciar sesión');
            }
            
            guardarProducto($pdo, $producto_id, $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto guardado en favoritos'
            ]);
            
        } elseif ($accion === 'chat') {
            // Registrar inicio de chat
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Debes iniciar sesión');
            }
            
            $vendedor_id = $data['vendedor_id'] ?? null;
            if (!$vendedor_id) {
                throw new Exception('vendedor_id requerido');
            }
            
            registrarChat($pdo, $producto_id, $_SESSION['user_id'], $vendedor_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Chat registrado'
            ]);
        }
        
    // DELETE: Quitar de guardados
    } elseif ($method === 'DELETE') {
        $producto_id = $_GET['producto_id'] ?? null;
        
        if (!$producto_id) {
            throw new Exception('producto_id requerido');
        }
        
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Debes iniciar sesión');
        }
        
        quitarGuardado($pdo, $producto_id, $_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto removido de favoritos'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ===== FUNCIONES =====

function obtenerProductosRecomendados($pdo, $usuario_id, $limit, $offset) {
    // Si el usuario está logueado, personalizar recomendaciones
    if ($usuario_id) {
        // Obtener categorías de productos que ha visto
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.categoria
            FROM producto_vistas pv
            JOIN productos p ON pv.producto_id = p.id
            WHERE pv.usuario_id = ?
            ORDER BY pv.fecha_vista DESC
            LIMIT 3
        ");
        $stmt->execute([$usuario_id]);
        $categorias_vistas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Construir query con preferencia por categorías vistas
        $categoria_filter = '';
        $params = [$usuario_id];
        
        if (!empty($categorias_vistas)) {
            $placeholders = str_repeat('?,', count($categorias_vistas) - 1) . '?';
            $categoria_filter = "OR p.categoria IN ($placeholders)";
            $params = array_merge($params, $categorias_vistas);
        }
        
        $sql = "
            SELECT DISTINCT
                p.id,
                p.nombre,
                p.descripcion,
                p.imagen,
                p.categoria,
                p.estado,
                p.user_id,
                u.username as vendedor_username,
                u.fullname as vendedor_name,
                u.avatar_path,
                p.promedio_estrellas,
                p.total_valoraciones,
                ps.score_total,
                ps.total_vistas,
                ps.total_guardados,
                ps.total_chats,
                -- Calcular score personalizado
                (ps.score_total + 
                 CASE WHEN p.categoria IN (" . implode(',', array_fill(0, count($categorias_vistas), '?')) . ") THEN 50 ELSE 0 END) as score_personalizado
            FROM productos p
            LEFT JOIN usuarios u ON p.user_id = u.id
            LEFT JOIN producto_scores ps ON p.id = ps.producto_id
            WHERE p.estado = 'disponible'
            AND p.user_id != ?
            AND p.id NOT IN (
                SELECT producto_id FROM producto_vistas WHERE usuario_id = ? AND fecha_vista >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            )
            ORDER BY score_personalizado DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params = array_merge($categorias_vistas, [$usuario_id, $usuario_id, $limit, $offset]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
    } else {
        // Usuario no logueado: mostrar productos más populares
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.descripcion,
                p.imagen,
                p.categoria,
                p.estado,
                p.user_id,
                u.username as vendedor_username,
                u.fullname as vendedor_name,
                u.avatar_path,
                p.promedio_estrellas,
                p.total_valoraciones,
                ps.score_total,
                ps.total_vistas,
                ps.total_guardados,
                ps.total_chats
            FROM productos p
            LEFT JOIN usuarios u ON p.user_id = u.id
            LEFT JOIN producto_scores ps ON p.id = ps.producto_id
            WHERE p.estado = 'disponible'
            ORDER BY ps.score_total DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerProductosSimilares($pdo, $producto_id, $limit) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre,
            p.descripcion,
            p.imagen,
            p.categoria,
            p.estado,
            p.user_id,
            u.username as vendedor_username,
            u.fullname as vendedor_name,
            u.avatar_path,
            p.promedio_estrellas,
            p.total_valoraciones,
            ps.similitud_score
        FROM producto_similitudes ps
        JOIN productos p ON (
            CASE 
                WHEN ps.producto_a_id = ? THEN ps.producto_b_id
                ELSE ps.producto_a_id
            END = p.id
        )
        LEFT JOIN usuarios u ON p.user_id = u.id
        WHERE (ps.producto_a_id = ? OR ps.producto_b_id = ?)
        AND p.estado = 'disponible'
        ORDER BY ps.similitud_score DESC
        LIMIT ?
    ");
    $stmt->execute([$producto_id, $producto_id, $producto_id, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerProductosGuardados($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre,
            p.descripcion,
            p.imagen,
            p.categoria,
            p.estado,
            p.user_id,
            u.username as vendedor_username,
            u.fullname as vendedor_name,
            u.avatar_path,
            p.promedio_estrellas,
            p.total_valoraciones,
            pg.fecha_guardado
        FROM producto_guardados pg
        JOIN productos p ON pg.producto_id = p.id
        LEFT JOIN usuarios u ON p.user_id = u.id
        WHERE pg.usuario_id = ?
        ORDER BY pg.fecha_guardado DESC
    ");
    $stmt->execute([$usuario_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function registrarVista($pdo, $producto_id, $usuario_id) {
    $session_id = session_id();
    
    $stmt = $pdo->prepare("
        INSERT INTO producto_vistas (producto_id, usuario_id, session_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$producto_id, $usuario_id, $session_id]);
}

function guardarProducto($pdo, $producto_id, $usuario_id) {
    $stmt = $pdo->prepare("
        INSERT INTO producto_guardados (producto_id, usuario_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE fecha_guardado = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$producto_id, $usuario_id]);
}

function registrarChat($pdo, $producto_id, $usuario_id, $vendedor_id) {
    $stmt = $pdo->prepare("
        INSERT INTO producto_chats (producto_id, usuario_id, vendedor_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE fecha_chat = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$producto_id, $usuario_id, $vendedor_id]);
}

function quitarGuardado($pdo, $producto_id, $usuario_id) {
    $stmt = $pdo->prepare("
        DELETE FROM producto_guardados
        WHERE producto_id = ? AND usuario_id = ?
    ");
    $stmt->execute([$producto_id, $usuario_id]);
}
?>
