<?php
require_once 'config/database.php';

echo "<h1>Corrección de Rutas de Imágenes</h1>";

try {
    $pdo = getConnection();
    
    // Verificar rutas en producto_imagenes
    echo "<h2>Tabla: producto_imagenes</h2>";
    $stmt = $pdo->query("SELECT id, producto_id, imagen FROM producto_imagenes");
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Producto ID</th><th>Ruta Actual</th><th>Ruta Corregida</th><th>Acción</th></tr>";
    
    $corregidas = 0;
    
    foreach ($imagenes as $img) {
        $ruta_actual = $img['imagen'];
        $ruta_corregida = $ruta_actual;
        
        // Casos a corregir:
        // 1. /img/productos/... -> img/productos/...
        if (strpos($ruta_actual, '/img/productos/') === 0) {
            $ruta_corregida = substr($ruta_actual, 1); // Quitar el / del inicio
        }
        // 2. uploads/productos/... -> img/productos/...
        elseif (strpos($ruta_actual, 'uploads/productos/') === 0) {
            $ruta_corregida = str_replace('uploads/productos/', 'img/productos/', $ruta_actual);
        }
        // 3. Solo el nombre del archivo -> img/productos/filename
        elseif (!strpos($ruta_actual, '/') && !strpos($ruta_actual, '\\')) {
            $ruta_corregida = 'img/productos/' . $ruta_actual;
        }
        
        $necesita_correccion = ($ruta_actual !== $ruta_corregida);
        
        echo "<tr>";
        echo "<td>{$img['id']}</td>";
        echo "<td>{$img['producto_id']}</td>";
        echo "<td style='color: " . ($necesita_correccion ? 'red' : 'green') . ";'>{$ruta_actual}</td>";
        echo "<td>{$ruta_corregida}</td>";
        echo "<td>" . ($necesita_correccion ? '❌ Necesita corrección' : '✅ OK') . "</td>";
        echo "</tr>";
        
        if ($necesita_correccion) {
            $stmt_update = $pdo->prepare("UPDATE producto_imagenes SET imagen = ? WHERE id = ?");
            $stmt_update->execute([$ruta_corregida, $img['id']]);
            $corregidas++;
        }
    }
    
    echo "</table>";
    echo "<p><strong>Total de rutas corregidas:</strong> $corregidas</p>";
    
    // Verificar rutas en tabla productos
    echo "<h2>Tabla: productos</h2>";
    $stmt = $pdo->query("SELECT id, nombre, imagen FROM productos LIMIT 20");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ruta Actual</th><th>Ruta Corregida</th><th>Acción</th></tr>";
    
    $corregidas_productos = 0;
    
    foreach ($productos as $prod) {
        $ruta_actual = $prod['imagen'];
        $ruta_corregida = $ruta_actual;
        
        // Mismas correcciones
        if (strpos($ruta_actual, '/img/productos/') === 0) {
            $ruta_corregida = substr($ruta_actual, 1);
        } elseif (strpos($ruta_actual, 'uploads/productos/') === 0) {
            $ruta_corregida = str_replace('uploads/productos/', 'img/productos/', $ruta_actual);
        } elseif (!strpos($ruta_actual, '/') && !strpos($ruta_actual, '\\') && $ruta_actual !== 'default.jpg') {
            $ruta_corregida = 'img/productos/' . $ruta_actual;
        }
        
        $necesita_correccion = ($ruta_actual !== $ruta_corregida);
        
        echo "<tr>";
        echo "<td>{$prod['id']}</td>";
        echo "<td>" . htmlspecialchars($prod['nombre']) . "</td>";
        echo "<td style='color: " . ($necesita_correccion ? 'red' : 'green') . ";'>{$ruta_actual}</td>";
        echo "<td>{$ruta_corregida}</td>";
        echo "<td>" . ($necesita_correccion ? '❌ Necesita corrección' : '✅ OK') . "</td>";
        echo "</tr>";
        
        if ($necesita_correccion) {
            $stmt_update = $pdo->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
            $stmt_update->execute([$ruta_corregida, $prod['id']]);
            $corregidas_productos++;
        }
    }
    
    echo "</table>";
    echo "<p><strong>Total de rutas corregidas en productos:</strong> $corregidas_productos</p>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Proceso completado</h3>";
    echo "<p>Total corregido: " . ($corregidas + $corregidas_productos) . " rutas</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th { background: #6a994e; color: white; padding: 10px; }
td { padding: 8px; }
tr:nth-child(even) { background: #f2f2f2; }
</style>
