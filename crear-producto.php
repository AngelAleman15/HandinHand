<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

$page_title = "Crear Producto - HandinHand";
$body_class = "body-create-product";

// Procesar formulario si se envió
$message = '';
$error = '';
$producto_creado_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: Log completo de lo que llega
    error_log("=== CREAR PRODUCTO - INICIO ===");
    error_log("POST nombre: " . ($_POST['nombre'] ?? 'NO ENVIADO'));
    error_log("FILES isset: " . (isset($_FILES['imagenes']) ? 'SI' : 'NO'));
    if (isset($_FILES['imagenes'])) {
        error_log("FILES imagenes name: " . print_r($_FILES['imagenes']['name'], true));
        error_log("FILES imagenes error: " . print_r($_FILES['imagenes']['error'], true));
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $estado = trim($_POST['estado'] ?? 'disponible');
    $departamento_id = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
    $ciudad_id = !empty($_POST['ciudad_id']) ? (int)$_POST['ciudad_id'] : null;
    
    if (empty($nombre) || empty($descripcion)) {
        $error = 'El nombre y descripción son obligatorios';
    } else {
        try {
            require_once 'config/database.php';
            $pdo = getConnection();
            
            $pdo->beginTransaction();
            
            // Crear producto con imagen por defecto inicialmente
            $imagen_default = 'img/productos/default.jpg';
            $stmt = $pdo->prepare("INSERT INTO productos (user_id, nombre, descripcion, imagen, categoria, estado, departamento_id, ciudad_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $nombre, $descripcion, $imagen_default, $categoria, $estado, $departamento_id, $ciudad_id]);
            
            $producto_creado_id = $pdo->lastInsertId();
            
            // Procesar imágenes si se subieron
            $imagenes_subidas = 0;
            $primera_imagen = null;
            
            // DEBUG: Log para verificar si llegan archivos
            error_log("=== PROCESANDO IMAGENES ===");
            error_log("isset FILES[imagenes]: " . (isset($_FILES['imagenes']) ? 'SI' : 'NO'));
            
            if (isset($_FILES['imagenes'])) {
                error_log("FILES[imagenes][name][0]: " . ($_FILES['imagenes']['name'][0] ?? 'VACIO'));
                error_log("empty name[0]: " . (empty($_FILES['imagenes']['name'][0]) ? 'SI' : 'NO'));
            }
            
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                error_log("ENTRANDO AL BLOQUE DE UPLOAD");
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $dir = __DIR__ . '/img/productos/';
                
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $files = $_FILES['imagenes'];
                $fileCount = min(count($files['name']), 6); // Máximo 6 imágenes
                
                error_log("Número de archivos a procesar: $fileCount");
                
                for ($i = 0; $i < $fileCount; $i++) {
                    error_log("Procesando imagen $i: " . $files['name'][$i]);
                    
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        error_log("Error en upload de imagen $i: " . $files['error'][$i]);
                        continue;
                    }
                    
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) {
                        error_log("Extensión no permitida: $ext");
                        continue;
                    }
                    
                    // Validar tamaño (5MB)
                    if ($files['size'][$i] > 5 * 1024 * 1024) {
                        error_log("Archivo muy grande: " . $files['size'][$i] . " bytes");
                        continue;
                    }
                    
                    $filename = 'prod_' . $producto_creado_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $dest = $dir . $filename;
                    
                    error_log("Intentando mover archivo a: $dest");
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                        error_log("Archivo movido exitosamente: $filename");
                        
                        // La primera imagen será la principal
                        $es_principal = ($imagenes_subidas === 0) ? 1 : 0;
                        
                        // Guardar con la ruta relativa completa
                        $ruta_imagen = 'img/productos/' . $filename;
                        
                        $stmt = $pdo->prepare('INSERT INTO producto_imagenes (producto_id, imagen, es_principal) VALUES (?, ?, ?)');
                        $stmt->execute([$producto_creado_id, $ruta_imagen, $es_principal]);
                        
                        error_log("Imagen insertada en BD: $ruta_imagen (principal: $es_principal)");
                        
                        if ($es_principal) {
                            $primera_imagen = 'img/productos/' . $filename;
                        }
                        
                        $imagenes_subidas++;
                    } else {
                        error_log("ERROR: No se pudo mover el archivo: " . $files['tmp_name'][$i]);
                    }
                }
                
                // Actualizar la imagen principal del producto si se subió al menos una
                if ($primera_imagen) {
                    $stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
                    $stmt->execute([$primera_imagen, $producto_creado_id]);
                }
            }
            
            $pdo->commit();
            
            if ($imagenes_subidas > 0) {
                $message = "Producto creado exitosamente con $imagenes_subidas imagen(es).";
            } else {
                $message = 'Producto creado exitosamente. Puedes agregar imágenes desde "Editar Producto".';
            }
            
            // Si es una petición AJAX (fetch), devolver JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'producto_id' => $producto_creado_id,
                    'message' => $message
                ]);
                exit;
            }
            
            // Limpiar el formulario redirigiendo
            header("Location: editar-producto.php?id=$producto_creado_id&success=created");
            exit;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error al crear el producto: ' . $e->getMessage();
        }
    }
}

// Cargar categorías disponibles para el autocomplete
$categorias_disponibles = getCategoriasUnicas();

// Cargar departamentos y ciudades para el selector de ubicaciones
try {
    require_once 'config/database.php';
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre");
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departamentos = [];
    error_log("Error al cargar departamentos: " . $e->getMessage());
}

include 'includes/header.php';
?>

<style>
/* Container principal */
.create-product-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Header moderno */
.create-product-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 32px 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(106, 153, 78, 0.15);
    margin-bottom: 30px;
    text-align: center;
}

.create-product-header h1 {
    color: #2c3e50;
    font-size: 2em;
    margin: 0 0 8px 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.create-product-header h1 i {
    color: #6a994e;
}

.create-product-header p {
    color: #6c757d;
    font-size: 1.1em;
    margin: 0;
}

/* Layout de dos columnas */
.create-product-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 30px;
}

/* Columna de imágenes */
.images-column {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    padding: 30px;
}

.images-section-title {
    color: #2c3e50;
    font-size: 1.3em;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #6a994e;
    display: flex;
    align-items: center;
    gap: 10px;
}

.images-section-title i {
    color: #6a994e;
}

/* Imagen principal preview */
.main-image-preview {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    background: #f8f9fa;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-image-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #6a994e;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Galería de miniaturas */
.images-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.gallery-item:hover {
    border-color: #6a994e;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.2);
}

.gallery-item.active {
    border-color: #6a994e;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.2);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-item-add {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #6a994e;
    font-weight: 600;
    background: #f0f7ed;
    border: 2px dashed #6a994e;
}

.gallery-item-add:hover {
    background: #e1f0db;
}

.gallery-item-add i {
    font-size: 2em;
}

.gallery-item-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .gallery-item-remove {
    opacity: 1;
}

.gallery-item-remove:hover {
    background: rgba(220, 53, 69, 1);
}

/* Botón de subir */
.upload-images-btn {
    width: 100%;
    padding: 12px;
    background: #6a994e;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.upload-images-btn:hover:not(:disabled) {
    background: #558040;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.upload-images-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.upload-message {
    margin-top: 12px;
    text-align: center;
    font-size: 0.9em;
    font-weight: 600;
}

/* Columna de formulario */
.form-column {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    padding: 40px;
}

.form-section-title {
    color: #2c3e50;
    font-size: 1.3em;
    margin: 0 0 25px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #6a994e;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title i {
    color: #6a994e;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.95em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: #6a994e;
}

.form-group label small {
    font-weight: normal;
    color: #6c757d;
    margin-left: auto;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #6a994e;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.1);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

/* Sistema de categorías (igual que editar-producto) */
.categories-container {
    position: relative;
}

.categories-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    min-height: 50px;
    cursor: text;
    transition: all 0.3s ease;
}

.categories-tags:focus-within {
    border-color: #6a994e;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.1);
}

.category-tag {
    background: #6a994e;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    animation: slideIn 0.2s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.category-tag i {
    font-size: 0.85em;
}

.category-tag-remove {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.category-tag-remove:hover {
    background: rgba(255, 255, 255, 0.4);
}

.category-input-wrapper {
    flex: 1;
    min-width: 150px;
}

.category-input-wrapper input {
    border: none;
    outline: none;
    padding: 6px;
    font-size: 0.95em;
    width: 100%;
}

/* Autocomplete de categorías */
.categories-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #6a994e;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    display: none;
}

.categories-autocomplete.show {
    display: block;
}

.autocomplete-item {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background 0.2s;
}

.autocomplete-item:hover,
.autocomplete-item.highlighted {
    background: #f0f7ed;
}

.autocomplete-item i {
    color: #6a994e;
}

.autocomplete-count {
    margin-left: auto;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    color: #6c757d;
}

.autocomplete-empty {
    padding: 15px;
    text-align: center;
    color: #6c757d;
}

/* Selector de estado */
.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 36px;
}

/* Botones de acción */
.btn-group {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #dee2e6;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #6a994e;
    color: white;
}

.btn-primary:hover {
    background: #558040;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Alertas */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert i {
    font-size: 1.2em;
}

/* Info box */
.info-box {
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 25px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.info-box i {
    color: #1976d2;
    font-size: 1.3em;
    margin-top: 2px;
}

.info-box p {
    margin: 0;
    color: #0d47a1;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 1024px) {
    .create-product-layout {
        grid-template-columns: 1fr;
    }
    
    .images-column {
        order: 2;
    }
    
    .form-column {
        order: 1;
    }
}

@media (max-width: 768px) {
    .create-product-container {
        padding: 20px 15px;
    }
    
    .images-column,
    .form-column {
        padding: 25px 20px;
    }
    
    .create-product-header {
        padding: 25px 20px;
    }
    
    .create-product-header h1 {
        font-size: 1.5em;
        flex-direction: column;
    }
    
    .btn-group {
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .images-gallery {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="create-product-container">
    <!-- Header -->
    <div class="create-product-header">
        <h1>
            <i class="fas fa-plus-circle"></i>
            Crear Nuevo Producto
        </h1>
        <p>Completa la información de tu producto para publicarlo</p>
    </div>

    <!-- Info box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <p>
            <strong>💡 Consejo:</strong> Después de crear el producto, podrás subir hasta 6 imágenes. 
            Asegúrate de proporcionar información clara y completa para atraer más interés.
        </p>
    </div>

    <!-- Mensajes -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Layout de dos columnas -->
    <div class="create-product-layout">
        <!-- Columna de Imágenes -->
        <div class="images-column">
            <h3 class="images-section-title">
                <i class="fas fa-images"></i>
                Imágenes del Producto
                <small style="font-size: 0.7em; color: #6c757d; font-weight: normal; margin-left: auto;">
                    Máximo 6 imágenes
                </small>
            </h3>

            <!-- Vista previa de imágenes seleccionadas -->
            <div id="images-preview-container">
                <div class="main-image-preview" id="main-preview">
                    <div style="text-align: center; color: #adb5bd;">
                        <i class="fas fa-image" style="font-size: 4em; margin-bottom: 10px;"></i>
                        <p>Vista previa de imagen principal</p>
                    </div>
                </div>

                <!-- Galería de miniaturas -->
                <div class="images-gallery" id="thumbnails-gallery">
                    <!-- Las miniaturas se agregarán dinámicamente aquí -->
                </div>

                <!-- Input de archivos (oculto) -->
                <input 
                    type="file" 
                    id="input-imagenes-producto" 
                    name="imagenes[]"
                    accept="image/jpeg,image/png,image/webp,image/jpg,image/gif" 
                    multiple 
                    style="display: none;"
                    onchange="handleImagePreview(this)">
                
                <!-- Botón para seleccionar imágenes -->
                <button type="button" 
                        class="upload-images-btn" 
                        onclick="document.getElementById('input-imagenes-producto').click()">
                    <i class="fas fa-folder-open"></i>
                    Seleccionar Imágenes (0/6)
                </button>
                
                <p style="text-align: center; color: #6c757d; margin-top: 12px; font-size: 0.9em;">
                    <i class="fas fa-info-circle"></i> 
                    Selecciona hasta 6 imágenes. La primera será la principal.
                </p>
            </div>
        </div>

        <!-- Columna de Formulario -->
        <div class="form-column">
            <form method="POST" id="product-form" enctype="multipart/form-data">
                <h3 class="form-section-title">
                    <i class="fas fa-file-alt"></i>
                    Información del Producto
                </h3>

                <!-- Nombre -->
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-tag"></i>
                        Nombre del Producto
                        <small class="required">* Requerido</small>
                    </label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        class="form-control" 
                        placeholder="Ej: iPhone 12 Pro Max 128GB" 
                        required
                        maxlength="100">
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label for="descripcion">
                        <i class="fas fa-align-left"></i>
                        Descripción
                        <small class="required">* Requerido</small>
                    </label>
                    <textarea 
                        id="descripcion" 
                        name="descripcion" 
                        class="form-control" 
                        placeholder="Describe tu producto: condición, características, accesorios incluidos, motivo de intercambio..."
                        required
                        rows="5"></textarea>
                </div>

                <!-- Categorías con autocomplete -->
                <div class="form-group">
                    <label for="category-input">
                        <i class="fas fa-tags"></i>
                        Categorías
                        <small style="font-weight: normal; color: #6c757d;">Presiona Enter para agregar</small>
                    </label>
                    
                    <div class="categories-container">
                        <div class="categories-tags" id="categories-tags" onclick="document.getElementById('category-input').focus()">
                            <!-- Categorías seleccionadas aparecerán aquí -->
                            <div class="category-input-wrapper">
                                <input 
                                    type="text" 
                                    id="category-input" 
                                    placeholder="Escribe una categoría..."
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="categories-autocomplete" id="categories-autocomplete"></div>
                    </div>
                    
                    <input type="hidden" id="categorias-hidden" name="categoria" value="">
                </div>

                <!-- Ubicación -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-map-marker-alt"></i>
                        Ubicación
                        <small style="font-weight: normal; color: #6c757d;">Opcional - Ayuda a los compradores a encontrar tu producto</small>
                    </label>
                    <div class="row">
                        <div class="col-md-6">
                            <select name="departamento_id" id="departamento" class="form-control form-select">
                                <option value="">Seleccionar departamento...</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="ciudad_id" id="ciudad" class="form-control form-select" disabled>
                                <option value="">Primero selecciona un departamento</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Estado -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-toggle-on"></i>
                        Estado
                    </label>
                    <select name="estado" id="estado" class="form-control form-select">
                        <option value="disponible" selected>Disponible</option>
                        <option value="reservado">Reservado</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="btn-group">
                    <a href="mis-productos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="fas fa-save"></i>
                        Crear Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================
// PREVISUALIZACIÓN DE IMÁGENES
// ============================================
let selectedFiles = [];
const MAX_IMAGES = 6;
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

function handleImagePreview(input) {
    const files = Array.from(input.files);
    
    // Validar cantidad
    if (files.length > MAX_IMAGES) {
        Swal.fire({
            icon: 'warning',
            title: 'Demasiadas imágenes',
            text: `Máximo ${MAX_IMAGES} imágenes por producto. Se seleccionarán las primeras ${MAX_IMAGES}.`,
            confirmButtonColor: '#6a994e'
        });
        files.splice(MAX_IMAGES);
    }
    
    // Validar y filtrar archivos
    selectedFiles = [];
    const validFiles = [];
    
    for (const file of files) {
        // Validar tipo
        if (!file.type.match('image.*')) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo inválido',
                text: `"${file.name}" no es una imagen válida`,
                confirmButtonColor: '#6a994e'
            });
            continue;
        }
        
        // Validar tamaño
        if (file.size > MAX_FILE_SIZE) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: `"${file.name}" excede el tamaño máximo de 5MB`,
                confirmButtonColor: '#6a994e'
            });
            continue;
        }
        
        validFiles.push(file);
    }
    
    selectedFiles = validFiles;
    
    // Actualizar botón
    const btn = document.querySelector('.upload-images-btn');
    btn.innerHTML = `<i class="fas fa-folder-open"></i> Seleccionar Imágenes (${selectedFiles.length}/${MAX_IMAGES})`;
    
    // Mostrar previsualizaciones
    renderImagePreviews();
}

function renderImagePreviews() {
    const mainPreview = document.getElementById('main-preview');
    const gallery = document.getElementById('thumbnails-gallery');
    
    // Limpiar galería
    gallery.innerHTML = '';
    
    if (selectedFiles.length === 0) {
        mainPreview.innerHTML = `
            <div style="text-align: center; color: #adb5bd;">
                <i class="fas fa-image" style="font-size: 4em; margin-bottom: 10px;"></i>
                <p>Vista previa de imagen principal</p>
            </div>
        `;
        return;
    }
    
    // Renderizar cada imagen
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Primera imagen en preview principal
            if (index === 0) {
                mainPreview.innerHTML = `
                    <img src="${e.target.result}" alt="Imagen principal">
                    <span class="main-image-badge">
                        <i class="fas fa-star"></i>
                        Principal
                    </span>
                `;
            }
            
            // Todas las imágenes en galería
            const thumbnailDiv = document.createElement('div');
            thumbnailDiv.className = 'gallery-item' + (index === 0 ? ' active' : '');
            thumbnailDiv.innerHTML = `
                <img src="${e.target.result}" alt="Imagen ${index + 1}">
                <button type="button" 
                        class="gallery-item-remove" 
                        onclick="removeImagePreview(${index})"
                        title="Quitar imagen">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            // Click para hacer principal
            thumbnailDiv.addEventListener('click', function(evt) {
                if (!evt.target.closest('.gallery-item-remove')) {
                    makeImagePrincipal(index);
                }
            });
            
            gallery.appendChild(thumbnailDiv);
        };
        
        reader.readAsDataURL(file);
    });
    
    // Botón para agregar más si no llegó al límite
    if (selectedFiles.length < MAX_IMAGES) {
        const addBtn = document.createElement('div');
        addBtn.className = 'gallery-item gallery-item-add';
        addBtn.innerHTML = `
            <i class="fas fa-plus"></i>
            <span>Agregar</span>
        `;
        addBtn.addEventListener('click', () => {
            document.getElementById('input-imagenes-producto').click();
        });
        gallery.appendChild(addBtn);
    }
}

function removeImagePreview(index) {
    event.stopPropagation();
    selectedFiles.splice(index, 1);
    
    // Actualizar el input file
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('input-imagenes-producto').files = dt.files;
    
    // Actualizar botón
    const btn = document.querySelector('.upload-images-btn');
    btn.innerHTML = `<i class="fas fa-folder-open"></i> Seleccionar Imágenes (${selectedFiles.length}/${MAX_IMAGES})`;
    
    renderImagePreviews();
}

function makeImagePrincipal(index) {
    // Mover el archivo al principio del array
    const file = selectedFiles.splice(index, 1)[0];
    selectedFiles.unshift(file);
    
    // Actualizar el input file
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('input-imagenes-producto').files = dt.files;
    
    renderImagePreviews();
    
    Swal.fire({
        icon: 'success',
        title: 'Imagen principal cambiada',
        timer: 1500,
        showConfirmButton: false
    });
}

// ============================================
// SISTEMA DE CATEGORÍAS CON AUTOCOMPLETE
// ============================================
let allCategories = [];
let selectedCategories = new Set();
let highlightedIndex = -1;

// Cargar categorías desde la API
async function loadCategories() {
    try {
        const response = await fetch('api/get-categorias.php');
        const data = await response.json();
        
        if (data.success) {
            allCategories = data.categorias;
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
}

// Mostrar autocomplete
function showAutocomplete(categories) {
    const autocomplete = document.getElementById('categories-autocomplete');
    const input = document.getElementById('category-input');
    const inputValue = input.value.trim().toLowerCase();
    
    if (!inputValue || categories.length === 0) {
        autocomplete.classList.remove('show');
        return;
    }
    
    const filtered = categories.filter(cat => 
        cat.nombre.toLowerCase().includes(inputValue) && 
        !selectedCategories.has(cat.nombre)
    ).slice(0, 8);
    
    if (filtered.length === 0) {
        autocomplete.classList.remove('show');
        return;
    }
    
    autocomplete.innerHTML = filtered.map((cat, index) => `
        <div class="autocomplete-item ${index === highlightedIndex ? 'highlighted' : ''}" 
             data-category="${cat.nombre}"
             data-index="${index}">
            <i class="fas fa-tag"></i>
            <span>${cat.nombre}</span>
            <span class="autocomplete-count">${cat.count}</span>
        </div>
    `).join('');
    
    autocomplete.classList.add('show');
    
    // Event listeners para items
    autocomplete.querySelectorAll('.autocomplete-item').forEach(item => {
        item.addEventListener('click', () => {
            addCategory(item.dataset.category);
        });
    });
}

// Agregar categoría
function addCategory(name) {
    name = name.trim();
    
    if (!name || selectedCategories.has(name)) {
        return;
    }
    
    if (selectedCategories.size >= 5) {
        Swal.fire({
            icon: 'warning',
            title: 'Límite alcanzado',
            text: 'Máximo 5 categorías por producto',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }
    
    selectedCategories.add(name);
    renderCategories();
    updateCategoriesInput();
    
    const input = document.getElementById('category-input');
    input.value = '';
    document.getElementById('categories-autocomplete').classList.remove('show');
    highlightedIndex = -1;
}

// Eliminar categoría
function removeCategory(btn) {
    const tag = btn.closest('.category-tag');
    const name = tag.dataset.category;
    selectedCategories.delete(name);
    renderCategories();
    updateCategoriesInput();
}

// Renderizar categorías
function renderCategories() {
    const container = document.getElementById('categories-tags');
    const inputWrapper = container.querySelector('.category-input-wrapper');
    
    // Limpiar tags existentes
    container.querySelectorAll('.category-tag').forEach(tag => tag.remove());
    
    // Agregar tags
    selectedCategories.forEach(name => {
        const tag = document.createElement('div');
        tag.className = 'category-tag';
        tag.dataset.category = name;
        tag.innerHTML = `
            <i class="fas fa-tag"></i>
            <span>${name}</span>
            <button type="button" class="category-tag-remove" onclick="removeCategory(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.insertBefore(tag, inputWrapper);
    });
}

// Actualizar input hidden
function updateCategoriesInput() {
    const hidden = document.getElementById('categorias-hidden');
    hidden.value = Array.from(selectedCategories).join(',');
}

// Event listeners para el input de categorías
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    
    const input = document.getElementById('category-input');
    const autocomplete = document.getElementById('categories-autocomplete');
    
    // Input event
    input.addEventListener('input', () => {
        showAutocomplete(allCategories);
    });
    
    // Keydown event
    input.addEventListener('keydown', (e) => {
        const items = autocomplete.querySelectorAll('.autocomplete-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
            showAutocomplete(allCategories);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex = Math.max(highlightedIndex - 1, -1);
            showAutocomplete(allCategories);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            
            if (highlightedIndex >= 0 && items[highlightedIndex]) {
                addCategory(items[highlightedIndex].dataset.category);
            } else {
                const value = input.value.trim();
                if (value) {
                    addCategory(value);
                }
            }
        } else if (e.key === 'Escape') {
            autocomplete.classList.remove('show');
            highlightedIndex = -1;
        } else if (e.key === 'Backspace' && input.value === '') {
            e.preventDefault();
            const lastCategory = Array.from(selectedCategories).pop();
            if (lastCategory) {
                selectedCategories.delete(lastCategory);
                renderCategories();
                updateCategoriesInput();
            }
        }
    });
    
    // Cerrar autocomplete al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.categories-container')) {
            autocomplete.classList.remove('show');
            highlightedIndex = -1;
        }
    });
});

// ============================================
// VALIDACIÓN DEL FORMULARIO
// ============================================
document.getElementById('product-form').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    
    if (!nombre || !descripcion) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Campos requeridos',
            text: 'Por favor completa el nombre y descripción del producto',
            confirmButtonColor: '#6a994e'
        });
        return false;
    }
    
    if (nombre.length < 3) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Nombre muy corto',
            text: 'El nombre debe tener al menos 3 caracteres',
            confirmButtonColor: '#6a994e'
        });
        return false;
    }
    
    if (descripcion.length < 10) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Descripción muy corta',
            text: 'La descripción debe tener al menos 10 caracteres',
            confirmButtonColor: '#6a994e'
        });
        return false;
    }
    
    // Si hay imágenes, enviar con FormData para asegurar que lleguen
    if (selectedFiles.length > 0) {
        e.preventDefault(); // Prevenir envío normal
        
        console.log('Enviando con FormData:', selectedFiles.length, 'archivos');
        
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('descripcion', descripcion);
        formData.append('categoria', document.getElementById('categorias-hidden').value);
        formData.append('estado', document.getElementById('estado').value);
        formData.append('departamento_id', document.getElementById('departamento').value);
        formData.append('ciudad_id', document.getElementById('ciudad').value);
        
        // Agregar cada archivo explícitamente
        selectedFiles.forEach((file, index) => {
            formData.append('imagenes[]', file);
            console.log(`Agregando archivo ${index}:`, file.name, file.size, 'bytes');
        });
        
        const btnSubmit = document.getElementById('btn-submit');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando producto...';
        
        Swal.fire({
            title: 'Creando producto...',
            html: `Subiendo ${selectedFiles.length} imagen(es)...`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Enviar con fetch
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir a editar producto
                window.location.href = `editar-producto.php?id=${data.producto_id}&success=created`;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Hubo un error al crear el producto',
                    confirmButtonColor: '#6a994e'
                });
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Crear Producto';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al crear el producto',
                confirmButtonColor: '#6a994e'
            });
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Crear Producto';
        });
        
        return false;
    }
});

// ===== SISTEMA DE UBICACIONES =====
// Cargar ciudades cuando se selecciona un departamento
document.getElementById('departamento').addEventListener('change', function() {
    const departamentoId = this.value;
    const ciudadSelect = document.getElementById('ciudad');
    
    if (!departamentoId) {
        ciudadSelect.disabled = true;
        ciudadSelect.innerHTML = '<option value="">Primero selecciona un departamento</option>';
        return;
    }
    
    ciudadSelect.disabled = true;
    ciudadSelect.innerHTML = '<option value="">Cargando ciudades...</option>';
    
    fetch(`api/get-ciudades.php?departamento_id=${departamentoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ciudades) {
                ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad...</option>';
                data.ciudades.forEach(ciudad => {
                    const option = document.createElement('option');
                    option.value = ciudad.id;
                    option.textContent = ciudad.nombre + (ciudad.es_capital ? ' (Capital)' : '');
                    ciudadSelect.appendChild(option);
                });
                ciudadSelect.disabled = false;
            } else {
                ciudadSelect.innerHTML = '<option value="">Error al cargar ciudades</option>';
            }
        })
        .catch(error => {
            console.error('Error al cargar ciudades:', error);
            ciudadSelect.innerHTML = '<option value="">Error al cargar ciudades</option>';
        });
});
</script>

<?php include 'includes/footer.php'; ?>
