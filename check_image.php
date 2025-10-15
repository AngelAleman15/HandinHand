<?php
// Script temporal para verificar las dimensiones de la imagen

$imageFile = 'temp_test_image.png';

if (!file_exists($imageFile)) {
    die("Archivo no encontrado\n");
}

echo "Tamaño del archivo: " . filesize($imageFile) . " bytes\n";

$imageInfo = getimagesize($imageFile);

if ($imageInfo === false) {
    die("No es una imagen válida\n");
}

echo "Dimensiones: {$imageInfo[0]}x{$imageInfo[1]} píxeles\n";
echo "Tipo MIME: {$imageInfo['mime']}\n";
echo "Canales: " . (isset($imageInfo['channels']) ? $imageInfo['channels'] : 'N/A') . "\n";
echo "Bits: " . (isset($imageInfo['bits']) ? $imageInfo['bits'] : 'N/A') . "\n";

// Verificar si cumple con las validaciones
if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
    echo "\n❌ ERROR: La imagen es muy pequeña. Debe ser al menos 100x100 píxeles\n";
    echo "   Dimensiones actuales: {$imageInfo[0]}x{$imageInfo[1]}\n";
} else {
    echo "\n✅ La imagen cumple con el tamaño mínimo\n";
}

// Verificar tipo de imagen
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($imageInfo['mime'], $allowedTypes)) {
    echo "❌ ERROR: Tipo de imagen no permitido\n";
    echo "   Tipo actual: {$imageInfo['mime']}\n";
    echo "   Tipos permitidos: " . implode(', ', $allowedTypes) . "\n";
} else {
    echo "✅ El tipo de imagen es válido\n";
}
?>
