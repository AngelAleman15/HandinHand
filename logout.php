<?php
session_start();
require_once 'includes/functions.php';

if (isLoggedIn()) {
    $username = $_SESSION['username'] ?? 'Usuario';
    
    // Destruir sesión
    session_destroy();
    
    // Redirigir al inicio con mensaje
    header('Location: index.php?logout=success');
    exit();
} else {
    // Si no hay sesión, redirigir al inicio
    header('Location: index.php');
    exit();
}
?>
