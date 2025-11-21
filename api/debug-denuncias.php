<?php
/**
 * Script temporal de debug para denuncias
 * Muestra exactamente qué está recibiendo el servidor
 */

// Headers para debug
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Iniciar sesión
session_start();

// Capturar TODO lo que llega
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
    'session_user_id' => $_SESSION['user_id'] ?? 'NO SESSION',
    'raw_input' => file_get_contents('php://input'),
    'json_decoded' => json_decode(file_get_contents('php://input'), true),
    'json_error' => json_last_error_msg(),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'server_info' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'N/A'
    ]
];

// Escribir al log
error_log("=== DEBUG DENUNCIAS ===");
error_log(json_encode($debug_info, JSON_PRETTY_PRINT));

// Devolver respuesta
echo json_encode([
    'success' => true,
    'message' => 'Debug info capturado',
    'debug' => $debug_info
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
