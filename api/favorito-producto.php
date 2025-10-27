<?php
// api/favorito-producto.php
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

$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

$pdo = getConnection();
// Verificar si ya es favorito
$stmt = $pdo->prepare('SELECT id FROM favoritos WHERE user_id = ? AND producto_id = ?');
$stmt->execute([$user['id'], $producto_id]);
$fav = $stmt->fetch();
if ($fav) {
    // Si ya es favorito, eliminar
    $pdo->prepare('DELETE FROM favoritos WHERE id = ?')->execute([$fav['id']]);
    echo json_encode(['success' => true, 'favorito' => false]);
} else {
    // Si no es favorito, agregar
    $pdo->prepare('INSERT INTO favoritos (user_id, producto_id) VALUES (?, ?)')->execute([$user['id'], $producto_id]);
    echo json_encode(['success' => true, 'favorito' => true]);
}
