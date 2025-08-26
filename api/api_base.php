<?php
// Base para todas las APIs - Funciones comunes
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Función para enviar respuesta JSON exitosa
 */
function sendSuccess($data = [], $message = 'Operación exitosa', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función para enviar respuesta JSON de error
 */
function sendError($message = 'Error en la operación', $code = 400, $details = null) {
    http_response_code($code);
    $response = [
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($details !== null) {
        $response['details'] = $details;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función para validar que el método HTTP sea el correcto
 */
function validateMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $allowedMethods)) {
        sendError("Método $method no permitido. Métodos permitidos: " . implode(', ', $allowedMethods), 405);
    }
}

/**
 * Función para obtener datos JSON del body de la request
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
        sendError('JSON inválido en el cuerpo de la petición', 400);
    }
    
    return $data ?: [];
}

/**
 * Función para validar campos requeridos
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendError('Campos requeridos faltantes: ' . implode(', ', $missing), 400);
    }
}

/**
 * Función para verificar autenticación
 */
function requireAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendError('Se requiere autenticación', 401);
    }
    return $_SESSION['user_id'];
}

/**
 * Función para sanitizar datos
 */
function sanitizeData($data) {
    if (is_array($data)) {
        return array_map('sanitizeData', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
