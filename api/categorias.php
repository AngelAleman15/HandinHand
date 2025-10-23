<?php
require_once '../api_base.php';
require_once '../config/database.php';

validateMethod(['GET']);

try {
    $pdo = getConnection();
    
    // Obtener categorías con conteo de productos
    $stmt = $pdo->prepare("SELECT c.*, COUNT(p.id) as total_productos
                           FROM categorias c
                           LEFT JOIN productos p ON c.nombre = p.categoria AND p.estado = 'disponible'
                           GROUP BY c.id
                           ORDER BY c.nombre");
    
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($categorias, 'Categorías obtenidas exitosamente');
    
} catch (Exception $e) {
    sendError('Error al obtener categorías: ' . $e->getMessage(), 500);
}
?>
