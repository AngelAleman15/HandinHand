<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getConnection();
    
    // Obtener todas las categorías únicas de los productos
    $stmt = $pdo->query("
        SELECT categoria, COUNT(*) as count 
        FROM productos 
        WHERE categoria IS NOT NULL 
        AND categoria != '' 
        GROUP BY categoria 
        ORDER BY count DESC, categoria ASC
    ");
    
    $categorias = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Separar categorías múltiples (separadas por coma)
        $cats = explode(',', $row['categoria']);
        foreach ($cats as $cat) {
            $cat = trim($cat);
            if ($cat) {
                if (!isset($categorias[$cat])) {
                    $categorias[$cat] = 0;
                }
                $categorias[$cat] += (int)$row['count'];
            }
        }
    }
    
    // Convertir a array y ordenar por popularidad
    $result = [];
    foreach ($categorias as $nombre => $count) {
        $result[] = [
            'nombre' => $nombre,
            'count' => $count
        ];
    }
    
    // Ordenar por count descendente
    usort($result, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    echo json_encode([
        'success' => true,
        'categorias' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener categorías'
    ]);
}
