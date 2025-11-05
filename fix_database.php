<?php
/**
 * Script para corregir los nombres de imágenes en la base de datos
 */

require_once 'config/database.php';

try {
    // Conectar a la base de datos
    $db = getConnection();
    
    echo "<h2>🔧 Corrección de Base de Datos - Productos</h2>";
    echo "<hr>";
    
    // Corregir producto ID=4 (Smartphone Samsung)
    $sql1 = "UPDATE productos SET imagen = 'img/productos/smartphonesamsung.jpg' WHERE id = 4";
    $result1 = $db->query($sql1);
    echo "✅ Producto ID=4 (Smartphone): Imagen corregida<br>";
    
    // Corregir producto ID=5 (Chaqueta de Cuero)
    $sql2 = "UPDATE productos SET imagen = 'img/productos/chaquetadecuero.jpg' WHERE id = 5";
    $result2 = $db->query($sql2);
    echo "✅ Producto ID=5 (Chaqueta): Imagen corregida<br>";
    
    // Corregir producto ID=1 (Zapatos)
    $sql3 = "UPDATE productos SET imagen = 'img/productos/zapatosdeportivosnike.jpg' WHERE id = 1";
    $result3 = $db->query($sql3);
    echo "✅ Producto ID=1 (Zapatos): Imagen corregida<br>";
    
    // Corregir producto ID=2 (Guitarra)
    $sql4 = "UPDATE productos SET imagen = 'img/productos/guitarraacustica.jpg' WHERE id = 2";
    $result4 = $db->query($sql4);
    echo "✅ Producto ID=2 (Guitarra): Imagen corregida<br>";
    
    // Corregir producto ID=6 (Bicicleta)
    $sql5 = "UPDATE productos SET imagen = 'img/productos/bicicletademontana.jpg' WHERE id = 6";
    $result5 = $db->query($sql5);
    echo "✅ Producto ID=6 (Bicicleta): Imagen corregida<br>";
    
    // Asegurar que todos los productos estén disponibles
    $sql6 = "UPDATE productos SET estado = 'disponible' WHERE estado IS NULL OR estado = ''";
    $result6 = $db->query($sql6);
    echo "✅ Estados de productos verificados<br>";
    
    echo "<hr>";
    echo "<h3>📋 Productos Actualizados:</h3>";
    
    // Mostrar todos los productos
    $query = "SELECT id, nombre, imagen, estado FROM productos ORDER BY id";
    $result = $db->query($query);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Imagen</th><th>Estado</th><th>Archivo Existe</th></tr>";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $imagePath = str_replace('img/productos/', '', $row['imagen']);
        $fullPath = __DIR__ . '/img/productos/' . $imagePath;
        $exists = file_exists($fullPath) ? '✅ Sí' : '❌ No';
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['imagen']}</td>";
        echo "<td>{$row['estado']}</td>";
        echo "<td>{$exists}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>✅ ¡Corrección completada exitosamente!</h3>";
    echo "<p><a href='index.php'>← Volver al inicio</a> | <a href='producto.php?id=4'>Ver Producto ID=4</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
