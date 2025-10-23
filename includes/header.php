<?php
// Asegurar que las sesiones estén iniciadas y las funciones estén cargadas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/perseo-actions.css?v=<?php echo time(); ?>">
    <title><?php echo isset($page_title) ? $page_title : 'HandinHand'; ?></title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Sistema de notificaciones de mensajes -->
    <?php if (isLoggedIn()): ?>
    <script src="js/notifications.js?v=<?php echo time(); ?>" defer></script>
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
        <div class="logo"><a href="index.php"><img src="img/Hand(sinfondo).png" alt="Icono"></a></div>
        <div class="menu">
            <ul>
                <li><img src="img/ayuda.png" alt="ayuda"></li>
                <li class="menu-toggle-container">
                    <img src="img/menudesplegable.png" alt="menú desplegable" id="menu-toggle" class="menutoggle">
                    <div class="dropdown-menu" id="dropdown-menu">
                        <?php if (isLoggedIn()): ?>
                            <?php
                            $currentUser = getCurrentUser();
                            ?>
                            <!-- Opciones para usuarios logueados -->
                            <div class="dropdown-header">
                                <img src="<?php echo isset($currentUser['avatar_path']) && !empty($currentUser['avatar_path']) ? htmlspecialchars($currentUser['avatar_path']) : 'img/usuario.png'; ?>"
                                     alt="usuario"
                                     onerror="this.src='img/usuario.png'"
                                     style="border-radius: 50%; object-fit: cover;">
                                <span>Hola, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="perfil.php">
                                <button class="dropdown-item">
                                    Mi Perfil
                                </button>
                            </a>
                            <a href="mensajeria.php">
                                <button class="dropdown-item">
                                    💬 Mensajes
                                </button>
                            </a>
                            <a href="#" onclick="showWipMessage('Mis Productos'); return false;">
                                <button class="dropdown-item">
                                    Mis Productos <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span>
                                </button>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="configuracion.php">
                                <button class="dropdown-item">
                                    Configuración
                                </button>
                            </a>
                            <button class="dropdown-item" onclick="showWipMessage('Ayuda')">
                                Ayuda <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span>
                            </button>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php">
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
                            <a href="iniciarsesion.php">
                                <button class="dropdown-item">
                                    Iniciar Sesión
                                </button>
                            </a>
                            <a href="registrar.php">
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
