<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión'
    ]);
    exit;
}

try {
    $pdo = getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Obtener productos del usuario que estén disponibles o reservados
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre,
            p.categoria,
            p.estado,
            p.imagen
        FROM productos p
        WHERE p.user_id = ?
        AND p.estado IN ('disponible', 'reservado')
        ORDER BY 
            CASE 
                WHEN p.estado = 'disponible' THEN 1
                WHEN p.estado = 'reservado' THEN 2
            END,
            p.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-mis-productos-disponibles.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los productos: ' . $e->getMessage()
    ]);
}
?>
