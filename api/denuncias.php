<?php
require_once '../config/database.php';
session_start();

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $producto_id = $data['producto_id'] ?? null;
    $motivo = $data['motivo'] ?? null;
    $descripcion = $data['descripcion'] ?? null;

    if (!$producto_id || !$motivo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }

    // Verificar si el usuario ya ha denunciado este producto
    $stmt = $conn->prepare("SELECT id FROM denuncias_productos WHERE usuario_id = ? AND producto_id = ?");
    $stmt->bind_param('ii', $usuario_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya has denunciado este producto']);
        exit;
    }

    // Crear la denuncia
    $stmt = $conn->prepare("INSERT INTO denuncias_productos (usuario_id, producto_id, motivo, descripcion) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiss', $usuario_id, $producto_id, $motivo, $descripcion);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Denuncia registrada correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al registrar la denuncia'
        ]);
    }
}
?>