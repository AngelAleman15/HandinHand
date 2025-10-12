<?php
class ApiResponse {
    private $success;
    private $data;
    private $message;
    private $errors;
    private $statusCode;

    public function __construct($success = false, $data = null, $message = '', $errors = [], $statusCode = 200) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->errors = is_array($errors) ? $errors : [$errors];
        $this->statusCode = $statusCode;
    }

    public static function success($data = null, $message = 'Operación exitosa') {
        return new self(true, $data, $message);
    }

    public static function error($message = 'Error en la operación', $errors = [], $statusCode = 400) {
        return new self(false, null, $message, $errors, $statusCode);
    }

    public static function unauthorized($message = 'No autorizado') {
        return new self(false, null, $message, [], 401);
    }

    public static function notFound($message = 'Recurso no encontrado') {
        return new self(false, null, $message, [], 404);
    }

    public function send() {
        // Limpiar cualquier buffer de salida previo
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Función global para manejar errores y excepciones
function handleApiError($errno = null, $errstr = null, $errfile = null, $errline = null, $errcontext = null) {
    $error = error_get_last();
    if ($error !== null) {
        $errstr = $error['message'];
        $errfile = $error['file'];
        $errline = $error['line'];
        $errno = $error['type'];
    }

    if (error_reporting() === 0) {
        return false;
    }

    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    
    $response = ApiResponse::error('Error interno del servidor', [
        'type' => $errorType,
        'message' => $errstr,
        'file' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $errfile),
        'line' => $errline
    ], 500);
    
    $response->send();
    return true;
}

// Función para manejar excepciones no capturadas
function handleApiException($e) {
    $response = ApiResponse::error('Error interno del servidor', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->getFile()),
        'line' => $e->getLine()
    ], 500);
    $response->send();
}

// Registrar los manejadores de errores
set_error_handler('handleApiError');
set_exception_handler('handleApiException');
register_shutdown_function('handleApiError');

// Asegurarse de que los errores no se muestren en la salida
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers CORS y de contenido
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para validar el método HTTP
function validateMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $allowedMethods)) {
        ApiResponse::error(
            "Método $method no permitido",
            ["Métodos permitidos: " . implode(', ', $allowedMethods)],
            405
        )->send();
    }
}

// Función para obtener y validar input JSON
function getJsonInput() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::error(
            'JSON inválido en el cuerpo de la petición',
            ['error' => json_last_error_msg()],
            400
        )->send();
    }

    return $data;
}

// Función para validar campos requeridos
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        ApiResponse::error(
            'Campos requeridos faltantes',
            ['fields' => $missing],
            400
        )->send();
    }
}

// Función para requerir autenticación
function requireAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        ApiResponse::unauthorized('Se requiere autenticación')->send();
    }
    return $_SESSION['user_id'];
}

// Función para sanitizar datos
function sanitizeData($data) {
    if (is_array($data)) {
        return array_map('sanitizeData', $data);
    }
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

// Función para validar tipos de datos
function validateDataTypes($data, $types) {
    $errors = [];
    foreach ($types as $field => $type) {
        if (isset($data[$field])) {
            $value = $data[$field];
            switch ($type) {
                case 'int':
                    if (!is_numeric($value) || intval($value) != $value) {
                        $errors[] = "$field debe ser un número entero";
                    }
                    break;
                case 'float':
                    if (!is_numeric($value)) {
                        $errors[] = "$field debe ser un número";
                    }
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "$field debe ser un email válido";
                    }
                    break;
                case 'date':
                    if (!strtotime($value)) {
                        $errors[] = "$field debe ser una fecha válida";
                    }
                    break;
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[] = "$field debe ser una URL válida";
                    }
                    break;
                case 'bool':
                    if (!is_bool($value) && $value !== '0' && $value !== '1') {
                        $errors[] = "$field debe ser un valor booleano";
                    }
                    break;
            }
        }
    }
    
    if (!empty($errors)) {
        ApiResponse::error('Validación de tipos fallida', $errors, 400)->send();
    }
}