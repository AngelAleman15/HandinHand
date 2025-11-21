<?php
// Asegurar que las sesiones estén iniciadas y las funciones estén cargadas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

// Detectar la ruta base del proyecto dinámicamente
// Para HandinHand en WAMP: http://localhost/MisTrabajos/HandinHand
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
// Obtener el directorio del script actual y remover 'includes' si está presente
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if (basename($script_dir) === 'includes') {
    $script_dir = dirname($script_dir);
}
// Limpiar la barra final para evitar dobles barras
$script_dir = rtrim($script_dir, '/');
// Construir base_url - siempre agregar una barra al final
$base_url = $protocol . '://' . $host . ($script_dir !== '' ? $script_dir : '');

// Función helper para construir URLs correctamente
function url($path) {
    global $base_url;
    // Limpiar barras del path
    $path = ltrim($path, '/');
    // Construir URL sin dobles barras
    return rtrim($base_url, '/') . '/' . $path;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?php echo $base_url; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/tema-usuarios.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/fyp-section.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/perseo-actions.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/header-notifications.css?v=<?php echo time(); ?>">
    <?php if (isset($additional_css)) echo $additional_css; ?>
    <title><?php echo isset($page_title) ? $page_title : 'HandinHand'; ?></title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- FYP Tracking System -->
    <script src="<?php echo $base_url; ?>/js/fyp-tracking.js?v=<?php echo time(); ?>" defer></script>
    <script src="<?php echo $base_url; ?>/js/fyp-carousel.js?v=<?php echo time(); ?>" defer></script>

    <!-- Definir variables globales para Socket.IO y usuario -->
    <?php if (isLoggedIn()): ?>
        <?php
            require_once __DIR__ . '/../config/chat_server.php';
            $currentUser = getCurrentUser();
            $userId = isset($currentUser['id']) ? (int)$currentUser['id'] : 0;
            $chatServerUrl = getChatServerUrl();
        ?>
        <script>
            window.CURRENT_USER_ID = <?php echo json_encode($userId); ?>;
            window.CHAT_SERVER_URL = <?php echo json_encode($chatServerUrl); ?>;
            // Emitir user_connected al cargar cualquier página
            document.addEventListener('DOMContentLoaded', function() {
                if (window.io && window.CURRENT_USER_ID && window.CHAT_SERVER_URL) {
                    try {
                        window.globalSocket = window.globalSocket || io(window.CHAT_SERVER_URL, { transports: ['websocket', 'polling'] });
                        window.globalSocket.emit('user_connected', window.CURRENT_USER_ID);
                    } catch (e) { console.warn('No se pudo conectar a Socket.IO global:', e); }
                }
            });
        </script>
        <!-- Cliente Socket.IO solo por CDN -->
        <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
        <script src="<?php echo $base_url; ?>/js/notifications.js?v=<?php echo time(); ?>"></script>
        <script src="<?php echo $base_url; ?>/js/header-notifications.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>

    <!-- Scripts adicionales si están definidos -->
    <?php if (isset($additional_scripts) && is_array($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <?php if (strpos($script, 'http') === 0): ?>
                <script src="<?php echo $script; ?>"></script>
            <?php else: ?>
                <script src="<?php echo $script; ?>?v=<?php echo time(); ?>"></script>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : 'body-index'; ?>">
    <div class="header">
        <div class="logo"><a href="<?php echo $base_url; ?>/index.php"><img src="<?php echo $base_url; ?>/img/Hand(sinfondo).png" alt="Icono"></a></div>
        
        <?php if (isLoggedIn()): ?>
        <!-- Botón de notificaciones -->
        <div class="notifications-btn-container">
            <button id="notifications-btn" class="icon-btn" title="Notificaciones">
                <i class="fas fa-bell"></i>
                <span id="notifications-badge" class="notification-badge" style="display: none;">0</span>
            </button>
            
            <!-- Panel de notificaciones -->
            <div id="notifications-panel" class="notifications-panel" style="display: none;">
                <div class="notifications-header">
                    <h3>Notificaciones</h3>
                    <button id="mark-all-read-btn" class="text-btn" title="Marcar todas como leídas">
                        <i class="fas fa-check-double"></i>
                    </button>
                </div>
                <div id="notifications-list" class="notifications-list">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="menu">
            <ul>
                <li class="menu-toggle-container">
                    <img src="<?php echo $base_url; ?>/img/menudesplegable.png" alt="menú desplegable" id="menu-toggle" class="menutoggle">
                    <div class="dropdown-menu" id="dropdown-menu">
                        <?php if (isLoggedIn()): ?>
                            <?php
                            $currentUser = getCurrentUser();
                            ?>
                            <!-- Opciones para usuarios logueados -->
                            <div class="dropdown-header">
                                <img src="<?php echo isset($currentUser['avatar_path']) && !empty($currentUser['avatar_path']) ? htmlspecialchars($currentUser['avatar_path']) : $base_url . '/img/usuario.svg'; ?>"
                                     alt="usuario"
                                     onerror="this.src='<?php echo $base_url; ?>/img/usuario.svg';"
                                     style="border-radius: 50%; object-fit: cover;">
                                <span>Hola, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo url('perfil.php'); ?>">
                                <button class="dropdown-item">
                                    Mi Perfil
                                </button>
                            </a>
                            <a href="<?php echo url('mensajeria.php'); ?>">
                                <button class="dropdown-item">
                                    💬 Mensajes
                                </button>
                            </a>
                            <a href="<?php echo url('mis-productos.php'); ?>">
                                <button class="dropdown-item">
                                    Mis Productos
                                </button>
                            </a>
                            <a href="<?php echo url('mis-intercambios.php'); ?>">
                                <button class="dropdown-item">
                                    🔄 Mis Intercambios
                                </button>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo url('configuracion.php'); ?>">
                                <button class="dropdown-item">
                                    Configuración
                                </button>
                            </a>
                            <button class="dropdown-item" onclick="showWipMessage('Ayuda')">
                                Ayuda <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span>
                            </button>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>/logout.php">
                                <button class="dropdown-item-exit logout-btn">
                                    Cerrar Sesión
                                </button>
                            </a>
                        <?php else: ?>
                            <!-- Opciones para usuarios no logueados -->
                            <button class="dropdown-item" onclick="showWipMessage('Ayuda')">
                                Ayuda <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span>
                            </button>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>/iniciarsesion.php">
                                <button class="dropdown-item">
                                    Iniciar Sesión
                                </button>
                            </a>
                            <a href="<?php echo $base_url; ?>/registrar.php">
                                <button class="dropdown-item">
                                    Registrarse
                                </button>
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <script>
    function showWipMessage(feature) {
        Swal.fire({
            icon: 'info',
            title: '🚧 Función en desarrollo',
            text: `La función "${feature}" está siendo desarrollada. Pronto estará disponible.`,
            confirmButtonColor: '#6a994e',
            confirmButtonText: 'Entendido'
        });
    }
    </script>
