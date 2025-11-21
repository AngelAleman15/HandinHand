<?php
// ENDPOINT PARA ACTUALIZAR INFORMACIÓN DEL PERFIL DE USUARIO
// Esta API maneja la actualización de datos personales y cambio de contraseña

session_start();

// Limpiar cualquier output previo y suprimir warnings
ob_clean();
ob_start();

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir archivos necesarios
require_once '../api/api_base.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar que solo se use POST
validateMethod(['POST']);

// Verificar que el usuario esté logueado
requireLogin();

// Obtener usuario actual
$user = getCurrentUser();

try {
    $pdo = getConnection();
    $errors = [];
    
    // Verificar la acción solicitada
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_personal_info') {
        // ACTUALIZAR INFORMACIÓN PERSONAL
        handlePersonalInfoUpdate($pdo, $user, $errors);
        
    } elseif ($action === 'change_password') {
        // CAMBIAR CONTRASEÑA
        handlePasswordChange($pdo, $user, $errors);
        
    } elseif ($action === 'test_connection') {
        // TEST DE CONECTIVIDAD
        sendSuccess([
            'message' => 'API funcionando correctamente',
            'user' => $user['username'],
            'timestamp' => date('Y-m-d H:i:s')
        ], 'Conexión a update-profile.php exitosa');
        
    } else {
        sendError('Acción no válida', 400);
    }
    
} catch (PDOException $e) {
    error_log("Error de base de datos en update-profile.php: " . $e->getMessage());
    sendError('Error de base de datos. Inténtalo de nuevo.', 500);
} catch (Exception $e) {
    error_log("Error en update-profile.php: " . $e->getMessage());
    sendError('Error interno del servidor. Inténtalo de nuevo.', 500);
}

// FUNCIÓN PARA MANEJAR ACTUALIZACIÓN DE INFORMACIÓN PERSONAL
function handlePersonalInfoUpdate($pdo, $user, &$errors) {
    // Obtener y validar datos del formulario
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    
    // Obtener la contraseña actual del usuario desde la base de datos
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userPassword = $stmt->fetchColumn();
    
    if (!$userPassword) {
        $errors[] = 'Error al verificar las credenciales del usuario';
        return;
    }
    
    // VALIDACIONES
    
    // Validar nombre completo
    if (empty($fullname)) {
        $errors[] = '❌ El nombre completo es obligatorio';
    } elseif (strlen($fullname) < 2) {
        $errors[] = '❌ El nombre completo debe tener al menos 2 caracteres (actual: ' . strlen($fullname) . ')';
    } elseif (strlen($fullname) > 100) {
        $errors[] = '❌ El nombre completo no puede tener más de 100 caracteres (actual: ' . strlen($fullname) . ')';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $fullname)) {
        $errors[] = '❌ El nombre completo solo puede contener letras y espacios';
    }
    
    // Validar username
    if (empty($username)) {
        $errors[] = '❌ El nombre de usuario es obligatorio';
    } elseif (strlen($username) < 3) {
        $errors[] = '❌ El nombre de usuario debe tener al menos 3 caracteres (actual: ' . strlen($username) . ')';
    } elseif (strlen($username) > 50) {
        $errors[] = '❌ El nombre de usuario no puede tener más de 50 caracteres (actual: ' . strlen($username) . ')';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = '❌ El nombre de usuario solo puede contener letras, números y guiones bajos';
    }
    
    // Verificar si el username ya existe (solo si es diferente al actual)
    if ($username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = '⚠️ El nombre de usuario "' . $username . '" ya está en uso por otro usuario';
        }
    }
    
    // Validar email
    if (empty($email)) {
        $errors[] = '❌ El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '❌ El formato del email no es válido: "' . $email . '"';
    } elseif (strlen($email) > 255) {
        $errors[] = '❌ El email no puede tener más de 255 caracteres (actual: ' . strlen($email) . ')';
    }
    
    // Verificar si el email ya existe (solo si es diferente al actual)
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = '⚠️ El email "' . $email . '" ya está registrado con otra cuenta';
        }
    }
    
    // Validar teléfono (opcional)
    if (!empty($phone)) {
        // Limpiar formato del teléfono
        $cleanPhone = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
        
        if (strlen($cleanPhone) < 9) {
            $errors[] = '📞 El teléfono debe tener al menos 9 dígitos (actual: ' . strlen($cleanPhone) . ' dígitos)';
        } elseif (strlen($cleanPhone) > 20) {
            $errors[] = '📞 El teléfono es demasiado largo (máximo 20 caracteres, actual: ' . strlen($cleanPhone) . ')';
        } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]{9,}$/', $cleanPhone)) {
            $errors[] = '📞 El formato del teléfono no es válido. Use formato: +123456789 o (123) 456-789';
        } else {
            $phone = $cleanPhone; // Usar la versión limpia
        }
    }
    
    // Validar contraseña actual
    if (empty($currentPassword)) {
        $errors[] = '🔑 La contraseña actual es requerida para confirmar los cambios';
    } else {
        // Debug info
        error_log("[UPDATE-PROFILE] Verificando contraseña para usuario ID: " . $user['id']);
        error_log("[UPDATE-PROFILE] Contraseña recibida longitud: " . strlen($currentPassword));
        error_log("[UPDATE-PROFILE] Hash en BD longitud: " . strlen($userPassword));
        
        // Verificar la contraseña actual
        if (!password_verify($currentPassword, $userPassword)) {
            $errors[] = '❌ La contraseña actual que ingresaste NO coincide con tu contraseña registrada';
            error_log("[UPDATE-PROFILE] Contraseña incorrecta para usuario ID: " . $user['id']);
        } else {
            error_log("[UPDATE-PROFILE] Contraseña correcta para usuario ID: " . $user['id']);
        }
    }
    
    // Si hay errores, enviarlos
    if (!empty($errors)) {
        sendError('Datos no válidos', 400, ['errors' => $errors]);
    }
    
    // ACTUALIZAR INFORMACIÓN EN LA BASE DE DATOS
    
    $fieldsToUpdate = [];
    $params = [];
    
    // Solo actualizar campos que hayan cambiado
    if ($fullname !== $user['fullname']) {
        $fieldsToUpdate[] = 'fullname = ?';
        $params[] = $fullname;
    }
    
    if ($username !== $user['username']) {
        $fieldsToUpdate[] = 'username = ?';
        $params[] = $username;
    }
    
    if ($email !== $user['email']) {
        $fieldsToUpdate[] = 'email = ?';
        $params[] = $email;
        
        // Marcar email como no verificado si cambió
        $fieldsToUpdate[] = 'email_verified = 0';
    }
    
    if ($phone !== ($user['phone'] ?? '')) {
        $fieldsToUpdate[] = 'phone = ?';
        $params[] = $phone ?: null;
    }
    
    // Actualizar fecha de modificación
    $fieldsToUpdate[] = 'updated_at = NOW()';
    
    if (!empty($fieldsToUpdate)) {
        // Agregar ID del usuario al final de los parámetros
        $params[] = $user['id'];
        
        $sql = "UPDATE usuarios SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Verificar que se actualizó
        if ($stmt->rowCount() === 0) {
            sendError('No se pudieron actualizar los datos', 500);
        }
        
        // Obtener datos actualizados
        $stmt = $pdo->prepare("SELECT id, fullname, username, email, phone, avatar_path, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updatedUser) {
            sendError('Error al obtener datos actualizados', 500);
        }
        
        // Actualizar la sesión con los nuevos datos
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }
        $_SESSION['user'] = array_merge($_SESSION['user'], $updatedUser);
        
        // Preparar respuesta
        $responseData = [
            'fullname' => $updatedUser['fullname'],
            'username' => $updatedUser['username'],
            'email' => $updatedUser['email'],
            'phone' => $updatedUser['phone']
        ];
        
        // Mensaje especial si el email cambió
        $message = 'Información personal actualizada correctamente';
        if ($email !== $user['email']) {
            $message .= '. Se ha enviado un email de verificación a tu nueva dirección.';
        }
        
        sendSuccess($responseData, $message);
        
    } else {
        // No hubo cambios
        sendSuccess([
            'fullname' => $user['fullname'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? ''
        ], 'No se detectaron cambios en la información');
    }
}

// FUNCIÓN PARA MANEJAR CAMBIO DE CONTRASEÑA
function handlePasswordChange($pdo, $user, &$errors) {
    // Obtener y validar datos del formulario
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // VALIDACIONES
    
    // Validar contraseña actual
    if (empty($currentPassword)) {
        $errors[] = 'La contraseña actual es obligatoria';
    } else {
        // Verificar la contraseña actual
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'La contraseña actual no es correcta';
        }
    }
    
    // Validar nueva contraseña
    if (empty($newPassword)) {
        $errors[] = 'La nueva contraseña es obligatoria';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif (strlen($newPassword) > 255) {
        $errors[] = 'La nueva contraseña es demasiado larga';
    }
    
    // Validar confirmación de contraseña
    if (empty($confirmPassword)) {
        $errors[] = 'La confirmación de contraseña es obligatoria';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    // Verificar que la nueva contraseña sea diferente a la actual
    if (!empty($currentPassword) && !empty($newPassword) && password_verify($newPassword, $user['password'])) {
        $errors[] = 'La nueva contraseña debe ser diferente a la actual';
    }
    
    // Si hay errores, enviarlos
    if (!empty($errors)) {
        sendError('Datos no válidos', 400, ['errors' => $errors]);
    }
    
    // ACTUALIZAR CONTRASEÑA EN LA BASE DE DATOS
    
    // Hash de la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    // Verificar que se actualizó
    if ($stmt->rowCount() === 0) {
        sendError('No se pudo actualizar la contraseña', 500);
    }
    
    // Respuesta exitosa
    sendSuccess([
        'message' => 'Contraseña actualizada correctamente'
    ], 'Contraseña cambiada correctamente. Por seguridad, debes iniciar sesión nuevamente.');
}