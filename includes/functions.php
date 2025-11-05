<?php
// Funciones auxiliares para la aplicación

/**
 * Función para verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Función para obtener datos del usuario actual
 * Ahora incluye avatar_path para mostrar la foto de perfil personalizada
 * NOTA: Si api_base.php está cargado, usará esa versión en su lugar
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        require_once __DIR__ . '/../config/database.php';
        $pdo = getConnection();
        // Incluimos avatar_path en la consulta para obtener la ruta de la foto de perfil
        $stmt = $pdo->prepare("SELECT id, fullname, username, email, avatar_path, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * Función para cerrar sesión
 */
function logout() {
    session_start();
    session_destroy();
    header('Location: index.php');
    exit();
}

/**
 * Función para redirigir si no está logueado
 * NOTA: Si api_base.php está cargado, usará esa versión en su lugar
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: iniciarsesion.php');
            exit();
        }
    }
}

/**
 * Función para sanitizar datos de entrada
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Función para obtener productos (SIN PRECIO - App de trueques)
 */
function getProductos($limit = null, $busqueda = null) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    $sql = "SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name, u.avatar_path,
                   COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas,
                   COUNT(v.id) as total_valoraciones
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
    
    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Función para obtener un producto específico (SIN PRECIO - App de trueques)
 */
function getProducto($id) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name, u.avatar_path,
                                  COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas,
                                  COUNT(v.id) as total_valoraciones
                           FROM productos p 
                           JOIN usuarios u ON p.user_id = u.id 
                           LEFT JOIN valoraciones v ON u.id = v.usuario_id
                           WHERE p.id = ?
                           GROUP BY p.id");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Función para generar estrellas HTML
 */
function generateStars($rating, $max = 5) {
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= round($rating)) {
            $html .= '<img src="img/starfilled.png" alt="Estrella llena">';
        } else {
            $html .= '<img src="img/star.png" alt="Estrella vacía">';
        }
    }
    return $html;
}

/**
 * Función para obtener productos con filtros avanzados
 */
function getProductosFiltrados($limit = null, $busqueda = null, $categoria = null, $estado = null) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    $sql = "SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name, u.avatar_path,
                   p.promedio_estrellas,
                   p.total_valoraciones
            FROM productos p 
            JOIN usuarios u ON p.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Filtro de búsqueda
    if ($busqueda) {
        $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    // Filtro de categoría
    if ($categoria) {
        $sql .= " AND p.categoria = ?";
        $params[] = $categoria;
    }
    
    // Filtro de estado
    if ($estado) {
        $sql .= " AND p.estado = ?";
        $params[] = $estado;
    } else {
        // Por defecto solo mostrar disponibles si no se especifica
        $sql .= " AND p.estado = 'disponible'";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Función para buscar usuarios
 */
function buscarUsuarios($busqueda = null, $limit = 20) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    // Verificar si existe la columna ubicacion
    $checkColumn = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'ubicacion'")->fetch();
    $ubicacionExists = $checkColumn !== false;
    
    $ubicacionField = $ubicacionExists ? 'u.ubicacion,' : '';
    
    $sql = "SELECT u.id, u.username, u.fullname, u.avatar_path, {$ubicacionField}
                   COUNT(DISTINCT p.id) as total_productos,
                   0 as total_intercambios
            FROM usuarios u
            LEFT JOIN productos p ON u.id = p.user_id
            WHERE 1=1";
    
    $params = [];
    
    if ($busqueda) {
        $sql .= " AND (u.username LIKE ? OR u.fullname LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asegurar que ubicacion existe en cada resultado
    if (!$ubicacionExists) {
        foreach ($usuarios as &$usuario) {
            $usuario['ubicacion'] = null;
        }
    }
    
    return $usuarios;
}
?>
