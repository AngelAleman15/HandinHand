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

// Procesar formulario si se envió
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    
    if (empty($nombre) || empty($descripcion)) {
        $error = 'El nombre y descripción son obligatorios';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, categoria = ?, estado = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$nombre, $descripcion, $categoria, $estado, $producto_id, $_SESSION['user_id']]);
            
            $message = 'Producto actualizado exitosamente';
            
            // Actualizar datos locales
            $producto['nombre'] = $nombre;
            $producto['descripcion'] = $descripcion;
            $producto['categoria'] = $categoria;
            $producto['estado'] = $estado;
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
    padding-top: 80px;
}

.edit-product-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.edit-product-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #313C26 0%, #273122 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.card-header h1 {
    margin: 0;
    font-size: 2.2em;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.card-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.card-body {
    padding: 40px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #313C26;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-control:focus {
    outline: none;
    border-color: #A2CB8D;
    box-shadow: 0 0 0 3px rgba(162,203,141,0.1);
    transform: translateY(-2px);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 40px;
}

.btn-group {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.btn {
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 150px;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    color: #313C26;
    box-shadow: 0 8px 25px rgba(162,203,141,0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(162,203,141,0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #868e96);
    color: white;
    box-shadow: 0 8px 25px rgba(108,117,125,0.3);
}

.btn-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(108,117,125,0.4);
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    border: none;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

@media (max-width: 768px) {
    .edit-product-container {
        margin: 20px auto;
        padding: 0 15px;
    }
    
    .card-body {
        padding: 30px 20px;
    }
    
    .btn-group {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<div class="edit-product-container">
    <!-- Banner WIP -->
    <div style="background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 15px rgba(255,193,7,0.3);">
        <h3 style="margin: 0; font-size: 1.2em;">🚧 Página en Desarrollo</h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Esta sección está siendo desarrollada. Funcionalidad completa próximamente.</p>
    </div>
    
    <div class="edit-product-card">
        <div class="card-header">
            <h1>✏️ Editar Producto</h1>
            <p>Actualiza la información de tu producto</p>
        </div>
        
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-tag"></i> Nombre del Producto
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($producto['nombre']); ?>"
                           required
                           maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">
                        <i class="fas fa-align-left"></i> Descripción
                    </label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              class="form-control" 
                              required
                              maxlength="500"
                              placeholder="Describe tu producto en detalle..."><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="categoria">
                        <i class="fas fa-list"></i> Categoría
                    </label>
                    <select id="categoria" name="categoria" class="form-control form-select">
                        <option value="">Seleccionar categoría</option>
                        <option value="Electrónicos" <?php echo $producto['categoria'] === 'Electrónicos' ? 'selected' : ''; ?>>📱 Electrónicos</option>
                        <option value="Ropa" <?php echo $producto['categoria'] === 'Ropa' ? 'selected' : ''; ?>>👕 Ropa</option>
                        <option value="Libros" <?php echo $producto['categoria'] === 'Libros' ? 'selected' : ''; ?>>📚 Libros</option>
                        <option value="Deportes" <?php echo $producto['categoria'] === 'Deportes' ? 'selected' : ''; ?>>⚽ Deportes</option>
                        <option value="Hogar" <?php echo $producto['categoria'] === 'Hogar' ? 'selected' : ''; ?>>🏠 Hogar</option>
                        <option value="Juguetes" <?php echo $producto['categoria'] === 'Juguetes' ? 'selected' : ''; ?>>🧸 Juguetes</option>
                        <option value="Música" <?php echo $producto['categoria'] === 'Música' ? 'selected' : ''; ?>>🎵 Música</option>
                        <option value="Otro" <?php echo $producto['categoria'] === 'Otro' ? 'selected' : ''; ?>>🔧 Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estado">
                        <i class="fas fa-info-circle"></i> Estado del Producto
                    </label>
                    <select id="estado" name="estado" class="form-control form-select" required>
                        <option value="disponible" <?php echo $producto['estado'] === 'disponible' ? 'selected' : ''; ?>>✅ Disponible</option>
                        <option value="reservado" <?php echo $producto['estado'] === 'reservado' ? 'selected' : ''; ?>>⏳ Reservado</option>
                        <option value="intercambiado" <?php echo $producto['estado'] === 'intercambiado' ? 'selected' : ''; ?>>🔄 Intercambiado</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="mis-productos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    
    if (!nombre || !descripcion) {
        e.preventDefault();
        alert('El nombre y descripción son obligatorios');
        return;
    }
    
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        return;
    }
    
    if (descripcion.length < 10) {
        e.preventDefault();
        alert('La descripción debe tener al menos 10 caracteres');
        return;
    }
});

// Contador de caracteres para la descripción
const descripcionTextarea = document.getElementById('descripcion');
const maxLength = descripcionTextarea.getAttribute('maxlength');

function updateCharCounter() {
    const currentLength = descripcionTextarea.value.length;
    const remaining = maxLength - currentLength;
    
    // Crear contador si no existe
    let counter = document.getElementById('char-counter');
    if (!counter) {
        counter = document.createElement('small');
        counter.id = 'char-counter';
        counter.style.color = '#666';
        counter.style.fontSize = '14px';
        counter.style.marginTop = '5px';
        counter.style.display = 'block';
        descripcionTextarea.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${currentLength}/${maxLength} caracteres`;
    counter.style.color = remaining < 50 ? '#dc3545' : '#666';
}

descripcionTextarea.addEventListener('input', updateCharCounter);
updateCharCounter(); // Inicial
</script>

<?php include 'includes/footer.php'; ?>
