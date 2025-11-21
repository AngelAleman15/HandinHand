<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión'
    ]);
    exit;
}

try {
    // Obtener datos del request (soportar tanto JSON como POST)
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Fallback: si no hay JSON válido, intentar con $_POST
    if (!$input || !is_array($input)) {
        $input = $_POST;
    }
    
    $producto_solicitado_id = $input['producto_solicitado_id'] ?? null;
    $producto_ofrecido_id = $input['producto_ofrecido_id'] ?? null;
    $vendedor_id = $input['vendedor_id'] ?? null;
    $mensaje = trim($input['message'] ?? '');
    
    // Validaciones
    if (!$producto_solicitado_id || !$producto_ofrecido_id || !$vendedor_id) {
        throw new Exception('Faltan datos requeridos');
    }
    
    $pdo = getConnection();
    $comprador_id = $_SESSION['user_id'];
    
    // Verificar que el producto ofrecido pertenece al usuario actual
    $stmt = $pdo->prepare("SELECT user_id, nombre, estado, imagen FROM productos WHERE id = ?");
    $stmt->execute([$producto_ofrecido_id]);
    $producto_ofrecido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto_ofrecido || $producto_ofrecido['user_id'] != $comprador_id) {
        throw new Exception('El producto ofrecido no te pertenece');
    }
    
    if (!in_array($producto_ofrecido['estado'], ['disponible', 'reservado'])) {
        throw new Exception('El producto ofrecido no está disponible para intercambio');
    }
    
    // Obtener información del producto solicitado
    $stmt = $pdo->prepare("SELECT nombre, user_id, imagen FROM productos WHERE id = ?");
    $stmt->execute([$producto_solicitado_id]);
    $producto_solicitado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto_solicitado) {
        throw new Exception('Producto solicitado no encontrado');
    }
    
    // Verificar que el vendedor sea el dueño del producto solicitado
    if ($producto_solicitado['user_id'] != $vendedor_id) {
        throw new Exception('El producto no pertenece a este vendedor');
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // 1. Cambiar el estado del producto ofrecido a "reservado"
        $stmt = $pdo->prepare("UPDATE productos SET estado = 'reservado' WHERE id = ?");
        $stmt->execute([$producto_ofrecido_id]);
        
        // 2. Verificar si ya son amigos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM amistades 
            WHERE (usuario1_id = ? AND usuario2_id = ?) OR (usuario1_id = ? AND usuario2_id = ?)
        ");
        $stmt->execute([$comprador_id, $vendedor_id, $vendedor_id, $comprador_id]);
        $sonAmigos = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        // 3. Si no son amigos, crear chat temporal
        if (!$sonAmigos) {
            // Verificar si ya existe un chat temporal
            $stmt = $pdo->prepare("
                SELECT id FROM chats_temporales 
                WHERE (usuario1_id = ? AND usuario2_id = ?) 
                OR (usuario1_id = ? AND usuario2_id = ?)
            ");
            $stmt->execute([$comprador_id, $vendedor_id, $vendedor_id, $comprador_id]);
            $chatExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$chatExistente) {
                // Crear chat temporal
                $stmt = $pdo->prepare("
                    INSERT INTO chats_temporales (usuario1_id, usuario2_id, producto_relacionado_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$comprador_id, $vendedor_id, $producto_solicitado_id]);
            }
        }
        
        // 4. Crear el mensaje de propuesta de intercambio con formato especial
        $mensaje_data = json_encode([
            'tipo' => 'propuesta_intercambio',
            'producto_solicitado' => [
                'id' => $producto_solicitado_id,
                'nombre' => $producto_solicitado['nombre'],
                'imagen' => $producto_solicitado['imagen']
            ],
            'producto_ofrecido' => [
                'id' => $producto_ofrecido_id,
                'nombre' => $producto_ofrecido['nombre'],
                'imagen' => $producto_ofrecido['imagen']
            ],
            'mensaje_adicional' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        
        // Insertar mensaje en la tabla de mensajes
        $stmt = $pdo->prepare("
            INSERT INTO mensajes 
            (producto_id, sender_id, receiver_id, message, tipo_mensaje, producto_relacionado_id, created_at)
            VALUES (?, ?, ?, ?, 'propuesta_intercambio', ?, NOW())
        ");
        $stmt->execute([
            $producto_solicitado_id, 
            $comprador_id, 
            $vendedor_id, 
            $mensaje_data,
            $producto_ofrecido_id
        ]);
        
        $mensaje_id = $pdo->lastInsertId();
        
        // 5. Guardar la propuesta de intercambio en la tabla dedicada
        $stmt = $pdo->prepare("
            INSERT INTO propuestas_intercambio 
            (producto_solicitado_id, producto_ofrecido_id, solicitante_id, receptor_id, mensaje_id, mensaje, estado)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmt->execute([
            $producto_solicitado_id,
            $producto_ofrecido_id,
            $comprador_id,
            $vendedor_id,
            $mensaje_id,
            $mensaje
        ]);
        
        $propuesta_id = $pdo->lastInsertId();
        
        // Actualizar el mensaje para incluir el propuesta_id
        $mensaje_data_actualizado = json_encode([
            'tipo' => 'propuesta_intercambio',
            'propuesta_id' => $propuesta_id,
            'estado' => 'pendiente',
            'producto_solicitado' => [
                'id' => $producto_solicitado_id,
                'nombre' => $producto_solicitado['nombre'],
                'imagen' => $producto_solicitado['imagen']
            ],
            'producto_ofrecido' => [
                'id' => $producto_ofrecido_id,
                'nombre' => $producto_ofrecido['nombre'],
                'imagen' => $producto_ofrecido['imagen']
            ],
            'mensaje_adicional' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt = $pdo->prepare("UPDATE mensajes SET message = ? WHERE id = ?");
        $stmt->execute([$mensaje_data_actualizado, $mensaje_id]);
        
        // 6. Crear notificación para el vendedor (DESHABILITADO - usar sistema nuevo de notificaciones)
        // La notificación se creará cuando se acepte la propuesta en crear-seguimiento.php
        /*
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones (user_id, tipo, titulo, contenido, url, created_at)
            VALUES (?, 'mensaje', 'Nueva propuesta de intercambio', ?, ?, NOW())
        ");
        $enlace = "mensajeria.php?user_id=" . $comprador_id;
        $notif_mensaje = "Tienes una nueva propuesta de intercambio de productos";
        
        try {
            $stmt->execute([$vendedor_id, $notif_mensaje, $enlace]);
        } catch (Exception $e) {
            // Si falla la notificación, continuar (la tabla puede no existir aún)
            error_log("No se pudo crear notificación: " . $e->getMessage());
        }
        */
        
        // Commit de la transacción
        $pdo->commit();
        
        // 7. Emitir mensaje vía Socket.IO para actualización en tiempo real
        require_once __DIR__ . '/../config/chat_server.php';
        $chatServerUrl = getChatServerUrl();
        
        $socketData = [
            'sender_id' => $comprador_id,
            'receiver_id' => $vendedor_id,
            'message' => $mensaje_data_actualizado,
            'timestamp' => date('Y-m-d H:i:s'),
            'id' => $mensaje_id,
            'tipo_mensaje' => 'propuesta_intercambio',
            'propuesta_id' => $propuesta_id
        ];
        
        try {
            $ch = curl_init($chatServerUrl . '/api/emit-message');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($socketData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout corto para no bloquear
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            // Si falla Socket.IO, continuar (no es crítico)
            error_log("No se pudo emitir por Socket.IO: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Propuesta de intercambio enviada exitosamente',
            'propuesta_id' => $propuesta_id,
            'mensaje_id' => $mensaje_id,
            'son_amigos' => $sonAmigos,
            'chat_temporal' => !$sonAmigos
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en proponer-intercambio.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
