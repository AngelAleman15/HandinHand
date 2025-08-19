<?php
require_once '../api_base.php';
require_once '../config/database.php';

validateMethod(['POST']);

$data = getJsonInput();
validateRequired($data, ['username', 'password']);

$username = sanitizeData($data['username']);
$password = $data['password'];

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, username, fullname, email, password FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // No enviar la contraseña en la respuesta
        unset($user['password']);
        
        sendSuccess([
            'user' => $user,
            'session_id' => session_id()
        ], 'Inicio de sesión exitoso');
        
    } else {
        sendError('Credenciales inválidas', 401);
    }
    
} catch (Exception $e) {
    sendError('Error en el sistema: ' . $e->getMessage(), 500);
}
?>
