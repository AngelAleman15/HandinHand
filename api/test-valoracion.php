<?php
// Test simple para verificar la API de valoraciones
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

header('Content-Type: application/json; charset=utf-8');

// Test básico
sendSuccess([
    'test' => 'ok',
    'message' => 'API de valoraciones funcionando correctamente'
]);
