<?php
// ENDPOINT PARA SUBIR Y ACTUALIZAR AVATAR DEL USUARIO
// Esta API maneja la subida de imágenes de perfil con validaciones de seguridad
// Permite recorte de imagen y actualiza la base de datos

// session_start(): Iniciamos la sesión para poder acceder a los datos del usuario logueado
session_start();

// Limpiar cualquier output previo
ob_clean();

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// require_once: Incluimos los archivos necesarios una sola vez
require_once '../api/api_base.php'; // Funciones básicas de API
require_once '../config/database.php'; // Conexión a la base de datos
require_once '../includes/functions.php'; // Funciones auxiliares

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// validateMethod(): Verificamos que solo se use POST para esta API
// POST: Es para mandar datos al servidor (en este caso, la imagen)
validateMethod(['POST']);

// Verificar que el usuario esté logueado
// requireLogin(): Función que verifica si hay una sesión activa
requireLogin();

// $user: Obtenemos los datos del usuario actual desde la sesión
$user = getCurrentUser();

try {
    // getConnection(): Nos conectamos a la base de datos
    $pdo = getConnection();
    
    // VALIDACIÓN DEL ARCHIVO SUBIDO
    // $_FILES: Variable superglobal de PHP que contiene info de archivos subidos
    // ['avatar']: El nombre del input file en el formulario
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        // Si no hay archivo o hay error en la subida
        sendError('No se pudo subir la imagen. Inténtalo de nuevo.', 400);
    }
    
    // $file: Variable con toda la info del archivo subido
    $file = $_FILES['avatar'];
    
    // VALIDACIONES DE SEGURIDAD
    // filesize(): Obtiene el tamaño del archivo en bytes
    $maxSize = 25 * 1024 * 1024; // 25MB en bytes (1024 bytes = 1KB)
    if ($file['size'] > $maxSize) {
        sendError('La imagen es demasiado grande. Máximo 25MB permitido.', 400);
    }
    
    // getimagesize(): Función de PHP que verifica si es realmente una imagen
    // También nos da info como ancho, alto y tipo MIME
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        sendError('El archivo no es una imagen válida.', 400);
    }
    
    // Tipos MIME permitidos: Solo imágenes comunes y seguras
    // MIME: Tipo estándar que identifica el formato del archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($imageInfo['mime'], $allowedTypes)) {
        sendError('Tipo de imagen no permitido. Usa JPG, PNG, GIF o WebP.', 400);
    }
    
    // Validar dimensiones mínimas: Avatar debe ser al menos 100x100
    if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
        sendError('La imagen debe ser de al menos 100x100 píxeles.', 400);
    }
    
    // CREAR DIRECTORIO DE UPLOADS SI NO EXISTE
    // uploads/avatars/: Carpeta donde guardaremos los avatares
    $uploadDir = '../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        // mkdir(): Crea directorio con permisos 0755 (lectura/escritura para owner, lectura para otros)
        // true: Para crear directorios anidados si no existen
        mkdir($uploadDir, 0755, true);
    }
    
    // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
    // uniqid(): Genera un ID único basado en el timestamp
    // $user['id']: ID del usuario para que cada uno tenga su espacio
    // Siempre usamos .jpg porque el recorte se guarda como JPEG
    $fileName = 'avatar_' . $user['id'] . '_' . uniqid() . '.jpg';
    $filePath = $uploadDir . $fileName;
    
    // PROCESAR IMAGEN RECORTADA SI VIENE EN LA SOLICITUD
    // Los datos de recorte vienen en $_POST cuando se envía con FormData
    $cropData = null;
    
    error_log("Procesando datos de recorte...");
    
    if (isset($_POST['cropData'])) {
        $cropDataRaw = $_POST['cropData'];
        error_log("Raw crop data: " . $cropDataRaw);
        
        $cropData = json_decode($cropDataRaw, true);
        error_log("Decoded crop data: " . print_r($cropData, true));
        
        // Verificar si la decodificación fue exitosa
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error en upload-avatar: " . json_last_error_msg());
            sendError('Error al procesar datos de recorte: ' . json_last_error_msg(), 400);
        }
    } else {
        error_log("No se encontraron datos de cropData en POST");
    }
    
    error_log("Datos de recorte procesados. CropData existe: " . ($cropData ? 'SI' : 'NO'));
    
    if ($cropData && isset($cropData['cropData'])) {
        error_log("Iniciando proceso de recorte...");
        // Si hay datos de recorte, procesamos la imagen
        $crop = $cropData['cropData'];
        error_log("Crop parameters: " . print_r($crop, true));
        
        // Validar que los datos de recorte sean válidos
        if (!isset($crop['x']) || !isset($crop['y']) || !isset($crop['width']) || !isset($crop['height'])) {
            sendError('Datos de recorte inválidos.', 400);
        }
        
        // Validar que los valores sean numéricos y positivos
        if (!is_numeric($crop['x']) || !is_numeric($crop['y']) || 
            !is_numeric($crop['width']) || !is_numeric($crop['height']) ||
            $crop['width'] <= 0 || $crop['height'] <= 0) {
            sendError('Valores de recorte inválidos.', 400);
        }
        
        // CREAR IMAGEN DESDE EL ARCHIVO TEMPORAL
        // Creamos un recurso de imagen según el tipo
        $sourceImage = null;
        error_log("Creando imagen desde archivo temporal. MIME: " . $imageInfo['mime']);
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
            case 'image/jpg':
                error_log("Procesando JPEG...");
                $sourceImage = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                error_log("Procesando PNG...");
                $sourceImage = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                error_log("Procesando GIF...");
                $sourceImage = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                error_log("Procesando WebP...");
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($file['tmp_name']);
                } else {
                    error_log("WebP no soportado");
                    sendError('El servidor no soporta imágenes WebP.', 400);
                }
                break;
            default:
                error_log("Tipo de imagen no soportado: " . $imageInfo['mime']);
                sendError('Tipo de imagen no soportado para recorte.', 400);
        }
        
        // Verificar que la imagen se creó correctamente
        if ($sourceImage === false) {
            error_log("Error: No se pudo crear la imagen desde el archivo");
            sendError('Error al procesar la imagen. Intenta con otra imagen.', 400);
        }
        
        error_log("Imagen origen creada exitosamente");
        
        // Obtener dimensiones de la imagen original
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        error_log("Dimensiones originales: {$originalWidth}x{$originalHeight}");
        
        // CREAR IMAGEN RECORTADA
        // Tamaño final del avatar: 300x300 píxeles (cuadrado)
        $avatarSize = 300;
        error_log("Creando imagen recortada de {$avatarSize}x{$avatarSize}");
        
        $croppedImage = imagecreatetruecolor($avatarSize, $avatarSize);
        
        // Verificar que la imagen destino se creó correctamente
        if ($croppedImage === false) {
            error_log("Error: No se pudo crear la imagen recortada");
            imagedestroy($sourceImage);
            sendError('Error al crear la imagen recortada.', 500);
        }
        
        // Establecer color de fondo blanco para transparencias
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        error_log("Fondo blanco establecido");
        
        // Validar que las coordenadas de recorte estén dentro de los límites
        $cropX = max(0, min((int)$crop['x'], $originalWidth - 1));
        $cropY = max(0, min((int)$crop['y'], $originalHeight - 1));
        $cropW = max(1, min((int)$crop['width'], $originalWidth - $cropX));
        $cropH = max(1, min((int)$crop['height'], $originalHeight - $cropY));
        
        error_log("Coordenadas de recorte ajustadas: x=$cropX, y=$cropY, w=$cropW, h=$cropH");
        
        // imagecopyresampled(): Copia y redimensiona parte de la imagen
        error_log("Iniciando imagecopyresampled...");
        $result = imagecopyresampled(
            $croppedImage, $sourceImage,
            0, 0, 
            $cropX, $cropY,
            $avatarSize, $avatarSize,
            $cropW, $cropH
        );
        
        // Verificar que el recorte se realizó correctamente
        if ($result === false) {
            error_log("Error: imagecopyresampled falló");
            imagedestroy($sourceImage);
            imagedestroy($croppedImage);
            sendError('Error al recortar la imagen. Intenta de nuevo.', 500);
        }
        
        error_log("Recorte exitoso");
        
        // GUARDAR IMAGEN PROCESADA
        // Asegurar que el directorio tenga permisos de escritura
        if (!is_writable($uploadDir)) {
            error_log("Error: Directorio no escribible: " . $uploadDir);
            imagedestroy($sourceImage);
            imagedestroy($croppedImage);
            sendError('Error: No se puede escribir en el directorio de uploads.', 500);
        }
        
        error_log("Guardando imagen en: " . $filePath);
        
        // Guardamos como JPEG con calidad 85 (buen balance calidad/tamaño)
        $saved = imagejpeg($croppedImage, $filePath, 85);
        
        // Verificar que la imagen se guardó correctamente
        if ($saved === false) {
            error_log("Error: No se pudo guardar la imagen");
            imagedestroy($sourceImage);
            imagedestroy($croppedImage);
            sendError('Error al guardar la imagen recortada.', 500);
        }
        
        error_log("Imagen guardada exitosamente: " . $filePath);
        
        // LIBERAR MEMORIA
        // imagedestroy(): Libera la memoria usada por las imágenes
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        
        error_log("Memoria liberada");
        
    } else {
        error_log("Sin datos de recorte, procesando imagen sin recortar...");
        // Si no hay recorte, procesamos la imagen para normalizarla
        // Verificar que el directorio tenga permisos de escritura
        if (!is_writable($uploadDir)) {
            sendError('Error: No se puede escribir en el directorio de uploads.', 500);
        }
        
        // Crear imagen desde el archivo original
        $sourceImage = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($file['tmp_name']);
                } else {
                    sendError('El servidor no soporta imágenes WebP.', 400);
                }
                break;
            default:
                sendError('Tipo de imagen no soportado.', 400);
        }
        
        if ($sourceImage === false) {
            sendError('Error al procesar la imagen.', 400);
        }
        
        // Redimensionar a 300x300 manteniendo proporción
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        $avatarSize = 300;
        
        // Calcular dimensiones para mantener proporción y centrar
        if ($originalWidth > $originalHeight) {
            $newWidth = $avatarSize;
            $newHeight = ($originalHeight * $avatarSize) / $originalWidth;
            $offsetX = 0;
            $offsetY = ($avatarSize - $newHeight) / 2;
        } else {
            $newHeight = $avatarSize;
            $newWidth = ($originalWidth * $avatarSize) / $originalHeight;
            $offsetX = ($avatarSize - $newWidth) / 2;
            $offsetY = 0;
        }
        
        // Crear imagen cuadrada con fondo blanco
        $finalImage = imagecreatetruecolor($avatarSize, $avatarSize);
        $white = imagecolorallocate($finalImage, 255, 255, 255);
        imagefill($finalImage, 0, 0, $white);
        
        // Copiar imagen redimensionada
        imagecopyresampled(
            $finalImage, $sourceImage,
            $offsetX, $offsetY,
            0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        // Guardar como JPEG
        $saved = imagejpeg($finalImage, $filePath, 85);
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($finalImage);
        
        if ($saved === false) {
            sendError('Error al guardar la imagen.', 500);
        }
    }
    
    // ELIMINAR AVATAR ANTERIOR SI EXISTE
    // $stmt: Preparamos consulta para obtener el avatar actual
    $stmt = $pdo->prepare("SELECT avatar_path FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $currentAvatar = $stmt->fetchColumn();
    
    // Si tiene avatar anterior y no es el por defecto, lo eliminamos
    if ($currentAvatar && $currentAvatar !== 'img/usuario.png' && file_exists('../' . $currentAvatar)) {
        // unlink(): Elimina un archivo del sistema
        unlink('../' . $currentAvatar);
    }
    
    // ACTUALIZAR BASE DE DATOS
    // Guardamos la ruta del nuevo avatar en la base de datos
    $avatarPath = 'uploads/avatars/' . $fileName;
    $stmt = $pdo->prepare("UPDATE usuarios SET avatar_path = ? WHERE id = ?");
    $stmt->execute([$avatarPath, $user['id']]);
    
    // ACTUALIZAR SESIÓN
    // Actualizamos la info del usuario en la sesión para que se vea inmediatamente
    $_SESSION['user']['avatar_path'] = $avatarPath;
    
    // RESPUESTA EXITOSA
    sendSuccess([
        'avatar_path' => $avatarPath,
        'avatar_url' => $avatarPath, // Para compatibilidad con el frontend
        'message' => 'Avatar actualizado correctamente'
    ], 'Avatar actualizado correctamente');
    
} catch (Exception $e) {
    // error_log(): Registra el error en los logs del servidor
    error_log("Error en upload-avatar.php: " . $e->getMessage());
    
    // Enviamos error genérico al usuario (no exponemos detalles internos)
    sendError('Error interno del servidor. Inténtalo de nuevo.', 500);
}

// Final del archivo - sin etiqueta de cierre para evitar problemas de output