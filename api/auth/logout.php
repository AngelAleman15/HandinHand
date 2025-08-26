<?php
require_once '../api_base.php';

validateMethod(['POST']);

session_start();

if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username'] ?? 'Usuario';
    
    // Destruir sesión
    session_destroy();
    
    sendSuccess([], "Sesión cerrada exitosamente para $username");
} else {
    sendError('No hay sesión activa', 400);
}
?>
