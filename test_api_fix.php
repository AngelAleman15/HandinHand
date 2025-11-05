<?php
/**
 * Test rápido de la API después de la corrección
 */

echo "<h2>🧪 Test de API - Producto ID=4</h2>";
echo "<hr>";

// Probar la API con CURL
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/productos.php?id=4';
echo "<p><strong>URL de prueba:</strong> <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . ($httpCode == 200 ? "✅ $httpCode" : "❌ $httpCode") . "</p>";

if ($error) {
    echo "<p style='color:red;'><strong>CURL Error:</strong> $error</p>";
}

$data = json_decode($response, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo "<h3>✅ Respuesta JSON válida:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if (isset($data['success']) && $data['success']) {
        echo "<h3 style='color:green;'>🎉 ¡API funcionando correctamente!</h3>";
        echo "<p><strong>Producto:</strong> " . $data['data']['nombre'] . "</p>";
        echo "<p><strong>Descripción:</strong> " . $data['data']['descripcion'] . "</p>";
        echo "<p><strong>Estado:</strong> " . $data['data']['estado'] . "</p>";
        echo "<p><strong>Propietario:</strong> " . $data['data']['username'] . "</p>";
    } else {
        echo "<h3 style='color:orange;'>⚠️ API respondió pero con error:</h3>";
        echo "<p>" . ($data['message'] ?? 'Sin mensaje de error') . "</p>";
    }
} else {
    echo "<h3 style='color:red;'>❌ Respuesta NO es JSON válido:</h3>";
    echo "<p><strong>Error JSON:</strong> " . json_last_error_msg() . "</p>";
    echo "<h4>Respuesta Raw:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr>";
echo "<p><a href='producto.php?id=4'><strong>🔗 Ir a la página del producto</strong></a> | <a href='index.php'>Volver al inicio</a></p>";
?>
