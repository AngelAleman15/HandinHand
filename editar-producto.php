<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

$page_title = "Editar Producto - HandinHand";
$body_class = "body-edit-product";

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$producto_id) {
    header('Location: mis-productos.php');
    exit();
}

// Conectar a BD y obtener producto
require_once 'config/database.php';
$pdo = getConnection();

// Verificar que el producto pertenece al usuario logueado
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND user_id = ?");
$stmt->execute([$producto_id, $_SESSION['user_id']]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: mis-productos.php');
    exit();
}

// Cargar imágenes adicionales del producto
$stmt_imagenes = $pdo->prepare("SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY es_principal DESC, id ASC");
$stmt_imagenes->execute([$producto_id]);
$imagenes_producto = $stmt_imagenes->fetchAll(PDO::FETCH_ASSOC);

// Cargar departamentos y ciudades para el selector de ubicaciones
try {
    $stmt = $pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre");
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargar ciudades del departamento actual si existe
    $ciudades = [];
    if (!empty($producto['departamento_id'])) {
        $stmt = $pdo->prepare("SELECT id, nombre, es_capital FROM ciudades WHERE departamento_id = ? ORDER BY es_capital DESC, nombre ASC");
        $stmt->execute([$producto['departamento_id']]);
        $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $departamentos = [];
    $ciudades = [];
    error_log("Error al cargar ubicaciones: " . $e->getMessage());
}

// Procesar formulario si se envió
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $departamento_id = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
    $ciudad_id = !empty($_POST['ciudad_id']) ? (int)$_POST['ciudad_id'] : null;
    
    if (empty($nombre) || empty($descripcion)) {
        $error = 'El nombre y descripción son obligatorios';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, categoria = ?, estado = ?, departamento_id = ?, ciudad_id = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$nombre, $descripcion, $categoria, $estado, $departamento_id, $ciudad_id, $producto_id, $_SESSION['user_id']]);
            
            $message = 'Producto actualizado exitosamente';
            
            // Actualizar datos locales
            $producto['nombre'] = $nombre;
            $producto['descripcion'] = $descripcion;
            $producto['categoria'] = $categoria;
            $producto['estado'] = $estado;
            $producto['departamento_id'] = $departamento_id;
            $producto['ciudad_id'] = $ciudad_id;
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<style>
/* Container principal */
.edit-product-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Header moderno */
.edit-product-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 32px 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(106, 153, 78, 0.15);
    margin-bottom: 30px;
    text-align: center;
}

.edit-product-header h1 {
    color: #2c3e50;
    font-size: 2em;
    margin: 0 0 8px 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.edit-product-header h1 i {
    color: #6a994e;
    font-size: 0.9em;
}

.edit-product-header p {
    color: #6c757d;
    font-size: 1em;
    margin: 0;
}

/* Layout de dos columnas */
.edit-product-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

/* Columna izquierda - Imágenes */
.images-column {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 30px;
    height: fit-content;
    position: sticky;
    top: 100px;
}

/* Columna derecha - Formulario */
.form-column {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 35px;
}

/* Sección de imágenes */
.images-section-title {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1em;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.images-section-title i {
    color: #6a994e;
}

/* Mensaje de ayuda */
.images-help-text {
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
    border-left: 4px solid #6a994e;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 0.9em;
    color: #2c5f2d;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.images-help-text i {
    color: #6a994e;
    font-size: 1.1em;
}

/* Imagen principal */
.main-image-preview {
    position: relative;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #6a994e;
    background: #f8f9fa;
}

.main-image-preview img {
    width: 100%;
    height: 300px;
    object-fit: contain;
    background: #ffffff;
    padding: 15px;
}

.main-image-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75em;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Galería de imágenes secundarias */
.images-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
}

.gallery-item:hover {
    border-color: #6a994e;
    transform: scale(1.05);
}

.gallery-item.active {
    border-color: #6a994e;
    border-width: 3px;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
    background: #ffffff;
}

.gallery-item-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #dc3545;
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
    transition: opacity 0.2s ease;
    font-size: 0.75em;
}

.gallery-item:hover .gallery-item-remove {
    opacity: 1;
}

/* Badge de imagen principal */
.badge-principal {
    position: absolute;
    top: 8px;
    left: 8px;
    background: linear-gradient(135deg, #6a994e 0%, #5a8a3e 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    z-index: 5;
}

.gallery-item-add {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #6a994e;
    font-size: 0.85em;
    font-weight: 600;
    cursor: pointer;
    border: 2px dashed #6a994e;
    background: rgba(106, 153, 78, 0.05);
}

.gallery-item-add:hover {
    background: rgba(106, 153, 78, 0.1);
}

.gallery-item-add i {
    font-size: 1.8em;
}

/* Botón de subir imágenes */
.upload-images-btn {
    width: 100%;
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.upload-images-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(106, 153, 78, 0.4);
}

.upload-images-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.upload-message {
    text-align: center;
    font-size: 0.85em;
    margin-top: 10px;
    min-height: 20px;
}

#input-img-producto {
    display: none;
}

/* Formulario */
.form-section-title {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1em;
    margin: 0 0 25px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e9ecef;
}

.form-section-title i {
    color: #6a994e;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.95em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: #6a994e;
    font-size: 1.1em;
}

.form-group label small {
    font-weight: 400;
    color: #6c757d;
    font-size: 0.85em;
    margin-left: auto;
}

/* Tags de categorías */
.categories-container {
    position: relative;
}

.categories-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px;
    min-height: 50px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: #ffffff;
    margin-bottom: 8px;
    cursor: text;
}

.categories-tags:focus-within {
    border-color: #6a994e;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.1);
}

.category-tag {
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 0.85em;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    animation: tagFadeIn 0.2s ease;
}

@keyframes tagFadeIn {
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
    font-size: 0.9em;
}

.category-tag-remove {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.75em;
    transition: all 0.2s ease;
    padding: 0;
}

.category-tag-remove:hover {
    background: rgba(255, 255, 255, 0.5);
    transform: scale(1.1);
}

.category-input-wrapper {
    flex: 1;
    min-width: 150px;
    position: relative;
}

#category-input {
    border: none;
    outline: none;
    padding: 6px 8px;
    font-size: 0.9em;
    background: transparent;
    width: 100%;
}

#category-input::placeholder {
    color: #adb5bd;
}

/* Autocomplete dropdown */
.categories-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #6a994e;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
    margin-top: 4px;
}

.categories-autocomplete.show {
    display: block;
}

.autocomplete-item {
    padding: 10px 15px;
    cursor: pointer;
    transition: background 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9em;
}

.autocomplete-item i {
    color: #6a994e;
    font-size: 0.9em;
}

.autocomplete-item:hover {
    background: rgba(106, 153, 78, 0.1);
}

.autocomplete-item.highlighted {
    background: rgba(106, 153, 78, 0.15);
}

.autocomplete-count {
    margin-left: auto;
    font-size: 0.8em;
    color: #6c757d;
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 10px;
}

.autocomplete-empty {
    padding: 15px;
    text-align: center;
    color: #6c757d;
    font-size: 0.85em;
}

/* Input oculto para guardar categorías */
#categorias-hidden {
    display: none;
}

.form-control {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95em;
    transition: all 0.3s ease;
    background: #ffffff;
    font-family: inherit;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #6a994e;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
    line-height: 1.5;
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236a994e' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 14px center;
    background-repeat: no-repeat;
    background-size: 18px;
    padding-right: 45px;
    cursor: pointer;
}

.form-select option {
    padding: 10px;
}

/* Grid de campos */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-grid .form-group {
    margin-bottom: 0;
}

/* Contador de caracteres */
#char-counter {
    display: block;
    margin-top: 6px;
    font-size: 0.85em;
    color: #6c757d;
    text-align: right;
}

/* Botones */
.btn-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 35px;
    padding-top: 30px;
    border-top: 2px solid #e9ecef;
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-size: 0.95em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(106, 153, 78, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3);
}

/* Alertas */
.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    border: none;
}

.alert i {
    font-size: 1.2em;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #6a994e;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Responsive */
@media (max-width: 1024px) {
    .edit-product-layout {
        grid-template-columns: 1fr;
    }
    
    .images-column {
        position: static;
    }
    
    .images-gallery {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .edit-product-container {
        padding: 20px 15px;
    }
    
    .edit-product-header {
        padding: 25px 20px;
    }
    
    .edit-product-header h1 {
        font-size: 1.6em;
    }
    
    .form-column {
        padding: 25px 20px;
    }
    
    .images-column {
        padding: 20px;
    }
    
    .main-image-preview img {
        height: 250px;
    }
    
    .images-gallery {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    
    .form-grid .form-group {
        margin-bottom: 0;
    }
    
    .btn-group {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="edit-product-container">
    <!-- Header -->
    <div class="edit-product-header">
        <h1>
            <i class="fas fa-edit"></i>
            Editar Producto
        </h1>
        <p>Actualiza la información de tu producto</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Layout de dos columnas -->
    <div class="edit-product-layout">
        <!-- Columna izquierda: Imágenes -->
        <div class="images-column">
            <h3 class="images-section-title">
                <i class="fas fa-images"></i>
                Imágenes del Producto
            </h3>
            
            <div class="images-help-text">
                <i class="fas fa-info-circle"></i>
                Haz clic en una imagen para marcarla como principal. Máximo 6 imágenes.
            </div>
            
            <!-- Imagen principal -->
            <div class="main-image-preview">
                <span class="main-image-badge">
                    <i class="fas fa-star"></i> Principal
                </span>
                <img id="main-image" 
                     src="<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'img/productos/default.jpg'; ?>" 
                     alt="Imagen principal"
                     onerror="this.src='img/productos/default.jpg'">
            </div>
            
            <!-- Galería de imágenes -->
            <div class="images-gallery" id="images-gallery">
                <?php if (!empty($imagenes_producto)): ?>
                    <?php foreach ($imagenes_producto as $index => $img): ?>
                        <div class="gallery-item <?php echo $img['es_principal'] ? 'active' : ''; ?>" 
                             data-index="<?php echo $index; ?>"
                             data-image-id="<?php echo $img['id']; ?>">
                            <img src="<?php echo htmlspecialchars($img['imagen']); ?>" 
                                 alt="Imagen <?php echo $index + 1; ?>"
                                 onerror="this.src='img/productos/default.jpg'">
                            <button class="gallery-item-remove" onclick="event.stopPropagation(); removeImageFromDB(<?php echo $img['id']; ?>, this)" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Si no hay imágenes en producto_imagenes, mostrar la imagen principal del producto -->
                    <div class="gallery-item active" data-index="0">
                        <img src="<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'img/productos/default.jpg'; ?>" 
                             alt="Imagen 1"
                             onerror="this.src='img/productos/default.jpg'">
                    </div>
                <?php endif; ?>
                
                <!-- Botón para agregar más imágenes (solo si hay menos de 6) -->
                <?php if (count($imagenes_producto) < 6): ?>
                <div class="gallery-item gallery-item-add" onclick="document.getElementById('input-img-producto').click()">
                    <i class="fas fa-plus"></i>
                    <span>Agregar</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Input de archivo oculto -->
            <input type="file" 
                   id="input-img-producto" 
                   accept="image/jpeg,image/jpg,image/png,image/webp" 
                   multiple>
            
            <!-- Botón de subir -->
            <button type="button" 
                    class="upload-images-btn" 
                    id="upload-btn" 
                    onclick="uploadImages()" 
                    disabled>
                <i class="fas fa-cloud-upload-alt"></i>
                Subir Imágenes Seleccionadas
            </button>
            
            <div class="upload-message" id="upload-message"></div>
        </div>
        
        <!-- Columna derecha: Formulario -->
        <div class="form-column">
            <h3 class="form-section-title">
                <i class="fas fa-file-alt"></i>
                Información del Producto
            </h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-tag"></i>
                        Nombre del Producto
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($producto['nombre']); ?>"
                           required
                           maxlength="100"
                           placeholder="Ej: iPhone 12 Pro Max">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">
                        <i class="fas fa-align-left"></i>
                        Descripción
                    </label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              class="form-control" 
                              required
                              maxlength="500"
                              placeholder="Describe las características principales de tu producto..."><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    <span id="char-counter"></span>
                </div>
                
                <div class="form-group">
                    <label for="categoria">
                        <i class="fas fa-tags"></i>
                        Categorías
                        <small>(Presiona Enter para agregar)</small>
                    </label>
                    <div class="categories-container">
                        <div class="categories-tags" id="categories-tags" onclick="document.getElementById('category-input').focus()">
                            <?php 
                            // Cargar categorías existentes
                            if (!empty($producto['categoria'])) {
                                $categorias = explode(',', $producto['categoria']);
                                foreach ($categorias as $cat) {
                                    $cat = trim($cat);
                                    if ($cat) {
                                        echo '<div class="category-tag">
                                                <i class="fas fa-tag"></i>
                                                <span>' . htmlspecialchars($cat) . '</span>
                                                <button type="button" class="category-tag-remove" onclick="removeCategory(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                              </div>';
                                    }
                                }
                            }
                            ?>
                            <div class="category-input-wrapper">
                                <input type="text" 
                                       id="category-input" 
                                       placeholder="Escribe una categoría..."
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="categories-autocomplete" id="categories-autocomplete"></div>
                    </div>
                    <input type="hidden" id="categorias-hidden" name="categoria" value="<?php echo htmlspecialchars($producto['categoria'] ?? ''); ?>">
                </div>
                
                <div class="form-grid">
                    <!-- Ubicación -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-map-marker-alt"></i>
                            Ubicación
                            <small style="font-weight: normal; color: #6c757d;">Opcional - Ayuda a los compradores</small>
                        </label>
                        <div class="ubicacion-grid">
                            <select name="departamento_id" id="departamento_edit" class="form-control form-select">
                                <option value="">Seleccionar departamento...</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($producto['departamento_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ciudad_id" id="ciudad_edit" class="form-control form-select" <?php echo empty($producto['departamento_id']) ? 'disabled' : ''; ?>>
                                <option value="">Seleccionar ciudad...</option>
                                <?php foreach ($ciudades as $ciudad): ?>
                                    <option value="<?php echo $ciudad['id']; ?>" <?php echo ($producto['ciudad_id'] == $ciudad['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ciudad['nombre']); ?><?php echo $ciudad['es_capital'] ? ' (Capital)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Estado -->
                    <div class="form-group">
                        <label for="estado">
                            <i class="fas fa-info-circle"></i>
                            Estado del Producto
                        </label>
                        <select id="estado" name="estado" class="form-control form-select" required>
                            <option value="disponible" <?php echo $producto['estado'] === 'disponible' ? 'selected' : ''; ?>>✅ Disponible</option>
                            <option value="reservado" <?php echo $producto['estado'] === 'reservado' ? 'selected' : ''; ?>>⏳ Reservado</option>
                            <option value="intercambiado" <?php echo $producto['estado'] === 'intercambiado' ? 'selected' : ''; ?>>🔄 Intercambiado</option>
                        </select>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="mis-productos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Array para almacenar las imágenes seleccionadas
let selectedImages = [];
const MAX_IMAGES = 6;

// Sistema de categorías
let allCategories = [];
let selectedCategories = new Set();
let highlightedIndex = -1;

// Cargar categorías existentes al inicio
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar categorías desde tags existentes
    document.querySelectorAll('.category-tag span').forEach(tag => {
        selectedCategories.add(tag.textContent.trim());
    });
    updateCategoriesInput();
    
    // Cargar categorías de la BD
    loadCategories();
});

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

// Input de categoría
const categoryInput = document.getElementById('category-input');
const autocompleteDiv = document.getElementById('categories-autocomplete');

categoryInput.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    
    if (query.length === 0) {
        autocompleteDiv.classList.remove('show');
        return;
    }
    
    // Filtrar categorías
    const filtered = allCategories.filter(cat => 
        cat.nombre.toLowerCase().includes(query) &&
        !selectedCategories.has(cat.nombre)
    ).slice(0, 8); // Máximo 8 sugerencias
    
    if (filtered.length > 0) {
        showAutocomplete(filtered);
    } else {
        showEmptyAutocomplete(query);
    }
});

// Mostrar autocomplete
function showAutocomplete(categories) {
    autocompleteDiv.innerHTML = categories.map((cat, index) => `
        <div class="autocomplete-item" data-category="${cat.nombre}" data-index="${index}">
            <i class="fas fa-tag"></i>
            <span>${cat.nombre}</span>
            <span class="autocomplete-count">${cat.count}</span>
        </div>
    `).join('');
    
    autocompleteDiv.classList.add('show');
    highlightedIndex = -1;
    
    // Click en item
    autocompleteDiv.querySelectorAll('.autocomplete-item').forEach(item => {
        item.addEventListener('click', function() {
            addCategory(this.dataset.category);
        });
    });
}

// Mostrar mensaje cuando no hay resultados
function showEmptyAutocomplete(query) {
    autocompleteDiv.innerHTML = `
        <div class="autocomplete-empty">
            No se encontraron categorías. Presiona <strong>Enter</strong> para crear "${query}"
        </div>
    `;
    autocompleteDiv.classList.add('show');
}

// Navegación con teclado
categoryInput.addEventListener('keydown', function(e) {
    const items = autocompleteDiv.querySelectorAll('.autocomplete-item');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
        updateHighlight(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex = Math.max(highlightedIndex - 1, -1);
        updateHighlight(items);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        
        if (highlightedIndex >= 0 && items[highlightedIndex]) {
            addCategory(items[highlightedIndex].dataset.category);
        } else {
            const value = this.value.trim();
            if (value) {
                addCategory(value);
            }
        }
    } else if (e.key === 'Escape') {
        autocompleteDiv.classList.remove('show');
        highlightedIndex = -1;
    } else if (e.key === 'Backspace' && this.value === '' && selectedCategories.size > 0) {
        // Eliminar última categoría con backspace
        const tags = Array.from(document.querySelectorAll('.category-tag'));
        if (tags.length > 0) {
            const lastTag = tags[tags.length - 1];
            removeCategory(lastTag.querySelector('.category-tag-remove'));
        }
    }
});

// Actualizar highlight en autocomplete
function updateHighlight(items) {
    items.forEach((item, index) => {
        if (index === highlightedIndex) {
            item.classList.add('highlighted');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('highlighted');
        }
    });
}

// Agregar categoría
function addCategory(name) {
    name = name.trim();
    
    if (!name || selectedCategories.has(name)) {
        categoryInput.value = '';
        autocompleteDiv.classList.remove('show');
        return;
    }
    
    selectedCategories.add(name);
    
    // Crear tag visual
    const tag = document.createElement('div');
    tag.className = 'category-tag';
    tag.innerHTML = `
        <i class="fas fa-tag"></i>
        <span>${name}</span>
        <button type="button" class="category-tag-remove" onclick="removeCategory(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const inputWrapper = document.querySelector('.category-input-wrapper');
    inputWrapper.parentNode.insertBefore(tag, inputWrapper);
    
    categoryInput.value = '';
    autocompleteDiv.classList.remove('show');
    updateCategoriesInput();
}

// Eliminar categoría
function removeCategory(button) {
    const tag = button.closest('.category-tag');
    const categoryName = tag.querySelector('span').textContent;
    
    selectedCategories.delete(categoryName);
    tag.remove();
    updateCategoriesInput();
}

// Actualizar input oculto
function updateCategoriesInput() {
    const hiddenInput = document.getElementById('categorias-hidden');
    hiddenInput.value = Array.from(selectedCategories).join(', ');
}

// Cerrar autocomplete al hacer click fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.categories-container')) {
        autocompleteDiv.classList.remove('show');
    }
});

// Manejo de selección de archivos
document.getElementById('input-img-producto').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const msg = document.getElementById('upload-message');
    const uploadBtn = document.getElementById('upload-btn');
    
    // Validar cantidad total
    if (selectedImages.length + files.length > MAX_IMAGES) {
        msg.textContent = `⚠️ Máximo ${MAX_IMAGES} imágenes permitidas`;
        msg.style.color = '#dc3545';
        return;
    }
    
    // Validar cada archivo
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    let invalidFiles = [];
    
    files.forEach(file => {
        if (!validTypes.includes(file.type)) {
            invalidFiles.push(file.name);
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            msg.textContent = `⚠️ ${file.name} supera los 5MB`;
            msg.style.color = '#dc3545';
            return;
        }
        
        // Agregar a selectedImages
        selectedImages.push(file);
        
        // Crear preview
        const reader = new FileReader();
        reader.onload = function(e) {
            addImageToGallery(e.target.result, selectedImages.length - 1);
        };
        reader.readAsDataURL(file);
    });
    
    if (invalidFiles.length > 0) {
        msg.textContent = `⚠️ Archivos no válidos: ${invalidFiles.join(', ')}`;
        msg.style.color = '#dc3545';
    } else {
        msg.textContent = `✅ ${selectedImages.length} imagen(es) seleccionada(s)`;
        msg.style.color = '#6a994e';
        uploadBtn.disabled = selectedImages.length === 0;
    }
    
    // Limpiar input
    e.target.value = '';
});

// Agregar imagen a la galería
function addImageToGallery(src, index) {
    const gallery = document.getElementById('images-gallery');
    const addButton = gallery.querySelector('.gallery-item-add');
    
    // Crear elemento de galería
    const item = document.createElement('div');
    item.className = 'gallery-item';
    item.dataset.index = index + 1; // +1 porque el índice 0 es la imagen principal
    
    item.innerHTML = `
        <img src="${src}" alt="Imagen ${index + 2}">
        <button class="gallery-item-remove" onclick="removeImage(${index + 1})" type="button">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Evento click para cambiar imagen principal
    item.querySelector('img').addEventListener('click', function() {
        changeMainImage(src, index + 1);
    });
    
    // Insertar antes del botón de agregar
    gallery.insertBefore(item, addButton);
    
    // Ocultar botón de agregar si llegamos al máximo
    if (gallery.querySelectorAll('.gallery-item:not(.gallery-item-add)').length >= MAX_IMAGES) {
        addButton.style.display = 'none';
    }
}

// Cambiar imagen principal
function changeMainImage(src, index) {
    document.getElementById('main-image').src = src;
    
    // Actualizar clases active
    document.querySelectorAll('.gallery-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeItem = document.querySelector(`.gallery-item[data-index="${index}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
}

// Remover imagen
function removeImage(index) {
    selectedImages.splice(index - 1, 1);
    
    const gallery = document.getElementById('images-gallery');
    const item = gallery.querySelector(`.gallery-item[data-index="${index}"]`);
    if (item) {
        item.remove();
    }
    
    // Re-indexar elementos restantes
    gallery.querySelectorAll('.gallery-item:not(.gallery-item-add)').forEach((item, i) => {
        if (i > 0) { // Saltar la imagen principal (índice 0)
            item.dataset.index = i;
            const removeBtn = item.querySelector('.gallery-item-remove');
            if (removeBtn) {
                removeBtn.onclick = () => removeImage(i);
            }
        }
    });
    
    // Mostrar botón de agregar si hay espacio
    const addButton = gallery.querySelector('.gallery-item-add');
    if (gallery.querySelectorAll('.gallery-item:not(.gallery-item-add)').length < MAX_IMAGES) {
        addButton.style.display = 'flex';
    }
    
    // Actualizar mensaje y botón
    const msg = document.getElementById('upload-message');
    const uploadBtn = document.getElementById('upload-btn');
    
    if (selectedImages.length === 0) {
        msg.textContent = '';
        uploadBtn.disabled = true;
    } else {
        msg.textContent = `✅ ${selectedImages.length} imagen(es) seleccionada(s)`;
        msg.style.color = '#6a994e';
        uploadBtn.disabled = false;
    }
}

// Subir imágenes
async function uploadImages() {
    if (selectedImages.length === 0) return;
    
    const msg = document.getElementById('upload-message');
    const uploadBtn = document.getElementById('upload-btn');
    
    msg.textContent = '⏳ Subiendo imágenes...';
    msg.style.color = '#6a994e';
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    
    try {
        const formData = new FormData();
        
        // Agregar todas las imágenes al FormData
        selectedImages.forEach((file, index) => {
            formData.append('imagenes[]', file);
        });
        
        formData.append('producto_id', '<?php echo $producto_id; ?>');
        
        const response = await fetch('api/upload-producto-imagen.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error al subir imágenes');
        }
        
        msg.textContent = '✅ ' + result.message;
        msg.style.color = '#6a994e';
        
        // Recargar página después de 1.5 segundos
        setTimeout(() => {
            location.reload();
        }, 1500);
        
    } catch (error) {
        msg.textContent = '❌ ' + error.message;
        msg.style.color = '#dc3545';
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Subir Imágenes Seleccionadas';
    }
}

// Eliminar imagen de la base de datos
async function removeImageFromDB(imageId, button) {
    const result = await Swal.fire({
        title: '¿Eliminar imagen?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('image_id', imageId);
        formData.append('producto_id', '<?php echo $producto_id; ?>');
        
        const response = await fetch('api/delete-producto-imagen.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Imagen eliminada',
                timer: 1500,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message || 'Error al eliminar');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#6a994e'
        });
    }
}

// Click en imagen de galería para cambiar principal
document.addEventListener('click', async function(e) {
    // Solo en imágenes de la galería, NO en el botón de eliminar
    if (e.target.closest('.gallery-item:not(.gallery-item-add)') && !e.target.closest('.gallery-item-remove')) {
        const item = e.target.closest('.gallery-item');
        const img = item.querySelector('img');
        
        // Si ya es la imagen principal, no hacer nada
        if (item.classList.contains('active')) {
            return;
        }
        
        // Obtener el ID de la imagen desde el atributo data
        const imageId = item.dataset.imageId;
        
        if (!imageId) {
            console.error('No se encontró el ID de la imagen');
            return;
        }
        
        try {
            // Confirmar acción
            const result = await Swal.fire({
                title: '¿Marcar como imagen principal?',
                text: 'Esta imagen se mostrará como la principal del producto',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6a994e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, marcar como principal',
                cancelButtonText: 'Cancelar'
            });
            
            if (!result.isConfirmed) return;
            
            // Enviar petición al servidor
            const formData = new FormData();
            formData.append('set_principal', imageId);
            formData.append('producto_id', '<?php echo $producto_id; ?>');
            
            const response = await fetch('api/upload-producto-imagen.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen principal actualizada',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Recargar página después de un momento
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Error al actualizar');
            }
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#6a994e'
            });
        }
    }
});

// Validación del formulario principal
document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    
    if (!nombre || nombre.length < 3) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Nombre inválido',
            text: 'El nombre debe tener al menos 3 caracteres',
            confirmButtonColor: '#6a994e'
        });
        return;
    }
    
    if (!descripcion || descripcion.length < 10) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Descripción inválida',
            text: 'La descripción debe tener al menos 10 caracteres',
            confirmButtonColor: '#6a994e'
        });
        return;
    }
    
    if (selectedCategories.size === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Categoría requerida',
            text: 'Por favor agrega al menos una categoría',
            confirmButtonColor: '#6a994e'
        });
        return;
    }
});

// Contador de caracteres para la descripción
const descripcionTextarea = document.getElementById('descripcion');
const maxLength = parseInt(descripcionTextarea.getAttribute('maxlength'));

function updateCharCounter() {
    const currentLength = descripcionTextarea.value.length;
    const remaining = maxLength - currentLength;
    const counter = document.getElementById('char-counter');
    
    counter.textContent = `${currentLength} / ${maxLength} caracteres`;
    
    if (remaining < 50) {
        counter.style.color = '#dc3545';
    } else if (remaining < 100) {
        counter.style.color = '#ffc107';
    } else {
        counter.style.color = '#6c757d';
    }
}

descripcionTextarea.addEventListener('input', updateCharCounter);
updateCharCounter(); // Inicializar

// ===== SISTEMA DE UBICACIONES =====
// Cargar ciudades cuando se selecciona un departamento
const departamentoEdit = document.getElementById('departamento_edit');
if (departamentoEdit) {
    departamentoEdit.addEventListener('change', function() {
        const departamentoId = this.value;
        const ciudadSelect = document.getElementById('ciudad_edit');
        
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
}
</script>

<?php include 'includes/footer.php'; ?>
