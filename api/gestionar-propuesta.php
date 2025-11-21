<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $propuesta_id = $input['propuesta_id'] ?? null;
    $accion = $input['accion'] ?? null; // 'aceptar', 'rechazar', 'contraoferta'
    $producto_contraoferta_id = $input['producto_contraoferta_id'] ?? null;
    
    if (!$propuesta_id || !$accion) {
        throw new Exception('Datos incompletos');
    }
    
    $pdo = getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Obtener la propuesta
    $stmt = $pdo->prepare("
        SELECT p.*, 
               ps.nombre as producto_solicitado_nombre, ps.imagen as producto_solicitado_imagen,
               po.nombre as producto_ofrecido_nombre, po.imagen as producto_ofrecido_imagen,
               u.fullname as solicitante_nombre
        FROM propuestas_intercambio p
        JOIN productos ps ON p.producto_solicitado_id = ps.id
        JOIN productos po ON p.producto_ofrecido_id = po.id
        JOIN usuarios u ON p.solicitante_id = u.id
        WHERE p.id = ? AND (p.receptor_id = ? OR p.solicitante_id = ?)
    ");
    $stmt->execute([$propuesta_id, $user_id, $user_id]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propuesta) {
        throw new Exception('Propuesta no encontrada');
    }
    
    // Verificar que el usuario sea el receptor (solo el receptor puede aceptar/rechazar)
    if ($accion !== 'cancelar' && $propuesta['receptor_id'] != $user_id) {
        throw new Exception('No tienes permiso para realizar esta acción');
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($accion === 'aceptar') {
            // Actualizar estado de la propuesta
            $stmt = $pdo->prepare("UPDATE propuestas_intercambio SET estado = 'aceptada' WHERE id = ?");
            $stmt->execute([$propuesta_id]);
            
            // Actualizar el mensaje original para reflejar el estado
            $stmt = $pdo->prepare("SELECT message FROM mensajes WHERE id = ?");
            $stmt->execute([$propuesta['mensaje_id']]);
            $mensaje_original = $stmt->fetchColumn();
            
            if ($mensaje_original) {
                $mensaje_obj = json_decode($mensaje_original, true);
                if ($mensaje_obj) {
                    $mensaje_obj['estado'] = 'aceptada';
                    $mensaje_actualizado = json_encode($mensaje_obj, JSON_UNESCAPED_UNICODE);
                    $stmt = $pdo->prepare("UPDATE mensajes SET message = ? WHERE id = ?");
                    $stmt->execute([$mensaje_actualizado, $propuesta['mensaje_id']]);
                }
            }
            
            // Enviar mensaje de confirmación
            $mensaje_data = json_encode([
                'tipo' => 'intercambio_aceptado',
                'propuesta_id' => $propuesta_id
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("
                INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
                VALUES (?, ?, ?, 'sistema', NOW())
            ");
            $stmt->execute([$user_id, $propuesta['solicitante_id'], $mensaje_data]);
            
            $mensaje_respuesta = '✅ Propuesta de intercambio aceptada';
            
        } elseif ($accion === 'rechazar') {
            // Actualizar estado
            $stmt = $pdo->prepare("UPDATE propuestas_intercambio SET estado = 'rechazada' WHERE id = ?");
            $stmt->execute([$propuesta_id]);
            
            // Actualizar el mensaje original para reflejar el estado
            $stmt = $pdo->prepare("SELECT message FROM mensajes WHERE id = ?");
            $stmt->execute([$propuesta['mensaje_id']]);
            $mensaje_original = $stmt->fetchColumn();
            
            if ($mensaje_original) {
                $mensaje_obj = json_decode($mensaje_original, true);
                if ($mensaje_obj) {
                    $mensaje_obj['estado'] = 'rechazada';
                    $mensaje_actualizado = json_encode($mensaje_obj, JSON_UNESCAPED_UNICODE);
                    $stmt = $pdo->prepare("UPDATE mensajes SET message = ? WHERE id = ?");
                    $stmt->execute([$mensaje_actualizado, $propuesta['mensaje_id']]);
                }
            }
            
            // Liberar el producto ofrecido (volver a disponible)
            $stmt = $pdo->prepare("UPDATE productos SET estado = 'disponible' WHERE id = ?");
            $stmt->execute([$propuesta['producto_ofrecido_id']]);
            
            // Mensaje de rechazo
            $mensaje_data = json_encode([
                'tipo' => 'intercambio_rechazado',
                'propuesta_id' => $propuesta_id
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("
                INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
                VALUES (?, ?, ?, 'sistema', NOW())
            ");
            $stmt->execute([$user_id, $propuesta['solicitante_id'], $mensaje_data]);
            
            $mensaje_respuesta = '❌ Propuesta de intercambio rechazada';
            
        } elseif ($accion === 'contraoferta') {
            if (!$producto_contraoferta_id) {
                throw new Exception('Debes seleccionar un producto para la contraoferta');
            }
            
            // Verificar que el producto pertenece al receptor
            $stmt = $pdo->prepare("SELECT nombre, imagen FROM productos WHERE id = ? AND user_id = ?");
            $stmt->execute([$producto_contraoferta_id, $user_id]);
            $producto_contra = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto_contra) {
                throw new Exception('Producto no válido para contraoferta');
            }
            
            // Crear nueva propuesta (contraoferta)
            $stmt = $pdo->prepare("
                INSERT INTO propuestas_intercambio 
                (producto_solicitado_id, producto_ofrecido_id, solicitante_id, receptor_id, mensaje, estado)
                VALUES (?, ?, ?, ?, 'Contraoferta', 'pendiente')
            ");
            $stmt->execute([
                $propuesta['producto_ofrecido_id'], // Ahora quiero el que me ofrecieron
                $producto_contraoferta_id,          // Ofrezco otro producto mío
                $user_id,                            // Yo soy el nuevo solicitante
                $propuesta['solicitante_id']        // El solicitante original es el nuevo receptor
            ]);
            
            $nueva_propuesta_id = $pdo->lastInsertId();
            
            // Actualizar propuesta original
            $stmt = $pdo->prepare("UPDATE propuestas_intercambio SET estado = 'contraoferta' WHERE id = ?");
            $stmt->execute([$propuesta_id]);
            
            // Mensaje de contraoferta
            $mensaje_data = json_encode([
                'tipo' => 'contraoferta',
                'propuesta_id' => $nueva_propuesta_id,
                'producto_solicitado' => [
                    'id' => $propuesta['producto_ofrecido_id'],
                    'nombre' => $propuesta['producto_ofrecido_nombre'],
                    'imagen' => $propuesta['producto_ofrecido_imagen']
                ],
                'producto_ofrecido' => [
                    'id' => $producto_contraoferta_id,
                    'nombre' => $producto_contra['nombre'],
                    'imagen' => $producto_contra['imagen']
                ]
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("
                INSERT INTO mensajes (sender_id, receiver_id, message, tipo_mensaje, created_at)
                VALUES (?, ?, ?, 'propuesta_intercambio', NOW())
            ");
            $stmt->execute([$user_id, $propuesta['solicitante_id'], $mensaje_data]);
            
            $mensaje_respuesta = '🔄 Contraoferta enviada';
            
        } elseif ($accion === 'cancelar') {
            // Solo el solicitante puede cancelar
            if ($propuesta['solicitante_id'] != $user_id) {
                throw new Exception('Solo el solicitante puede cancelar la propuesta');
            }
            
            $stmt = $pdo->prepare("UPDATE propuestas_intercambio SET estado = 'cancelada' WHERE id = ?");
            $stmt->execute([$propuesta_id]);
            
            // Actualizar el mensaje original para reflejar el estado
            $stmt = $pdo->prepare("SELECT message FROM mensajes WHERE id = ?");
            $stmt->execute([$propuesta['mensaje_id']]);
            $mensaje_original = $stmt->fetchColumn();
            
            if ($mensaje_original) {
                $mensaje_obj = json_decode($mensaje_original, true);
                if ($mensaje_obj) {
                    $mensaje_obj['estado'] = 'cancelada';
                    $mensaje_actualizado = json_encode($mensaje_obj, JSON_UNESCAPED_UNICODE);
                    $stmt = $pdo->prepare("UPDATE mensajes SET message = ? WHERE id = ?");
                    $stmt->execute([$mensaje_actualizado, $propuesta['mensaje_id']]);
                }
            }
            
            // Liberar producto
            $stmt = $pdo->prepare("UPDATE productos SET estado = 'disponible' WHERE id = ?");
            $stmt->execute([$propuesta['producto_ofrecido_id']]);
            
            $mensaje_respuesta = '🚫 Propuesta cancelada';
        } else {
            throw new Exception('Acción no válida');
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje_respuesta
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en gestionar-propuesta.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
