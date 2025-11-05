<script>
// Favoritos AJAX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-fav').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-producto-id');
            var self = this;
            fetch('api/favorito-producto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'producto_id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.favorito) {
                        self.querySelector('i').classList.add('fas');
                        self.querySelector('i').classList.remove('far');
                        self.style.color = '#ffc107';
                        self.title = 'Quitar de favoritos';
                    } else {
                        self.querySelector('i').classList.remove('fas');
                        self.querySelector('i').classList.add('far');
                        self.style.color = '#e0a800';
                        self.title = 'Agregar a favoritos';
                    }
                }
            });
        });
    });
});
// Cambio de estado por click en el badge
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.estado-badge').forEach(function(badge) {
        badge.onclick = function(e) {
            e.preventDefault();
            var productoId = this.getAttribute('data-producto-id');
            var estados = ['disponible', 'intercambiado', 'reservado'];
            var actual = this.getAttribute('data-estado');
            var idx = estados.indexOf(actual);
            var nuevoEstado = estados[(idx + 1) % estados.length];
            var self = this;
            fetch('api/update-producto-estado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(productoId) + '&estado=' + encodeURIComponent(nuevoEstado)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    self.setAttribute('data-estado', nuevoEstado);
                    self.className = 'status-badge estado-badge status-' + nuevoEstado;
                    self.textContent = nuevoEstado.toUpperCase();
                } else {
                    alert('Error al actualizar estado: ' + (data.message || ''));
                }
            })
            .catch(() => alert('Error de red al actualizar estado.'));
        };
    });
});
</script>
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
// Obtener categorías
$categorias = [];
$catStmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria = c.nombre AND p.user_id = " . (int)$user['id'] . ") as total_propios FROM categorias c ORDER BY c.nombre");
if ($catStmt) {
    $categorias = $catStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container">
    
    <div class="page-header">
        <h1>Mis Productos</h1>
    <a class="btn btn-primary" href="publicar-producto.php">+ Agregar Producto</a>
    </div>
    
    <div class="products-list">
        <?php if (!empty($misProductos)): ?>
            <div class="products-header-row">
                <div class="product-col product-col-img">Imagen</div>
                 <div class="product-col product-col-nombre">Nombre</div>
                 <div class="product-col product-col-desc">Descripción</div>
                 <div class="product-col product-col-categoria">Categoría</div>
                 <div class="product-col product-col-estado">Estado</div>
                 <div class="product-col product-col-condicion">Condición</div>
                 <div class="product-col product-col-acciones">Acciones</div>
            </div>
            <?php foreach ($misProductos as $producto): ?>
                <div class="product-row" style="cursor:pointer;" onclick="window.location.href='producto.php?id=<?php echo $producto['id']; ?>'">
                    <div class="product-col product-col-img">
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                    </div>
                    <div class="product-col product-col-nombre">
                        <span class="truncate-hover" data-fulltext="<?php echo htmlspecialchars($producto['nombre']); ?>" title="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                        </span>
                    </div>
                    <div class="product-col product-col-desc">
                        <span class="truncate-hover" data-fulltext="<?php echo htmlspecialchars($producto['descripcion']); ?>" title="<?php echo htmlspecialchars($producto['descripcion']); ?>">
                            <?php echo htmlspecialchars($producto['descripcion']); ?>
                        </span>
                    </div>
                    <div class="product-col product-col-categoria">
                        <span class="categoria-badge-listado" title="<?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?>">
                            <?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?>
                        </span>
                    </div>
                    <div class="product-col product-col-estado">
                        <span class="status-badge estado-badge status-<?php echo $producto['estado']; ?>" data-producto-id="<?php echo $producto['id']; ?>" data-estado="<?php echo $producto['estado']; ?>" tabindex="0" title="Haz clic para cambiar el estado" onclick="event.stopPropagation();">
                            <?php echo strtoupper($producto['estado']); ?>
                        </span>
                    </div>
                    <div class="product-col product-col-condicion">
                        <span class="truncate-hover" data-fulltext="<?php echo htmlspecialchars($producto['condicion'] ?? 'N/D'); ?>" title="<?php echo htmlspecialchars($producto['condicion'] ?? 'N/D'); ?>">
                            <?php echo isset($producto['condicion']) ? ucfirst($producto['condicion']) : 'N/D'; ?>
                        </span>
                    </div>
                    <div class="product-col product-col-acciones">
                        <a class="btn btn-sm btn-secondary" href="editar-producto.php?id=<?php echo $producto['id']; ?>" onclick="event.stopPropagation();">Editar</a>
                        <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteProduct(<?php echo $producto['id']; ?>);">Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-products">
                <h3>No tienes productos publicados</h3>
                <p>¡Agrega tu primer producto para comenzar a intercambiar!</p>
                <a class="btn btn-primary" href="publicar-producto.php">Agregar mi primer producto</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
</script>
// Cambio de estado por click en el badge
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.estado-badge').forEach(function(badge) {
        badge.addEventListener('click', function() {
            var productoId = this.getAttribute('data-producto-id');
            var estados = ['disponible', 'intercambiado', 'reservado'];
            var actual = this.getAttribute('data-estado');
            var idx = estados.indexOf(actual);
            var nuevoEstado = estados[(idx + 1) % estados.length];
            var self = this;
            fetch('api/update-producto-estado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(productoId) + '&estado=' + encodeURIComponent(nuevoEstado)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    self.setAttribute('data-estado', nuevoEstado);
                    self.className = 'status-badge estado-badge status-' + nuevoEstado;
                    self.textContent = nuevoEstado.toUpperCase();
                } else {
                    alert('Error al actualizar estado: ' + (data.message || ''));
                }
            })
            .catch(() => alert('Error de red al actualizar estado.'));
        });
    });
});
    font-size: 1em;
    padding: 4px 12px;
    text-transform: uppercase;
    box-shadow: none;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    min-width: 120px;
    transition: border 0.2s, background 0.2s;
}
.estado-select:focus {
    border: 2px solid #388e3c;
    background: #e9f5e1;
}
.categoria-badge-listado {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    border: 1px solid #90caf9;
    border-radius: 8px;
    padding: 2px 10px;
    font-size: 0.98em;
    font-weight: 500;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
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
    gap: 32px 28px;
    margin-bottom: 40px;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.products-list {
    width: 100%;
    margin-bottom: 40px;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.products-header-row, .product-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
    padding: 10px 0;
}
.product-row {
    transition: background-color 0.2s ease;
}
.product-row:hover {
    background-color: #f0f8f0;
}
.products-header-row {
    font-weight: bold;
    background: #f5f5f5;
    border-radius: 8px 8px 0 0;
    border-bottom: 2px solid #b2dfdb;
    color: #388e3c;
    font-size: 1.1em;
}
.product-col {
    padding: 0 12px;
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.product-col-img {
    flex: 0 0 70px;
    justify-content: center;
}
.product-col-nombre, .product-col-desc {
    flex: 2 1 0;
}
.product-col-estado {
    flex: 1 1 0;
    justify-content: center;
}
.product-col-acciones {
    flex: 1 1 0;
    justify-content: flex-end;
    gap: 8px;
}
.truncate-hover {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    position: relative;
    padding: 2px 6px;
    border-radius: 5px;
    transition: background 0.2s, color 0.2s;
}
.truncate-hover:hover {
    background: #e9f5e1;
    color: #1b5e20;
    z-index: 2;
}
.truncate-hover::after {
    content: attr(data-fulltext);
    display: none;
    position: absolute;
    left: 0;
    top: 110%;
    background: #fff;
    color: #222;
    border: 1px solid #b2dfdb;
    border-radius: 6px;
    padding: 6px 12px;
    white-space: normal;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    min-width: 180px;
    max-width: 350px;
    font-size: 1em;
    pointer-events: none;
}
.truncate-hover:hover::after {
    display: block;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 0;
    font-size: 0.9em;
    font-weight: bold;
    text-transform: uppercase;
    border: 1.5px solid #155724;
    background: #d4edda;
    color: #155724;
    box-shadow: none;
}
.estado-select {
    border-radius: 0 !important;
    border: 1.5px solid #155724;
    background: #d4edda;
    color: #155724;
    font-weight: bold;
    font-size: 0.98em;
    padding: 4px 12px;
    text-transform: uppercase;
    box-shadow: none;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    min-width: 120px;
    transition: border 0.2s, background 0.2s;
}
.estado-select:focus {
    border: 2px solid #388e3c;
    background: #e9f5e1;
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

.estado-badge {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    border: 1px solid #90caf9;
    border-radius: 8px;
    padding: 2px 10px;
    font-size: 0.98em;
    font-weight: 500;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    user-select: none;
    outline: none;
    transition: background 0.2s, border 0.2s, color 0.2s;
    text-align: center;
    box-shadow: 0 2px 8px rgba(25,118,210,0.07);
    letter-spacing: 0.5px;
}
.estado-badge:focus, .estado-badge:hover {
    border: 2px solid #1976d2;
    background: #bbdefb;
    color: #0d47a1;
}
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
