<?php
// Archivo de prueba simple para el chatbot
header('Content-Type: application/json; charset=utf-8');

// Solo para debug - remover después
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['mensaje'])) {
        throw new Exception('Mensaje requerido');
    }

    $mensaje = trim($data['mensaje']);
    $mensaje = strtolower($mensaje);
    
    // Respuestas simples
    $respuestas = [
        'hola' => '¡Hola! Soy Perseo 🤖',
        'que tal' => '¡Todo bien! ¿En qué puedo ayudarte?',
        'adios' => '¡Hasta luego! 👋'
    ];
    
    $respuesta = 'No entiendo tu mensaje, pero estoy funcionando correctamente. Escribe "hola" para probarme.';
    
    foreach ($respuestas as $palabra => $resp) {
        if (strpos($mensaje, $palabra) !== false) {
            $respuesta = $resp;
            break;
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'OK',
        'data' => [
            'respuesta' => $respuesta
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>
