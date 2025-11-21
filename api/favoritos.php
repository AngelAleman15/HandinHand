<?php
require_once 'api_base_new.php';
require_once '../config/database.php';

// Verificar que el usuario está logueado
$usuario_id = requireAuth();

$usuario_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar a favoritos
    $data = json_decode(file_get_contents('php://input'), true);
    $producto_id = $data['producto_id'] ?? null;

    if (!$producto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado']);
        exit;
    }

    // Verificar si ya está en favoritos
    $stmt = $conn->prepare("SELECT id FROM productos_favoritos WHERE usuario_id = ? AND producto_id = ?");
    $stmt->bind_param('ii', $usuario_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Si ya existe, lo eliminamos (toggle)
        $stmt = $conn->prepare("DELETE FROM productos_favoritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->bind_param('ii', $usuario_id, $producto_id);
        $success = $stmt->execute();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Producto eliminado de favoritos' : 'Error al eliminar de favoritos',
            'isFavorite' => false
        ]);
    } else {
        // Si no existe, lo agregamos
        $stmt = $conn->prepare("INSERT INTO productos_favoritos (usuario_id, producto_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $usuario_id, $producto_id);
        $success = $stmt->execute();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Producto agregado a favoritos' : 'Error al agregar a favoritos',
            'isFavorite' => true
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verificar si un producto está en favoritos o listar todos los favoritos
    $producto_id = $_GET['producto_id'] ?? null;

    if ($producto_id) {
        // Verificar un producto específico
        $stmt = $conn->prepare("SELECT id FROM productos_favoritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->bind_param('ii', $usuario_id, $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();

        echo json_encode([
            'success' => true,
            'isFavorite' => $result->num_rows > 0
        ]);
    } else {
        // Listar todos los favoritos
        $sql = "SELECT p.*, u.fullname as vendedor_name, u.avatar_path as avatar_url, 
                (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE usuario_id = p.user_id) as promedio_estrellas
                FROM productos p
                JOIN productos_favoritos pf ON p.id = pf.producto_id
                JOIN usuarios u ON p.user_id = u.id
                WHERE pf.usuario_id = ?
                ORDER BY pf.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favoritos = [];
        while ($row = $result->fetch_assoc()) {
            $favoritos[] = $row;
        }

        echo json_encode([
            'success' => true,
            'productos' => $favoritos
        ]);
    }
}
?>