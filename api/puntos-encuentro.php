<?php
// API para gestionar puntos de encuentro de productos
// Permite crear, obtener, actualizar y eliminar ubicaciones para el intercambio

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_base.php';

header('Content-Type: application/json; charset=utf-8');
validateMethod(['GET', 'POST', 'PUT', 'DELETE']);

try {
    $pdo = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener puntos de encuentro de un producto
        $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : null;
        
        if (!$producto_id) {
            sendError('ID de producto requerido', 400);
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM puntos_encuentro
            WHERE producto_id = ?
            ORDER BY es_principal DESC, created_at ASC
        ");
        $stmt->execute([$producto_id]);
        $puntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccess([
            'puntos_encuentro' => $puntos,
            'total' => count($puntos)
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear nuevo punto de encuentro
        $user_id = requireAuth();
        $data = getJsonInput();
        
        validateRequired($data, ['producto_id', 'nombre', 'direccion', 'latitud', 'longitud']);
        
        $producto_id = intval($data['producto_id']);
        $nombre = trim($data['nombre']);
        $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
        $direccion = trim($data['direccion']);
        $latitud = floatval($data['latitud']);
        $longitud = floatval($data['longitud']);
        $referencia = isset($data['referencia']) ? trim($data['referencia']) : null;
        $horario_sugerido = isset($data['horario_sugerido']) ? trim($data['horario_sugerido']) : null;
        $es_principal = isset($data['es_principal']) ? intval($data['es_principal']) : 0;
        
        // Verificar que el producto existe y pertenece al usuario
        $stmt = $pdo->prepare("SELECT user_id FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            sendError('Producto no encontrado', 404);
        }
        
        if ($producto['user_id'] != $user_id) {
            sendError('No tienes permiso para agregar puntos de encuentro a este producto', 403);
        }
        
        // Validar coordenadas
        if ($latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180) {
            sendError('Coordenadas GPS inválidas', 400);
        }
        
        // Si se marca como principal, desmarcar otros
        if ($es_principal) {
            $stmt = $pdo->prepare("UPDATE puntos_encuentro SET es_principal = 0 WHERE producto_id = ?");
            $stmt->execute([$producto_id]);
        }
        
        // Insertar punto de encuentro
        $stmt = $pdo->prepare("
            INSERT INTO puntos_encuentro 
            (producto_id, nombre, descripcion, direccion, latitud, longitud, referencia, horario_sugerido, es_principal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $producto_id, $nombre, $descripcion, $direccion, 
            $latitud, $longitud, $referencia, $horario_sugerido, $es_principal
        ]);
        
        $punto_id = $pdo->lastInsertId();
        
        sendSuccess([
            'punto_id' => $punto_id,
            'mensaje' => 'Punto de encuentro agregado exitosamente'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Actualizar punto de encuentro
        $user_id = requireAuth();
        $data = getJsonInput();
        
        validateRequired($data, ['punto_id']);
        
        $punto_id = intval($data['punto_id']);
        
        // Verificar que el punto existe y pertenece al usuario
        $stmt = $pdo->prepare("
            SELECT pe.*, p.user_id
            FROM puntos_encuentro pe
            JOIN productos p ON pe.producto_id = p.id
            WHERE pe.id = ?
        ");
        $stmt->execute([$punto_id]);
        $punto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$punto) {
            sendError('Punto de encuentro no encontrado', 404);
        }
        
        if ($punto['user_id'] != $user_id) {
            sendError('No tienes permiso para modificar este punto de encuentro', 403);
        }
        
        // Construir UPDATE dinámico
        $fields = [];
        $values = [];
        
        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = trim($data['nombre']);
        }
        if (isset($data['descripcion'])) {
            $fields[] = "descripcion = ?";
            $values[] = trim($data['descripcion']);
        }
        if (isset($data['direccion'])) {
            $fields[] = "direccion = ?";
            $values[] = trim($data['direccion']);
        }
        if (isset($data['latitud'])) {
            $fields[] = "latitud = ?";
            $values[] = floatval($data['latitud']);
        }
        if (isset($data['longitud'])) {
            $fields[] = "longitud = ?";
            $values[] = floatval($data['longitud']);
        }
        if (isset($data['referencia'])) {
            $fields[] = "referencia = ?";
            $values[] = trim($data['referencia']);
        }
        if (isset($data['horario_sugerido'])) {
            $fields[] = "horario_sugerido = ?";
            $values[] = trim($data['horario_sugerido']);
        }
        if (isset($data['es_principal'])) {
            $es_principal = intval($data['es_principal']);
            if ($es_principal) {
                // Desmarcar otros como principales
                $stmt = $pdo->prepare("UPDATE puntos_encuentro SET es_principal = 0 WHERE producto_id = ?");
                $stmt->execute([$punto['producto_id']]);
            }
            $fields[] = "es_principal = ?";
            $values[] = $es_principal;
        }
        
        if (empty($fields)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $values[] = $punto_id;
        $sql = "UPDATE puntos_encuentro SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        sendSuccess(['mensaje' => 'Punto de encuentro actualizado exitosamente']);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Eliminar punto de encuentro
        $user_id = requireAuth();
        $data = getJsonInput();
        
        validateRequired($data, ['punto_id']);
        
        $punto_id = intval($data['punto_id']);
        
        // Verificar que el punto existe y pertenece al usuario
        $stmt = $pdo->prepare("
            SELECT pe.*, p.user_id
            FROM puntos_encuentro pe
            JOIN productos p ON pe.producto_id = p.id
            WHERE pe.id = ?
        ");
        $stmt->execute([$punto_id]);
        $punto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$punto) {
            sendError('Punto de encuentro no encontrado', 404);
        }
        
        if ($punto['user_id'] != $user_id) {
            sendError('No tienes permiso para eliminar este punto de encuentro', 403);
        }
        
        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM puntos_encuentro WHERE id = ?");
        $stmt->execute([$punto_id]);
        
        sendSuccess(['mensaje' => 'Punto de encuentro eliminado exitosamente']);
    }
    
} catch (PDOException $e) {
    error_log("Error en puntos-encuentro.php: " . $e->getMessage());
    sendError('Error al procesar la solicitud', 500);
}
