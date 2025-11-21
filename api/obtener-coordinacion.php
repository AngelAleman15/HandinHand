<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No autorizado'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener propuesta_id
$propuesta_id = isset($_GET['propuesta_id']) ? (int)$_GET['propuesta_id'] : 0;

if (!$propuesta_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Propuesta no especificada'
    ]);
    exit;
}

try {
    $pdo = getConnection();
    
    // Obtener información de la propuesta con productos
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u_sol.username as solicitante_nombre,
            u_rec.username as receptor_nombre,
            prod_sol.nombre as producto_solicitado_nombre,
            prod_sol.imagen as producto_solicitado_imagen,
            prod_of.nombre as producto_ofrecido_nombre,
            prod_of.imagen as producto_ofrecido_imagen
        FROM propuestas_intercambio p
        INNER JOIN usuarios u_sol ON p.solicitante_id = u_sol.id
        INNER JOIN usuarios u_rec ON p.receptor_id = u_rec.id
        INNER JOIN productos prod_sol ON p.producto_solicitado_id = prod_sol.id
        INNER JOIN productos prod_of ON p.producto_ofrecido_id = prod_of.id
        WHERE p.id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Propuesta no encontrada'
        ]);
        exit;
    }
    
    // Verificar que el usuario es parte de la propuesta
    if ($propuesta['solicitante_id'] != $user_id && $propuesta['receptor_id'] != $user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No autorizado'
        ]);
        exit;
    }
    
    // Obtener coordinación si existe
    $stmt = $pdo->prepare("
        SELECT *
        FROM coordinacion_intercambios
        WHERE propuesta_id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $coordinacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Agregar flag de si es receptor o solicitante
    $propuesta['es_receptor'] = ($propuesta['receptor_id'] == $user_id);
    
    echo json_encode([
        'status' => 'success',
        'propuesta' => $propuesta,
        'coordinacion' => $coordinacion // puede ser null si no existe
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener-coordinacion.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener coordinación'
    ]);
}
?>
