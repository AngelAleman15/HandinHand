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
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, fullname, username, email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: iniciarsesion.php');
        exit();
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
    
    $sql = "SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name,
                   COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas
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
    
    $stmt = $pdo->prepare("SELECT p.*, u.username as vendedor_username, u.fullname as vendedor_name,
                                  COALESCE(AVG(v.puntuacion), 0) as promedio_estrellas
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
?>
