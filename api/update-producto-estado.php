<?php
// api/update-producto-estado.php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireLogin();
$user = getCurrentUser();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';
$estados_validos = ['disponible', 'intercambiado', 'reservado'];

if (!$id || !in_array($estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare('UPDATE productos SET estado = ? WHERE id = ? AND user_id = ?');
$ok = $stmt->execute([$estado, $id, $user['id']]);

if ($ok && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
}
