<?php
// Configuración del servidor de chat
define('CHAT_SERVER_PORT', '3001');

// IP MANUAL LOCAL - Tu IP en la red WiFi
define('CHAT_SERVER_IP_LOCAL', '192.168.1.5');

// IP PÚBLICA - Tu IP de Internet (para acceso desde fuera de tu red)
// NOTA: Esta IP puede cambiar si tu ISP usa IP dinámica
// Última actualización: 2025-10-16-6:05 p.m.
define('CHAT_SERVER_IP_PUBLIC', '186.55.23.21');

// Detectar si la conexión viene de red local o externa
function isLocalNetwork() {
    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
    // Es red local si la IP del cliente empieza con 192.168, 10., o 172.16-31
    return preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $remote_addr) ||
           $remote_addr === '127.0.0.1' || $remote_addr === '::1';
}

// Detectar automáticamente la IP del servidor según el origen de la conexión
function getServerIP() {
    // Si el cliente está en la misma red local, usar IP local
    if (isLocalNetwork()) {
        return CHAT_SERVER_IP_LOCAL;
    }
    
    // Si el cliente está fuera de la red, usar IP pública
    return CHAT_SERVER_IP_PUBLIC;
}

// Función para obtener la URL del servidor de chat
function getChatServerUrl() {
    $serverIp = getServerIP();
    return 'http://' . $serverIp . ':' . CHAT_SERVER_PORT;
}
?>