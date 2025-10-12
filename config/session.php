<?php
// Configuración de sesiones
ini_set('session.cookie_httponly', 1); // Previene acceso a la cookie de sesión via JavaScript
ini_set('session.use_only_cookies', 1); // Fuerza el uso de cookies para las sesiones
ini_set('session.cookie_secure', 1); // Cookies solo por HTTPS (comentar en desarrollo local sin HTTPS)

// Configuración de tiempo de vida de la sesión
ini_set('session.gc_maxlifetime', 3600); // 1 hora en segundos
ini_set('session.cookie_lifetime', 3600); // 1 hora en segundos

// Configuración de seguridad adicional
session_name('HANDINHAND_SESSION'); // Nombre personalizado de la sesión
session_start();

// Regenerar ID de sesión periódicamente
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    // Regenerar ID de sesión cada 30 minutos
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Verificar si la sesión ha expirado
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    // Si ha pasado más de 1 hora, destruir la sesión
    session_unset();
    session_destroy();
    header('Location: iniciarsesion.php?expired=1');
    exit();
}

// Actualizar último tiempo de actividad
$_SESSION['LAST_ACTIVITY'] = time();

// Verificar si la IP del usuario ha cambiado
if (!isset($_SESSION['USER_IP'])) {
    $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
} else if ($_SESSION['USER_IP'] !== $_SERVER['REMOTE_ADDR']) {
    // Si la IP cambió, destruir la sesión por seguridad
    session_unset();
    session_destroy();
    header('Location: iniciarsesion.php?security=ip_changed');
    exit();
}

// Verificar si el User Agent ha cambiado
if (!isset($_SESSION['USER_AGENT'])) {
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
} else if ($_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
    // Si el User Agent cambió, destruir la sesión por seguridad
    session_unset();
    session_destroy();
    header('Location: iniciarsesion.php?security=agent_changed');
    exit();
}

// Función para regenerar el ID de sesión después de acciones importantes
function regenerateSessionId() {
    // Guardar datos importantes de la sesión
    $old_session_data = $_SESSION;
    
    // Destruir la sesión actual
    session_destroy();
    
    // Iniciar nueva sesión
    session_start();
    session_regenerate_id(true);
    
    // Restaurar datos importantes
    $_SESSION = $old_session_data;
    $_SESSION['REGENERATED'] = time();
    
    return true;
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener datos del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'fullname' => $_SESSION['fullname'] ?? '',
        'avatar_path' => $_SESSION['avatar_path'] ?? '',
        'created_at' => $_SESSION['created_at'] ?? '',
    ];
}