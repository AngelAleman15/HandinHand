<?php
/**
 * Script para crear una imagen default.jpg placeholder
 * Ejecuta este archivo UNA VEZ: http://localhost/MisTrabajos/HandinHand/crear-imagen-default.php
 */

// Crear imagen de 800x800 pixels
$width = 800;
$height = 800;
$image = imagecreatetruecolor($width, $height);

// Colores
$bg_color = imagecolorallocate($image, 248, 249, 250); // #f8f9fa (gris claro)
$border_color = imagecolorallocate($image, 106, 153, 78); // #6a994e (verde HandinHand)
$icon_color = imagecolorallocate($image, 173, 181, 189); // #adb5bd (gris medio)
$text_color = imagecolorallocate($image, 108, 117, 125); // #6c757d (gris texto)

// Fondo
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Borde
imagerectangle($image, 10, 10, $width - 10, $height - 10, $border_color);
imagerectangle($image, 12, 12, $width - 12, $height - 12, $border_color);

// Icono de imagen (rectángulo simulando una imagen)
$icon_size = 200;
$icon_x = ($width - $icon_size) / 2;
$icon_y = ($height - $icon_size) / 2 - 50;

// Rectángulo del icono
imagerectangle($image, $icon_x, $icon_y, $icon_x + $icon_size, $icon_y + $icon_size, $icon_color);
imagerectangle($image, $icon_x + 2, $icon_y + 2, $icon_x + $icon_size - 2, $icon_y + $icon_size - 2, $icon_color);

// Círculo en la esquina (sol/luna)
$circle_x = $icon_x + 50;
$circle_y = $icon_y + 50;
$circle_radius = 25;
imagefilledellipse($image, $circle_x, $circle_y, $circle_radius * 2, $circle_radius * 2, $icon_color);

// Montañas (triángulos)
$mountain1 = array(
    $icon_x + 20, $icon_y + $icon_size - 20,
    $icon_x + 80, $icon_y + 100,
    $icon_x + 140, $icon_y + $icon_size - 20
);
imagefilledpolygon($image, $mountain1, 3, $icon_color);

$mountain2 = array(
    $icon_x + 100, $icon_y + $icon_size - 20,
    $icon_x + 150, $icon_y + 120,
    $icon_x + 200, $icon_y + $icon_size - 20
);
imagefilledpolygon($image, $mountain2, 3, $icon_color);

// Texto "Sin Imagen"
$font_path = 'C:/Windows/Fonts/arial.ttf';
$font_size = 24;
$text = "Sin Imagen";

if (file_exists($font_path)) {
    $text_box = imagettfbbox($font_size, 0, $font_path, $text);
    $text_width = abs($text_box[4] - $text_box[0]);
    $text_x = ($width - $text_width) / 2;
    $text_y = $icon_y + $icon_size + 80;
    
    imagettftext($image, $font_size, 0, $text_x, $text_y, $text_color, $font_path, $text);
    
    // Texto secundario
    $font_size_small = 16;
    $text2 = "HandinHand";
    $text_box2 = imagettfbbox($font_size_small, 0, $font_path, $text2);
    $text_width2 = abs($text_box2[4] - $text_box2[0]);
    $text_x2 = ($width - $text_width2) / 2;
    $text_y2 = $text_y + 35;
    
    imagettftext($image, $font_size_small, 0, $text_x2, $text_y2, $border_color, $font_path, $text2);
} else {
    // Fallback si no encuentra la fuente
    $text_x = ($width / 2) - 60;
    $text_y = $icon_y + $icon_size + 80;
    imagestring($image, 5, $text_x, $text_y, "Sin Imagen", $text_color);
    imagestring($image, 4, $text_x + 10, $text_y + 30, "HandinHand", $border_color);
}

// Guardar imagen
$output_path = __DIR__ . '/img/productos/default.jpg';
$success = imagejpeg($image, $output_path, 90);

// Liberar memoria
imagedestroy($image);

// Mostrar resultado
if ($success) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Imagen Default Creada</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: #f8f9fa;
            }
            .success-box {
                background: #d4edda;
                border: 2px solid #c3e6cb;
                border-radius: 12px;
                padding: 30px;
                text-align: center;
            }
            .success-box h1 {
                color: #155724;
                margin: 0 0 20px 0;
            }
            .success-box img {
                max-width: 400px;
                border: 3px solid #6a994e;
                border-radius: 8px;
                margin: 20px 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .info {
                background: #e3f2fd;
                border: 1px solid #90caf9;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
                text-align: left;
            }
            .info code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                color: #d63384;
            }
            .btn {
                display: inline-block;
                background: #6a994e;
                color: white;
                padding: 12px 24px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                margin-top: 20px;
                transition: all 0.3s;
            }
            .btn:hover {
                background: #558040;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <h1>✅ Imagen Default Creada Exitosamente</h1>
            <p>La imagen placeholder se ha guardado en:</p>
            <p><strong>" . $output_path . "</strong></p>
            
            <h3>Vista previa:</h3>
            <img src='img/productos/default.jpg?v=" . time() . "' alt='Default Image'>
            
            <div class='info'>
                <h3>ℹ️ Información:</h3>
                <ul>
                    <li>📏 Dimensiones: 800x800 pixels</li>
                    <li>📦 Tamaño: " . round(filesize($output_path) / 1024, 2) . " KB</li>
                    <li>🎨 Formato: JPEG (90% calidad)</li>
                    <li>📂 Ruta: <code>img/productos/default.jpg</code></li>
                </ul>
                
                <p><strong>✨ ¡Los errores 404 de default.jpg desaparecerán ahora!</strong></p>
                
                <p style='background: #fff3cd; padding: 10px; border-radius: 5px; border: 1px solid #ffc107;'>
                    ⚠️ Puedes eliminar este archivo (<code>crear-imagen-default.php</code>) después de ejecutarlo.
                </p>
            </div>
            
            <a href='index.php' class='btn'>← Volver al Inicio</a>
            <a href='mis-productos.php' class='btn'>Ver Mis Productos</a>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Error al Crear Imagen</title>
        <style>
            body {
                font-family: sans-serif;
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: #f8f9fa;
            }
            .error-box {
                background: #f8d7da;
                border: 2px solid #f5c6cb;
                border-radius: 12px;
                padding: 30px;
                text-align: center;
            }
            .error-box h1 {
                color: #721c24;
            }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h1>❌ Error al crear la imagen</h1>
            <p>No se pudo guardar el archivo. Verifica los permisos de la carpeta:</p>
            <p><strong>" . dirname($output_path) . "</strong></p>
        </div>
    </body>
    </html>";
}
?>
