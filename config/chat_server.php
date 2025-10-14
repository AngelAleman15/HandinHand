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
        
        // Intentar usar la IP local si está configurada
        $local_ip = gethostbyname(gethostname());
        if ($local_ip && $local_ip !== gethostname()) {
            return 'http://' . $local_ip . ':' . CHAT_SERVER_PORT;
        }
        
        // Si no se puede obtener la IP local, usar localhost
        return 'http://localhost:' . CHAT_SERVER_PORT;
    }
    
    // Si hay una URL de producción configurada, usarla
    if (!empty($production_url)) {
        return $production_url;
    }
    
    // Por defecto, usar la IP del servidor actual
    return 'http://' . $_SERVER['SERVER_ADDR'] . ':' . CHAT_SERVER_PORT;
}
?>