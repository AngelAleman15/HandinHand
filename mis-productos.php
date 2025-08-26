<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Configuración de la página
$page_title = "Mis Productos - HandinHand";
$body_class = "body-my-products";

// Obtener productos del usuario actual
$user = getCurrentUser();
require_once 'config/database.php';
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM productos WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$misProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir header
include 'includes/header.php';
?>

<div class="container">
    <!-- Banner WIP -->
    <div style="background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 15px rgba(255,193,7,0.3);">
        <h3 style="margin: 0; font-size: 1.2em;">🚧 Página en Desarrollo</h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Esta sección está siendo desarrollada. Funcionalidad completa próximamente.</p>
    </div>
    
    <div class="page-header">
        <h1>Mis Productos</h1>
        <button class="btn btn-primary" onclick="showWipMessage('Agregar Producto')">+ Agregar Producto <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></button>
    </div>
    
    <div class="products-grid">
        <?php if (!empty($misProductos)): ?>
            <?php foreach ($misProductos as $producto): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                        <div class="product-status">
                            <span class="status-badge status-<?php echo $producto['estado']; ?>">
                                <?php echo ucfirst($producto['estado']); ?>
                            </span>
                        </div>
                        <div class="product-actions">
                            <button class="btn btn-sm btn-secondary" onclick="editProduct(<?php echo $producto['id']; ?>)">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $producto['id']; ?>)">Eliminar</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-products">
                <h3>No tienes productos publicados</h3>
                <p>¡Agrega tu primer producto para comenzar a intercambiar!</p>
                <button class="btn btn-primary" onclick="addProduct()">Agregar mi primer producto</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #6a994e;
    font-size: 2.5em;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.product-card:hover {
    transform: translateY(-2px);
}

.product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 20px;
}

.product-info h3 {
    color: #333;
    margin-bottom: 10px;
    font-size: 1.3em;
}

.product-info p {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
}

.product-status {
    margin-bottom: 15px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-disponible {
    background-color: #d4edda;
    color: #155724;
}

.status-intercambiado {
    background-color: #cce7ff;
    color: #004085;
}

.status-reservado {
    background-color: #fff3cd;
    color: #856404;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #6a994e;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8em;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-products h3 {
    color: #333;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function addProduct() {
    Swal.fire({
        title: 'Agregar Producto',
        text: 'Funcionalidad para agregar productos próximamente',
        icon: 'info',
        confirmButtonColor: '#6a994e'
    });
}

function editProduct(id) {
    Swal.fire({
        title: 'Editar Producto',
        text: 'Funcionalidad de edición próximamente',
        icon: 'info',
        confirmButtonColor: '#6a994e'
    });
}

function deleteProduct(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica para eliminar el producto
            Swal.fire({
                title: 'Eliminado',
                text: 'Funcionalidad de eliminación próximamente',
                icon: 'info',
                confirmButtonColor: '#6a994e'
            });
        }
    });
}
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
