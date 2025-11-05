<?php
/**
 * Script de depuración para el producto ID=4
 */

require_once 'config/database.php';

echo "<h2>🔍 Depuración del Producto ID=4</h2>";
echo "<hr>";

try {
    $db = getConnection();
    
    // 1. Verificar si el producto existe en la BD
    echo "<h3>1️⃣ Consulta directa a la base de datos:</h3>";
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = 4");
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($producto) {
        echo "<pre>";
        print_r($producto);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ El producto ID=4 NO existe en la base de datos</p>";
    }
    
    echo "<hr>";
    
    // 2. Probar la misma consulta que usa la API
    echo "<h3>2️⃣ Consulta con JOIN (como en la API):</h3>";
    $query = "SELECT p.*, u.username, u.email, u.phone, u.avatar_path,
              COALESCE(AVG(v.puntuacion), 0) as promedio_valoracion,
              COUNT(v.id) as total_valoraciones
              FROM productos p
              INNER JOIN usuarios u ON p.user_id = u.id
              LEFT JOIN valoraciones v ON u.id = v.usuario_id
              WHERE p.id = 4
              GROUP BY p.id";
    
    $stmt2 = $db->prepare($query);
    $stmt2->execute();
    $productoAPI = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($productoAPI) {
        echo "<pre>";
        print_r($productoAPI);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ La consulta con JOIN no devuelve resultados</p>";
    }
    
    echo "<hr>";
    
    // 3. Verificar el usuario del producto
    echo "<h3>3️⃣ Usuario propietario del producto:</h3>";
    if ($producto) {
        $stmtUser = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmtUser->execute([$producto['user_id']]);
        $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo "<pre>";
            print_r($usuario);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>❌ El usuario ID={$producto['user_id']} NO existe</p>";
        }
    }
    
    echo "<hr>";
    
    // 4. Simular la llamada a la API
    echo "<h3>4️⃣ Simulación de llamada a la API:</h3>";
    
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/productos.php?id=4';
    echo "<p><strong>URL:</strong> $apiUrl</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($error) {
        echo "<p style='color:red;'><strong>CURL Error:</strong> $error</p>";
    }
    
    echo "<p><strong>Respuesta de la API:</strong></p>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
    
    echo "<p><strong>Respuesta Raw:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    echo "<hr>";
    
    // 5. Verificar la imagen
    echo "<h3>5️⃣ Verificación de la imagen:</h3>";
    if ($producto) {
        $imagePath = $producto['imagen'];
        $fullPath = __DIR__ . '/' . $imagePath;
        
        echo "<p><strong>Ruta en BD:</strong> $imagePath</p>";
        echo "<p><strong>Ruta completa:</strong> $fullPath</p>";
        echo "<p><strong>Existe:</strong> " . (file_exists($fullPath) ? '✅ Sí' : '❌ No') . "</p>";
        
        if (file_exists($fullPath)) {
            echo "<p><img src='$imagePath' style='max-width:200px;' /></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<hr>
<p><a href="producto.php?id=4">Ver página del producto</a> | <a href="index.php">Volver al inicio</a></p>
