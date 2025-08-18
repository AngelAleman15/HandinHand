<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <title><?php echo isset($page_title) ? $page_title : 'HandinHand'; ?></title>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
                        <button class="dropdown-item">
                            <img src="img/usuario.png" alt="perfil">
                            Mi Perfil
                        </button>
                        <button class="dropdown-item">
                            <img src="img/chat.png" alt="mensajes">
                            Mensajes
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item">
                            Configuración
                        </button>
                        <button class="dropdown-item">
                            Ayuda
                        </button>
                        <div class="dropdown-divider"></div>
                        <a href="iniciarsesion.php">
                            <button class="dropdown-item">Iniciar Sesión</button>
                        </a>
                        <a href="registrar.php">
                            <button class="dropdown-item">Registrarse</button>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
