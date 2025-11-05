<?php
/**
 * Test final - Verificar que todo funciona
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Final - Producto ID=4</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Test Final del Producto ID=4</h1>
    <hr>
    
    <?php
    // Test 1: API directa
    echo "<h2>1️⃣ Test de API</h2>";
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/productos.php?id=4';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode == 200 && json_last_error() === JSON_ERROR_NONE && isset($data['success']) && $data['success']) {
        echo "<p class='success'>✅ API funciona correctamente</p>";
        echo "<div class='info'>";
        echo "<strong>Producto:</strong> {$data['data']['nombre']}<br>";
        echo "<strong>Descripción:</strong> {$data['data']['descripcion']}<br>";
        echo "<strong>Estado:</strong> {$data['data']['estado']}<br>";
        echo "<strong>Propietario:</strong> {$data['data']['username']}<br>";
        echo "<strong>Imagen:</strong> {$data['data']['imagen']}<br>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ API tiene problemas</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
    // Test 2: Verificar imagen
    echo "<hr><h2>2️⃣ Test de Imagen</h2>";
    if (isset($data['data']['imagen'])) {
        $imagePath = $data['data']['imagen'];
        $fullPath = __DIR__ . '/' . $imagePath;
        
        if (file_exists($fullPath)) {
            echo "<p class='success'>✅ Imagen existe en el servidor</p>";
            echo "<img src='/$imagePath' style='max-width: 300px; border-radius: 8px;' alt='Producto'>";
        } else {
            echo "<p class='error'>❌ Imagen NO existe: $fullPath</p>";
        }
    }
    
    // Test 3: Link a la página del producto
    echo "<hr><h2>3️⃣ Resultado</h2>";
    if ($httpCode == 200 && json_last_error() === JSON_ERROR_NONE && isset($data['success']) && $data['success']) {
        echo "<p class='success' style='font-size: 1.2em;'>🎉 <strong>¡Todo está funcionando correctamente!</strong></p>";
        echo "<p><a href='producto.php?id=4' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Página del Producto</a></p>";
    } else {
        echo "<p class='error'>⚠️ Aún hay problemas por resolver</p>";
    }
    ?>
    
    <hr>
    <p><a href="index.php">← Volver al inicio</a></p>
</body>
</html>
