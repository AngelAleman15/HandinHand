<?php
require_once 'config/chat_server.php';

echo "🔧 Diagnóstico de configuración del servidor de chat\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📡 URL del servidor de chat:\n";
echo "   " . getChatServerUrl() . "\n\n";

echo "🌐 IP detectada del servidor:\n";
echo "   " . getServerIP() . "\n\n";

echo "🔍 Variables de servidor PHP:\n";
echo "   \$_SERVER['SERVER_ADDR']: " . ($_SERVER['SERVER_ADDR'] ?? 'No disponible') . "\n";
echo "   \$_SERVER['SERVER_NAME']: " . ($_SERVER['SERVER_NAME'] ?? 'No disponible') . "\n";
echo "   \$_SERVER['HTTP_HOST']: " . ($_SERVER['HTTP_HOST'] ?? 'No disponible') . "\n\n";

echo "💻 Sistema:\n";
echo "   Hostname: " . gethostname() . "\n";
echo "   IP por hostname: " . gethostbyname(gethostname()) . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Si la URL es correcta, el chat debería funcionar\n";
echo "❌ Si la URL es incorrecta, actualiza CHAT_SERVER_IP_MANUAL en config/chat_server.php\n";
?>
