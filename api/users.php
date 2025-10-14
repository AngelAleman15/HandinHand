<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Asegurarse de que no hay output antes de los headers
ob_start();

// Habilitar todos los errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log para debugging
error_log("API users.php - Iniciando petición");

header('Content-Type: application/json');

try {
    error_log("API users.php - Intentando conexión a BD");
    $conn = getConnection();
    
    error_log("API users.php - Conexión exitosa");
    
    // Obtener la lista de usuarios excluyendo el usuario actual
    $currentUserId = $_SESSION['user_id'] ?? 0;
    error_log("API users.php - Usuario actual ID: " . $currentUserId);
    
    $query = "SELECT id, username, avatar_path as avatar FROM usuarios WHERE id != :userId";
    $stmt = $conn->prepare($query);
    $stmt->execute(['userId' => $currentUserId]);
    
    $users = [];
    while ($row = $stmt->fetch()) {
        // Verificar si el avatar existe, sino usar imagen por defecto
        $avatarPath = 'img/usuario.png'; // Imagen por defecto
        
        if (!empty($row['avatar'])) {
            // Verificar si el archivo existe
            $fullPath = __DIR__ . '/../' . $row['avatar'];
            if (file_exists($fullPath)) {
                $avatarPath = $row['avatar'];
            }
        }
        
        $users[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'avatar' => $avatarPath
        ];
    }
    
    error_log("API users.php - Usuarios encontrados: " . count($users));
    
    ob_clean(); // Limpiar cualquier output previo
    echo json_encode(['status' => 'success', 'users' => $users]);
} catch (PDOException $e) {
    error_log("API users.php - Error de BD: " . $e->getMessage());
    http_response_code(500);
    ob_clean(); // Limpiar cualquier output previo
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("API users.php - Error general: " . $e->getMessage());
    http_response_code(500);
    ob_clean(); // Limpiar cualquier output previo
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener usuarios: ' . $e->getMessage()]);
}
?>