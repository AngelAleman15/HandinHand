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
    
    // Verificar si se solicita solo amigos (por defecto: true)
    $soloAmigos = isset($_GET['solo_amigos']) ? filter_var($_GET['solo_amigos'], FILTER_VALIDATE_BOOLEAN) : true;
    error_log("API users.php - Solo amigos: " . ($soloAmigos ? 'SI' : 'NO'));
    
    if ($soloAmigos) {
        // Mostrar amigos + usuarios con conversaciones activas
        $query = "
            SELECT DISTINCT
                u.id,
                u.username,
                u.avatar_path as avatar,
                CASE WHEN a.usuario1_id IS NOT NULL THEN 1 ELSE 0 END as es_amigo,
                COALESCE(a.created_at, m.first_message_date) as sort_date
            FROM usuarios u
            LEFT JOIN amistades a ON 
                (a.usuario1_id = ? AND a.usuario2_id = u.id) OR 
                (a.usuario2_id = ? AND a.usuario1_id = u.id)
            LEFT JOIN (
                SELECT 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id
                        ELSE sender_id
                    END as other_user_id,
                    MIN(created_at) as first_message_date
                FROM mensajes
                WHERE (sender_id = ? OR receiver_id = ?)
                    AND is_deleted = FALSE
                GROUP BY other_user_id
            ) m ON m.other_user_id = u.id
            WHERE u.id != ?
                AND (a.usuario1_id IS NOT NULL OR m.other_user_id IS NOT NULL)
            ORDER BY sort_date DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $currentUserId, $currentUserId,  // Para el JOIN de amistades
            $currentUserId,                  // Para el CASE en subquery
            $currentUserId, $currentUserId,  // Para el WHERE en subquery
            $currentUserId                   // Para el WHERE principal
        ]);
    } else {
        // Mostrar todos los usuarios con indicador de amistad
        $query = "
            SELECT 
                u.id,
                u.username,
                u.avatar_path as avatar,
                CASE WHEN a.usuario1_id IS NOT NULL THEN 1 ELSE 0 END as es_amigo
            FROM usuarios u
            LEFT JOIN amistades a ON 
                (a.usuario1_id = ? AND a.usuario2_id = u.id) OR 
                (a.usuario2_id = ? AND a.usuario1_id = u.id)
            WHERE u.id != ?
            ORDER BY u.username
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
    }
    
    error_log("API users.php - Query ejecutado correctamente");
    
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
        
        // Obtener el último mensaje de la conversación con este usuario
        $lastMessageQuery = "
            SELECT m.mensaje, m.sender_id, m.created_at, u.username as sender_name
            FROM mensajes m
            LEFT JOIN usuarios u ON m.sender_id = u.id
            WHERE 
                ((m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?))
                AND m.is_deleted = FALSE
                AND (m.deleted_for IS NULL OR m.deleted_for NOT LIKE ?)
            ORDER BY m.created_at DESC
            LIMIT 1
        ";
        
        $lastMsgStmt = $conn->prepare($lastMessageQuery);
        $lastMsgStmt->execute([
            $currentUserId,      // sender_id = ?
            $row['id'],          // receiver_id = ?
            $row['id'],          // sender_id = ?
            $currentUserId,      // receiver_id = ?
            '%"' . $currentUserId . '"%'  // deleted_for NOT LIKE ?
        ]);
        
        $lastMessage = $lastMsgStmt->fetch();
        
        $lastMessageText = '';
        $lastMessageSender = '';
        $lastMessageTime = '';
        
        if ($lastMessage) {
            // Truncar mensaje si es muy largo
            $messageText = $lastMessage['mensaje'];
            if (strlen($messageText) > 40) {
                $messageText = substr($messageText, 0, 40) . '...';
            }
            
            // Determinar quién envió el mensaje
            if ($lastMessage['sender_id'] == $currentUserId) {
                $lastMessageSender = 'Tú';
            } else {
                $lastMessageSender = $lastMessage['sender_name'];
            }
            
            $lastMessageText = $messageText;
            $lastMessageTime = $lastMessage['created_at'];
        }
        
        // El flag es_amigo ya viene de la query principal
        $esAmigo = isset($row['es_amigo']) ? (bool)$row['es_amigo'] : false;
        
        $users[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'avatar' => $avatarPath,
            'last_message' => $lastMessageText,
            'last_message_sender' => $lastMessageSender,
            'last_message_time' => $lastMessageTime,
            'es_amigo' => $esAmigo
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