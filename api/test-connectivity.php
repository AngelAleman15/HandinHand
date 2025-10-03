<?php
// Test simple de conectividad
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Conectividad OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}