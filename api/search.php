<?php
/**
 * API de búsqueda para el sistema de acciones de Perseo
 * HandinHand Platform
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['query']) || empty(trim($input['query']))) {
        throw new Exception('Query de búsqueda requerido');
    }
    
    $query = trim($input['query']);
    $limit = isset($input['limit']) ? intval($input['limit']) : 10;
    
    // Conectar a base de datos
    $pdo = getConnection();
    
    // Búsqueda en productos
    $productos = buscarProductos($pdo, $query, $limit);
    
    // Búsqueda en usuarios (solo nombres públicos)
    $usuarios = buscarUsuarios($pdo, $query, 5);
    
    // Compilar resultados
    $resultados = [
        'productos' => $productos,
        'usuarios' => $usuarios,
        'total' => count($productos) + count($usuarios)
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Búsqueda completada',
        'results' => $resultados,
        'query' => $query
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'SEARCH_ERROR'
    ]);
}

function buscarProductos($pdo, $query, $limit) {
    $sql = "SELECT 
                p.id, 
                p.nombre, 
                p.descripcion, 
                p.categoria,
                p.imagen_url,
                p.fecha_creacion,
                u.username as vendedor
            FROM productos p 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.nombre LIKE :query 
               OR p.descripcion LIKE :query 
               OR p.categoria LIKE :query
            ORDER BY p.fecha_creacion DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear resultados
    return array_map(function($producto) {
        return [
            'type' => 'producto',
            'id' => $producto['id'],
            'title' => $producto['nombre'],
            'description' => substr($producto['descripcion'], 0, 100) . '...',
            'category' => $producto['categoria'],
            'image' => $producto['imagen_url'],
            'seller' => $producto['vendedor'],
            'date' => $producto['fecha_creacion'],
            'url' => 'producto.php?id=' . $producto['id']
        ];
    }, $productos);
}

function buscarUsuarios($pdo, $query, $limit) {
    $sql = "SELECT 
                id, 
                username, 
                fullname,
                fecha_registro
            FROM usuarios 
            WHERE username LIKE :query 
               OR fullname LIKE :query
            ORDER BY fecha_registro DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear resultados
    return array_map(function($usuario) {
        return [
            'type' => 'usuario',
            'id' => $usuario['id'],
            'title' => $usuario['fullname'] ?: $usuario['username'],
            'description' => '@' . $usuario['username'],
            'date' => $usuario['fecha_registro'],
            'url' => 'perfil.php?user=' . $usuario['id']
        ];
    }, $usuarios);
}
?>
