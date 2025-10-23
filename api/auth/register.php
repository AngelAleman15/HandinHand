<?php
require_once '../api_base.php';
require_once '../config/database.php';

validateMethod(['POST']);

$data = getJsonInput();
validateRequired($data, ['fullname', 'username', 'email', 'phone', 'password', 'birthdate']);

// Sanitizar datos
$fullname = sanitizeData($data['fullname']);
$username = sanitizeData($data['username']);
$email = sanitizeData($data['email']);
$phone = sanitizeData($data['phone']);
$password = $data['password'];
$birthdate = sanitizeData($data['birthdate']);

// Validaciones específicas con mensajes claros
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('El formato del email es incorrecto. Debe ser como: ejemplo@correo.com', 400);
}

if (strlen($password) < 6) {
    sendError('La contraseña es muy corta. Debe tener al menos 6 caracteres', 400);
}

// Validar fecha de nacimiento
$birthDate = DateTime::createFromFormat('Y-m-d', $birthdate);
if (!$birthDate) {
    sendError('Formato de fecha de nacimiento inválido. Debe ser YYYY-MM-DD', 400);
}

// Verificar que sea mayor de edad (18 años)
$today = new DateTime();
$age = $today->diff($birthDate)->y;
if ($age < 18) {
    sendError('Debes ser mayor de 18 años para poder registrarte en HandInHand', 400);
}

try {
    $pdo = getConnection();
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        sendError('El nombre de usuario o email ya está en uso', 409);
    }
    
    // Crear nuevo usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (fullname, username, email, phone, password, birthdate, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    $success = $stmt->execute([$fullname, $username, $email, $phone, $hashedPassword, $birthdate]);
    
    if ($success) {
        $userId = $pdo->lastInsertId();
        
        sendSuccess([
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ], 'Usuario registrado exitosamente', 201);
        
    } else {
        sendError('Error al registrar usuario', 500);
    }
    
} catch (Exception $e) {
    sendError('Error en el sistema: ' . $e->getMessage(), 500);
}
?>
