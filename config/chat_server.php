<?php
// Configuración del servidor de chat
define('CHAT_SERVER_PORT', '3001');
define('CHAT_SERVER_IP', '192.168.88.207'); // Tu IP WiFi

// Función para obtener la URL del servidor de chat
function getChatServerUrl() {
    // Usar siempre la IP WiFi configurada
    return 'http://' . CHAT_SERVER_IP . ':' . CHAT_SERVER_PORT;
}
?>