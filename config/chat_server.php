<?php
// Configuración del servidor de chat
define('CHAT_SERVER_PORT', '3001');

// Función para obtener la URL del servidor de chat
function getChatServerUrl() {
    // URL del servidor público (producción)
    $production_url = ''; // Ejemplo: https://tu-dominio.com:3001
    
    // Si estamos en desarrollo local
    if ($_SERVER['SERVER_NAME'] === 'localhost' || 
        $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
        substr($_SERVER['SERVER_ADDR'], 0, 8) === '192.168.') {
        
        // Usar la IP del servidor actual (la que usa el usuario para acceder)
        // Esto asegura que siempre use la IP correcta de la red WiFi
        return 'http://' . $_SERVER['SERVER_ADDR'] . ':' . CHAT_SERVER_PORT;
    }
    
    // Si hay una URL de producción configurada, usarla
    if (!empty($production_url)) {
        return $production_url;
    }
    
    // Por defecto, usar la IP del servidor actual
    return 'http://' . $_SERVER['SERVER_ADDR'] . ':' . CHAT_SERVER_PORT;
}
?>