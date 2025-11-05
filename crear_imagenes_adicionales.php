<?php
// Script para crear imágenes adicionales (duplicados para prueba)

$productos = [
    'smartphonesamsung',
    'zapatosdeportivosnike',
    'guitarraacustica'
];

foreach ($productos as $producto) {
    $imagenOriginal = "img/productos/{$producto}.jpg";
    
    if (file_exists($imagenOriginal)) {
        // Crear versión -1 (renombrar la original)
        $nuevaOriginal = "img/productos/{$producto}-1.jpg";
        if (!file_exists($nuevaOriginal)) {
            copy($imagenOriginal, $nuevaOriginal);
            echo "✓ Creado: {$nuevaOriginal}<br>";
        }
        
        // Crear versión -2
        $imagen2 = "img/productos/{$producto}-2.jpg";
        if (!file_exists($imagen2)) {
            copy($imagenOriginal, $imagen2);
            echo "✓ Creado: {$imagen2}<br>";
        }
        
        // Crear versión -3
        $imagen3 = "img/productos/{$producto}-3.jpg";
        if (!file_exists($imagen3)) {
            copy($imagenOriginal, $imagen3);
            echo "✓ Creado: {$imagen3}<br>";
        }
    } else {
        echo "✗ No existe: {$imagenOriginal}<br>";
    }
}

echo "<br><strong>Proceso completado!</strong><br>";
echo "<a href='producto.php?id=1'>Ver Producto 1 (Zapatos)</a><br>";
echo "<a href='producto.php?id=2'>Ver Producto 2 (Guitarra)</a><br>";
echo "<a href='producto.php?id=4'>Ver Producto 4 (Smartphone)</a><br>";
?>
